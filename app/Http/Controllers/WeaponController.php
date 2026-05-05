<?php

namespace App\Http\Controllers;

use App\Events\WeaponChanged;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\File;
use App\Models\IncidentType;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponIncident;
use App\Models\WeaponPhoto;
use App\Services\WeaponDocumentService;
use App\Support\WeaponDocumentAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class WeaponController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Weapon::class, 'weapon');
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $filters = $this->filtersFromRequest($request);
        $query = $this->buildIndexQuery($request)
            ->with($this->indexRelationships());

        $this->applyInventoryOrdering($query);

        $weapons = $query
            ->paginate(50)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'tbody' => view('weapons.partials.index_rows', compact('weapons'))->render(),
                'pagination' => view('weapons.partials.index_pagination', compact('weapons'))->render(),
            ]);
        }

        [$clients, $responsibles] = $this->indexFilterOptions($request->user());
        $weaponTypes = $this->weaponTypeOptions();
        $destinationOptions = $this->destinationOptions();
        $incidentTypes = IncidentType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $incidentStatusOptions = WeaponIncident::statusOptions();

        return view('weapons.index', compact(
            'weapons',
            'search',
            'filters',
            'clients',
            'responsibles',
            'weaponTypes',
            'destinationOptions',
            'incidentTypes',
            'incidentStatusOptions',
        ));
    }

    public function create()
    {
        $ownershipTypes = $this->ownershipOptions();

        return view('weapons.create', compact('ownershipTypes'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Weapon::class);
        $format = $this->normalizeExportFormat($request->input('format'));

        $query = $this->buildExportQuery($request)
            ->with($this->indexRelationships());

        $this->applyInventoryOrdering($query);

        return $this->streamWeaponsExport(
            $query->get(),
            'armamento-filtrado-' . now()->format('Ymd-His'),
            $format
        );
    }

    public function exportPreview(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Weapon::class);

        $hasExplicitFilters = $this->hasExplicitExportFilters($request);
        $query = $this->buildExportQuery($request);
        $count = (clone $query)->count();

        if (!$hasExplicitFilters) {
            return response()->json([
                'count' => $count,
                'has_filters' => false,
                'items' => [],
                'truncated' => false,
            ]);
        }

        $previewLimit = 50;
        $previewQuery = (clone $query)
            ->with($this->indexRelationships());

        $this->applyInventoryOrdering($previewQuery);

        $items = $previewQuery
            ->limit($previewLimit)
            ->get()
            ->map(fn (Weapon $weapon) => $this->weaponPreviewRow($weapon))
            ->values()
            ->all();

        return response()->json([
            'count' => $count,
            'has_filters' => true,
            'items' => $items,
            'truncated' => $count > $previewLimit,
        ]);
    }

    public function exportSelected(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Weapon::class);
        $format = $this->normalizeExportFormat($request->input('format'));

        $validated = $request->validate([
            'weapon_ids' => ['required', 'array', 'min:1'],
            'weapon_ids.*' => ['integer', 'distinct'],
        ]);

        $weaponIds = collect($validated['weapon_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $query = Weapon::query()
            ->whereIn('id', $weaponIds);

        $filters = $this->filtersFromRequest($request);

        $this->applyInventoryScope($query, $filters['inventory_scope']);
        $this->applyRoleScope($query, $request->user());

        $query->with($this->indexRelationships());

        $this->applyInventoryOrdering($query);

        $weapons = $query
            ->get();

        if ($weapons->count() !== count($weaponIds)) {
            abort(403);
        }

        return $this->streamWeaponsExport(
            $weapons,
            'armamento-seleccionado-' . now()->format('Ymd-His'),
            $format
        );
    }

    public function store(Request $request, WeaponDocumentService $documentService)
    {
        $data = $request->validate([
            'internal_code' => ['nullable', 'string', 'max:100', 'unique:weapons,internal_code'],
            'serial_number' => ['required', 'string', 'max:100', 'unique:weapons,serial_number'],
            'weapon_type' => ['required', 'in:' . implode(',', $this->weaponTypeOptions())],
            'caliber' => ['required', 'string', 'max:100'],
            'brand' => ['required', 'string', 'max:100'],
            'capacity' => ['required', 'string', 'max:50'],
            'ownership_type' => ['required', 'in:' . implode(',', array_keys($this->ownershipOptions()))],
            'ownership_entity' => ['nullable', 'string', 'max:255'],
            'permit_type' => ['required', 'in:porte,tenencia'],
            'permit_number' => ['nullable', 'string', 'max:100'],
            'permit_expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['nullable', 'file', 'image', 'max:5120'],
            'permit_photo' => ['required', 'file', 'image', 'max:5120'],
        ]);

        if (empty($data['internal_code'])) {
            $data['internal_code'] = $this->generateInternalCode();
        }

        $photos = $request->file('photos', []);
        $photoOrder = array_keys(WeaponPhoto::DESCRIPTIONS);
        $permitPhoto = $request->file('permit_photo');
        $storedPaths = [];
        $storedPermitPath = null;
        $weapon = null;

        try {
            DB::transaction(function () use (&$weapon, $data, $photos, $photoOrder, $permitPhoto, $request, $documentService, &$storedPaths, &$storedPermitPath) {
                $weapon = Weapon::create($data);

                if ($permitPhoto) {
                    $storedPermitPath = $permitPhoto->store('weapons/' . $weapon->id . '/permits', 'local');
                    $storedFile = File::create([
                        'disk' => 'local',
                        'path' => $storedPermitPath,
                        'original_name' => $permitPhoto->getClientOriginalName(),
                        'mime_type' => $permitPhoto->getClientMimeType(),
                        'size' => $permitPhoto->getSize(),
                        'checksum' => hash_file('sha256', $permitPhoto->getRealPath()),
                        'uploaded_by' => $request->user()?->id,
                    ]);

                    $weapon->update(['permit_file_id' => $storedFile->id]);
                }

                $documentService->syncPermitDocument($weapon);
                $documentService->syncRenewalDocument($weapon);

                if (!$photos) {
                    return;
                }

                foreach ($photoOrder as $index => $description) {
                    $photoFile = $photos[$index] ?? null;
                    if (!$photoFile) {
                        continue;
                    }

                    $existingPhoto = $weapon->photos()->with('file')
                        ->where('description', $description)
                        ->first();
                    if ($existingPhoto) {
                        if ($existingPhoto->file) {
                            Storage::disk($existingPhoto->file->disk)->delete($existingPhoto->file->path);
                            $existingPhoto->file->delete();
                        }
                        $existingPhoto->delete();
                    }

                    $path = $photoFile->store('weapons/' . $weapon->id . '/photos', 'public');
                    $storedPaths[] = $path;

                    $storedFile = File::create([
                        'disk' => 'public',
                        'path' => $path,
                        'original_name' => $photoFile->getClientOriginalName(),
                        'mime_type' => $photoFile->getClientMimeType(),
                        'size' => $photoFile->getSize(),
                        'checksum' => hash_file('sha256', $photoFile->getRealPath()),
                        'uploaded_by' => $request->user()?->id,
                    ]);

                    $photo = $weapon->photos()->create([
                        'file_id' => $storedFile->id,
                        'description' => $description,
                    ]);

                    AuditLog::create([
                        'user_id' => $request->user()?->id,
                        'action' => 'upload_photo',
                        'auditable_type' => Weapon::class,
                        'auditable_id' => $weapon->id,
                        'before' => null,
                        'after' => [
                            'photo_id' => $photo->id,
                            'file_id' => $storedFile->id,
                            'description' => $photo->description,
                        ],
                    ]);
                }
            });
        } catch (Throwable $e) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }
            if ($storedPermitPath) {
                Storage::disk('local')->delete($storedPermitPath);
            }
            throw $e;
        }

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'weapon_created',
            'auditable_type' => Weapon::class,
            'auditable_id' => $weapon->id,
            'before' => null,
            'after' => $weapon->only(['internal_code', 'serial_number', 'weapon_type', 'caliber', 'brand', 'capacity']),
        ]);

        event(new WeaponChanged('created', $weapon->id));

        return redirect()->route('weapons.show', $weapon)->with('status', 'Arma creada.');
    }

    public function show(Weapon $weapon)
    {
        $weapon->load([
            'photos' => function ($query) {
                $query->orderBy('id');
            },
            'photos.file',
            'documents' => function ($query) {
                $query->orderByDesc('is_permit')
                    ->orderByDesc('is_renewal')
                    ->orderByDesc('id');
            },
            'documents.file',
            'activeClientAssignment.client',
            'activeClientAssignment.responsible',
            'activePostAssignment.post',
            'activeWorkerAssignment.worker',
        ]);
        $ownershipTypes = $this->ownershipOptions();
        $responsibles = collect();
        $posts = collect();
        $workers = collect();
        $clientOptions = collect();
        $clientResponsibleMap = [];

        if (request()->user()?->isAdmin()) {
            $responsibles = \App\Models\User::whereIn('role', ['RESPONSABLE', 'ADMIN'])->orderBy('name')->get();
            $clientOptions = Client::orderBy('name')->get();
        } elseif (request()->user()?->isResponsible()) {
            $responsibles = collect([request()->user()]);
            $clientOptions = request()->user()?->clients()->orderBy('name')->get() ?? collect();
        }

        if ($clientOptions->isNotEmpty()) {
            $clientResponsibleMap = Client::query()
                ->whereIn('id', $clientOptions->pluck('id'))
                ->with([
                    'users' => function ($query) {
                        $query->whereIn('role', ['RESPONSABLE', 'ADMIN'])
                            ->orderByRaw("CASE WHEN role = 'RESPONSABLE' THEN 0 WHEN role = 'ADMIN' THEN 1 ELSE 2 END")
                            ->orderBy('name');
                    },
                ])
                ->get()
                ->mapWithKeys(function (Client $client) {
                    $responsible = $client->users->first();
                    return [
                        $client->id => [
                            'id' => $responsible?->id,
                            'name' => $responsible?->name,
                        ],
                    ];
                })
                ->all();
        }

        $activeClientId = $weapon->activeClientAssignment?->client_id;
        if ($activeClientId) {
            if (request()->user()?->isAdmin()) {
                $posts = \App\Models\Post::where('client_id', $activeClientId)->active()->orderBy('name')->get();
                $workers = \App\Models\Worker::where('client_id', $activeClientId)->active()->orderBy('name')->get();
            } elseif (request()->user()?->isResponsible()) {
                $inPortfolio = request()->user()?->clients()->whereKey($activeClientId)->exists() ?? false;
                if ($inPortfolio) {
                    $posts = \App\Models\Post::where('client_id', $activeClientId)->active()->orderBy('name')->get();
                    $workers = \App\Models\Worker::where('client_id', $activeClientId)
                        ->active()
                        ->where('responsible_user_id', request()->user()?->id)
                        ->orderBy('name')
                        ->get();
                }
            }
        }

        $photoOrder = array_keys(WeaponPhoto::DESCRIPTIONS);
        $weapon->setRelation(
            'photos',
            $weapon->photos
                ->sortBy(fn ($photo) => array_search($photo->description, $photoOrder, true) ?? PHP_INT_MAX)
                ->values()
        );

        return view('weapons.show', compact(
            'weapon',
            'ownershipTypes',
            'responsibles',
            'posts',
            'workers',
            'clientOptions',
            'clientResponsibleMap',
        ));
    }

    public function permitPhoto(Weapon $weapon)
    {
        $this->authorize('view', $weapon);

        $permitFile = $weapon->permitFile;
        if (!$permitFile) {
            abort(404);
        }

        return Storage::disk($permitFile->disk)->response(
            $permitFile->path,
            $permitFile->original_name ?? 'permiso'
        );
    }

    public function updatePermitPhoto(Request $request, Weapon $weapon, WeaponDocumentService $documentService)
    {
        $this->authorize('updatePhotos', $weapon);

        $data = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        $permitPhoto = $data['photo'];
        $storedPermitPath = $permitPhoto->store('weapons/' . $weapon->id . '/permits', 'local');

        try {
            DB::transaction(function () use ($request, $weapon, $permitPhoto, $storedPermitPath) {
                $storedFile = File::create([
                    'disk' => 'local',
                    'path' => $storedPermitPath,
                    'original_name' => $permitPhoto->getClientOriginalName(),
                    'mime_type' => $permitPhoto->getClientMimeType(),
                    'size' => $permitPhoto->getSize(),
                    'checksum' => hash_file('sha256', $permitPhoto->getRealPath()),
                    'uploaded_by' => $request->user()?->id,
                ]);

                $oldPermitFile = $weapon->permitFile;
                $weapon->update(['permit_file_id' => $storedFile->id]);

                if ($oldPermitFile) {
                    Storage::disk($oldPermitFile->disk)->delete($oldPermitFile->path);
                    $oldPermitFile->delete();
                }
            });
        } catch (Throwable $e) {
            Storage::disk('local')->delete($storedPermitPath);
            throw $e;
        }

        $documentService->syncPermitDocument($weapon);
        $documentService->syncRenewalDocument($weapon);

        event(new WeaponChanged('updated', $weapon->id));

        return response()->json(['ok' => true]);
    }

    public function edit(Weapon $weapon)
    {
        $weapon->loadMissing(['photos.file', 'permitFile']);
        $ownershipTypes = $this->ownershipOptions();

        return view('weapons.edit', compact('weapon', 'ownershipTypes'));
    }

    public function update(Request $request, Weapon $weapon, WeaponDocumentService $documentService)
    {
        $data = $request->validate([
            'internal_code' => ['required', 'string', 'max:100', 'unique:weapons,internal_code,' . $weapon->id],
            'serial_number' => ['required', 'string', 'max:100', 'unique:weapons,serial_number,' . $weapon->id],
            'weapon_type' => ['required', 'in:' . implode(',', $this->weaponTypeOptions())],
            'caliber' => ['required', 'string', 'max:100'],
            'brand' => ['required', 'string', 'max:100'],
            'capacity' => ['required', 'string', 'max:50'],
            'ownership_type' => ['required', 'in:' . implode(',', array_keys($this->ownershipOptions()))],
            'ownership_entity' => ['nullable', 'string', 'max:255'],
            'permit_type' => ['required', 'in:porte,tenencia'],
            'permit_number' => ['nullable', 'string', 'max:100'],
            'permit_expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['nullable', 'file', 'image', 'max:5120'],
            'permit_photo' => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $before = $weapon->only([
            'internal_code',
            'serial_number',
            'weapon_type',
            'caliber',
            'brand',
            'capacity',
            'permit_type',
            'permit_number',
            'permit_expires_at',
        ]);

        $photos = $request->file('photos', []);
        $photoOrder = array_keys(WeaponPhoto::DESCRIPTIONS);
        $permitPhoto = $request->file('permit_photo');
        $storedPaths = [];
        $storedPermitPath = null;

        try {
            DB::transaction(function () use ($data, $photos, $photoOrder, $permitPhoto, $request, $weapon, $documentService, &$storedPaths, &$storedPermitPath) {
                if ($permitPhoto) {
                    $storedPermitPath = $permitPhoto->store('weapons/' . $weapon->id . '/permits', 'local');
                    $storedFile = File::create([
                        'disk' => 'local',
                        'path' => $storedPermitPath,
                        'original_name' => $permitPhoto->getClientOriginalName(),
                        'mime_type' => $permitPhoto->getClientMimeType(),
                        'size' => $permitPhoto->getSize(),
                        'checksum' => hash_file('sha256', $permitPhoto->getRealPath()),
                        'uploaded_by' => $request->user()?->id,
                    ]);

                    $data['permit_file_id'] = $storedFile->id;
                }

                $oldPermitFile = $weapon->permitFile;
                $weapon->update($data);

                if ($permitPhoto && $oldPermitFile) {
                    Storage::disk($oldPermitFile->disk)->delete($oldPermitFile->path);
                    $oldPermitFile->delete();
                }

                $documentService->syncPermitDocument($weapon);
                $documentService->syncRenewalDocument($weapon);

                foreach ($photoOrder as $index => $description) {
                    $photoFile = $photos[$index] ?? null;
                    if (!$photoFile) {
                        continue;
                    }

                    $existingPhoto = $weapon->photos()->with('file')
                        ->where('description', $description)
                        ->first();
                    if ($existingPhoto) {
                        if ($existingPhoto->file) {
                            Storage::disk($existingPhoto->file->disk)->delete($existingPhoto->file->path);
                            $existingPhoto->file->delete();
                        }
                        $existingPhoto->delete();
                    }

                    $path = $photoFile->store('weapons/' . $weapon->id . '/photos', 'public');
                    $storedPaths[] = $path;

                    $storedFile = File::create([
                        'disk' => 'public',
                        'path' => $path,
                        'original_name' => $photoFile->getClientOriginalName(),
                        'mime_type' => $photoFile->getClientMimeType(),
                        'size' => $photoFile->getSize(),
                        'checksum' => hash_file('sha256', $photoFile->getRealPath()),
                        'uploaded_by' => $request->user()?->id,
                    ]);

                    $photo = $weapon->photos()->create([
                        'file_id' => $storedFile->id,
                        'description' => $description,
                    ]);

                    AuditLog::create([
                        'user_id' => $request->user()?->id,
                        'action' => 'upload_photo',
                        'auditable_type' => Weapon::class,
                        'auditable_id' => $weapon->id,
                        'before' => null,
                        'after' => [
                            'photo_id' => $photo->id,
                            'file_id' => $storedFile->id,
                            'description' => $photo->description,
                        ],
                    ]);
                }
            });
        } catch (Throwable $e) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }
            if ($storedPermitPath) {
                Storage::disk('local')->delete($storedPermitPath);
            }
            throw $e;
        }

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'weapon_updated',
            'auditable_type' => Weapon::class,
            'auditable_id' => $weapon->id,
            'before' => $before,
            'after' => $weapon->only([
                'internal_code',
                'serial_number',
                'weapon_type',
                'caliber',
                'brand',
                'capacity',
                'permit_type',
                'permit_number',
                'permit_expires_at',
            ]),
        ]);

        event(new WeaponChanged('updated', $weapon->id));

        return redirect()->route('weapons.show', $weapon)->with('status', 'Arma actualizada.');
    }

    public function destroy(Weapon $weapon)
    {
        AuditLog::create([
            'user_id' => request()->user()?->id,
            'action' => 'weapon_delete_blocked',
            'auditable_type' => Weapon::class,
            'auditable_id' => $weapon->id,
            'before' => $weapon->only(['internal_code', 'serial_number', 'weapon_type', 'caliber', 'brand', 'capacity']),
            'after' => null,
        ]);

        return redirect()
            ->route('weapons.show', $weapon)
            ->with('status', 'La eliminación física de armas esta deshabilitada. Usa historial y novedades para mantener la trazabilidad.');
    }

    private function ownershipOptions(): array
    {
        return [
            'company_owned' => 'Propiedad de la empresa',
            'leased' => 'Arrendada',
            'third_party' => 'Terceros',
        ];
    }

    private function weaponTypeOptions(): array
    {
        return [
            'Escopeta',
            'Pistola',
            "Rev\u{00F3}lver",
            'Subametralladora',
        ];
    }

    private function destinationOptions(): array
    {
        return [
            'with_destination' => 'Con destino',
            'without_destination' => 'Sin destino',
            'post' => 'Asignadas a puesto',
            'worker' => 'Asignadas a trabajador',
        ];
    }

    private function normalizeExportFormat(mixed $format): string
    {
        return $format === 'csv' ? 'csv' : 'xlsx';
    }

    private function buildIndexQuery(Request $request): Builder
    {
        $query = Weapon::query();
        $filters = $this->filtersFromRequest($request);

        $this->applyInventoryScope($query, $filters['inventory_scope']);
        $this->applyRoleScope($query, $request->user());
        $this->applySearch($query, trim((string) $request->input('q', '')));
        $this->applyFilters($query, $filters);

        return $query;
    }

    private function buildExportQuery(Request $request): Builder
    {
        $query = Weapon::query();
        $filters = $this->filtersFromRequest($request);

        if (!$this->hasExplicitExportFilters($request)) {
            $filters['inventory_scope'] = 'all';
        }

        $this->applyInventoryScope($query, $filters['inventory_scope']);
        $this->applyRoleScope($query, $request->user());
        $this->applySearch($query, trim((string) $request->input('q', '')));
        $this->applyFilters($query, $filters);

        return $query;
    }

    private function hasExplicitExportFilters(Request $request): bool
    {
        $filters = $this->filtersFromRequest($request);

        if (trim((string) $request->input('q', '')) !== '') {
            return true;
        }

        if (($filters['inventory_scope'] ?? 'operational') !== 'operational') {
            return true;
        }

        foreach ([
            'client_id',
            'responsible_user_id',
            'weapon_type',
            'incident_type_id',
            'incident_status',
            'permit_expires_from',
            'permit_expires_to',
            'destination',
        ] as $key) {
            if (!empty($filters[$key])) {
                return true;
            }
        }

        return false;
    }

    private function applyInventoryScope(Builder $query, string $scope): void
    {
        switch ($scope) {
            case 'all':
                return;
            case 'non_operational':
                $query->nonOperationalInventory();
                return;
            case 'operational':
            default:
                $query->operationalInventory();
                return;
        }
    }

    private function applyRoleScope(Builder $query, User $user): void
    {
        if ($user->isResponsible() && !$user->isAdmin()) {
            $query->whereHas('clientAssignments', function (Builder $assignmentQuery) use ($user) {
                $assignmentQuery
                    ->where('responsible_user_id', $user->id)
                    ->where('is_active', true);
            });
        }
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($search) {
            $builder->where('serial_number', 'like', '%' . $search . '%')
                ->orWhere('weapon_type', 'like', '%' . $search . '%')
                ->orWhere('permit_type', 'like', '%' . $search . '%')
                ->orWhere('permit_number', 'like', '%' . $search . '%')
                ->orWhere('caliber', 'like', '%' . $search . '%')
                ->orWhere('brand', 'like', '%' . $search . '%')
                ->orWhereHas('activeClientAssignment.client', function (Builder $clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('activeClientAssignment.responsible', function (Builder $userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('activePostAssignment.post', function (Builder $postQuery) use ($search) {
                    $postQuery->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('activeWorkerAssignment.worker', function (Builder $workerQuery) use ($search) {
                    $workerQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('document', 'like', '%' . $search . '%');
                });
        });
    }

    private function applyInventoryOrdering(Builder $query): void
    {
        $activeClientName = Client::query()
            ->select('clients.name')
            ->join('weapon_client_assignments as active_assignment', 'active_assignment.client_id', '=', 'clients.id')
            ->whereColumn('active_assignment.weapon_id', 'weapons.id')
            ->where('active_assignment.is_active', true)
            ->orderByDesc('active_assignment.start_at')
            ->orderByDesc('active_assignment.id')
            ->limit(1);

        $query
            ->addSelect(['active_client_name' => $activeClientName])
            ->orderByRaw('CASE WHEN weapons.permit_expires_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('weapons.permit_expires_at')
            ->orderByRaw('CASE WHEN active_client_name IS NULL THEN 1 ELSE 0 END')
            ->orderBy('active_client_name')
            ->orderByDesc('weapons.id');
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['client_id']) {
            $query->whereHas('activeClientAssignment', function (Builder $assignmentQuery) use ($filters) {
                $assignmentQuery->where('client_id', $filters['client_id']);
            });
        }

        if ($filters['responsible_user_id']) {
            $query->whereHas('activeClientAssignment', function (Builder $assignmentQuery) use ($filters) {
                $assignmentQuery->where('responsible_user_id', $filters['responsible_user_id']);
            });
        }

        if ($filters['weapon_type']) {
            $query->where('weapon_type', $filters['weapon_type']);
        }

        if ($filters['incident_type_id']) {
            $query->whereHas('openIncidents', function (Builder $incidentQuery) use ($filters) {
                $incidentQuery->where('incident_type_id', $filters['incident_type_id']);
            });
        }

        if ($filters['incident_status']) {
            $query->whereHas('incidents', function (Builder $incidentQuery) use ($filters) {
                $incidentQuery->where('status', $filters['incident_status']);
            });
        }

        if ($filters['permit_expires_from']) {
            $query->whereDate('permit_expires_at', '>=', $filters['permit_expires_from']);
        }

        if ($filters['permit_expires_to']) {
            $query->whereDate('permit_expires_at', '<=', $filters['permit_expires_to']);
        }

        switch ($filters['destination']) {
            case 'with_destination':
                $query->where(function (Builder $builder) {
                    $builder->whereHas('activeClientAssignment')
                        ->orWhereHas('activePostAssignment')
                        ->orWhereHas('activeWorkerAssignment');
                });
                break;
            case 'without_destination':
                $query->whereDoesntHave('activeClientAssignment')
                    ->whereDoesntHave('activePostAssignment')
                    ->whereDoesntHave('activeWorkerAssignment');
                break;
            case 'post':
                $query->whereHas('activePostAssignment');
                break;
            case 'worker':
                $query->whereHas('activeWorkerAssignment');
                break;
        }
    }

    private function filtersFromRequest(Request $request): array
    {
        $inventoryScope = trim((string) $request->input('inventory_scope', 'operational')) ?: 'operational';

        if (!in_array($inventoryScope, ['operational', 'all', 'non_operational'], true)) {
            $inventoryScope = 'operational';
        }

        return [
            'inventory_scope' => $inventoryScope,
            'client_id' => $request->filled('client_id') ? (int) $request->input('client_id') : null,
            'responsible_user_id' => $request->filled('responsible_user_id') ? (int) $request->input('responsible_user_id') : null,
            'weapon_type' => trim((string) $request->input('weapon_type', '')) ?: null,
            'incident_type_id' => $request->filled('incident_type_id') ? (int) $request->input('incident_type_id') : null,
            'incident_status' => trim((string) $request->input('incident_status', '')) ?: null,
            'permit_expires_from' => trim((string) $request->input('permit_expires_from', '')) ?: null,
            'permit_expires_to' => trim((string) $request->input('permit_expires_to', '')) ?: null,
            'destination' => trim((string) $request->input('destination', '')) ?: null,
        ];
    }

    private function indexRelationships(): array
    {
        return [
            'activeClientAssignment.client',
            'activeClientAssignment.responsible',
            'activePostAssignment.post',
            'activeWorkerAssignment.worker',
            'documents',
            'openIncidents.type',
            'openIncidents.modality',
            'operationalBlockingIncidents.type',
            'operationalBlockingIncidents.modality',
        ];
    }

    private function indexFilterOptions(User $user): array
    {
        $clients = $user->isResponsible() && !$user->isAdmin()
            ? $user->clients()->orderBy('name')->get(['clients.id', 'name'])
            : Client::query()->orderBy('name')->get(['id', 'name']);

        $responsibles = $user->isResponsible() && !$user->isAdmin()
            ? collect([$user])
            : User::query()
                ->whereIn('role', ['ADMIN', 'RESPONSABLE'])
                ->orderBy('name')
                ->get(['id', 'name']);

        return [$clients, $responsibles];
    }

    private function streamWeaponsExport(iterable $weapons, string $filename, string $format): StreamedResponse
    {
        if ($format === 'csv') {
            return $this->streamWeaponsCsvExport($weapons, $filename . '.csv');
        }

        return $this->streamWeaponsXlsxExport($weapons, $filename . '.xlsx');
    }

    private function streamWeaponsCsvExport(iterable $weapons, string $filename): StreamedResponse
    {
        $headers = [
            'Cliente',
            'Tipo',
            'Marca',
            'Serie',
            'Calibre',
            'Capacidad',
            'Tipo de permiso',
            "N\u{00B0} de permiso",
            'Vence',
            'Estado',
            'Novedad activa',
            "Cant. munici\u{00F3}n",
            'Cant. proveedor',
            'Responsable',
            'Puesto o trabajador',
            "C\u{00E9}dula",
            'Impronta',
        ];

        return response()->streamDownload(function () use ($weapons, $headers) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xFF\xFE");
            $this->writeExcelCsvLine($output, ['sep=;']);
            $this->writeExcelCsvLine($output, $headers);

            foreach ($weapons as $weapon) {
                $this->writeExcelCsvLine($output, $this->weaponExportRow($weapon));
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-16LE',
        ]);
    }

    private function streamWeaponsXlsxExport(iterable $weapons, string $filename): StreamedResponse
    {
        $headers = [
            'Cliente',
            'Tipo',
            'Marca',
            'Serie',
            'Calibre',
            'Capacidad',
            'Tipo de permiso',
            "N\u{00B0} de permiso",
            'Vence',
            'Estado',
            'Novedad activa',
            "Cant. munici\u{00F3}n",
            'Cant. proveedor',
            'Responsable',
            'Puesto o trabajador',
            "C\u{00E9}dula",
            'Impronta',
        ];

        $rows = [];
        foreach ($weapons as $weapon) {
            $rows[] = $this->weaponExportRow($weapon);
        }

        return response()->streamDownload(function () use ($headers, $rows) {
            $temporaryPath = tempnam(sys_get_temp_dir(), 'weapons-export-');
            $zip = new \ZipArchive();
            $zip->open($temporaryPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypesXml());
            $zip->addFromString('_rels/.rels', $this->xlsxRootRelsXml());
            $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml());
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRelsXml());
            $zip->addFromString('xl/styles.xml', $this->xlsxStylesXml());
            $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxWorksheetXml($headers, $rows));
            $zip->close();

            $handle = fopen($temporaryPath, 'rb');
            fpassthru($handle);
            fclose($handle);
            @unlink($temporaryPath);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function writeExcelCsvLine($output, array $fields): void
    {
        $line = collect($fields)
            ->map(function ($value) {
                $text = (string) ($value ?? '');
                $escaped = str_replace('"', '""', $text);

                return '"' . $escaped . '"';
            })
            ->implode(';') . "\r\n";

        fwrite($output, mb_convert_encoding($line, 'UTF-16LE', 'UTF-8'));
    }

    private function xlsxContentTypesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML;
    }

    private function xlsxRootRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;
    }

    private function xlsxWorkbookXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Armamento" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>
XML;
    }

    private function xlsxWorkbookRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
    }

    private function xlsxStylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font>
            <sz val="11"/>
            <name val="Aptos"/>
            <family val="2"/>
        </font>
        <font>
            <b/>
            <sz val="11"/>
            <color rgb="FFFFFFFF"/>
            <name val="Aptos"/>
            <family val="2"/>
        </font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill>
            <patternFill patternType="solid">
                <fgColor rgb="FF162457"/>
                <bgColor indexed="64"/>
            </patternFill>
        </fill>
    </fills>
    <borders count="1">
        <border>
            <left/><right/><top/><bottom/><diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="2">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
    </cellXfs>
</styleSheet>
XML;
    }

    private function xlsxWorksheetXml(array $headers, array $rows): string
    {
        $allRows = array_merge([$headers], $rows);
        $columnWidths = [22, 18, 18, 18, 14, 12, 18, 18, 14, 24, 24, 14, 14, 22, 22, 18, 14];
        $lastColumn = $this->xlsxColumnName(count($headers));
        $sheetRows = [];

        foreach ($allRows as $rowIndex => $row) {
            $cells = [];
            $style = $rowIndex === 0 ? ' s="1"' : '';

            foreach ($row as $columnIndex => $value) {
                $reference = $this->xlsxColumnName($columnIndex + 1) . ($rowIndex + 1);
                $escaped = $this->xlsxEscape((string) ($value ?? ''));
                $cells[] = '<c r="' . $reference . '" t="inlineStr"' . $style . '><is><t>' . $escaped . '</t></is></c>';
            }

            $sheetRows[] = '<row r="' . ($rowIndex + 1) . '">' . implode('', $cells) . '</row>';
        }

        $columnsXml = [];
        foreach ($columnWidths as $index => $width) {
            $column = $index + 1;
            $columnsXml[] = '<col min="' . $column . '" max="' . $column . '" width="' . $width . '" customWidth="1"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<cols>' . implode('', $columnsXml) . '</cols>'
            . '<sheetData>' . implode('', $sheetRows) . '</sheetData>'
            . '<autoFilter ref="A1:' . $lastColumn . '1"/>'
            . '</worksheet>';
    }

    private function xlsxColumnName(int $columnIndex): string
    {
        $name = '';

        while ($columnIndex > 0) {
            $columnIndex--;
            $name = chr(65 + ($columnIndex % 26)) . $name;
            $columnIndex = intdiv($columnIndex, 26);
        }

        return $name;
    }

    private function xlsxEscape(string $value): string
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';

        return htmlspecialchars($clean, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
    private function weaponPreviewRow(Weapon $weapon): array
    {
        return [
            'id' => $weapon->id,
            'client' => $weapon->activeClientAssignment?->client?->name ?? 'Sin destino',
            'type' => $weapon->weapon_type,
            'brand' => $weapon->brand,
            'serial' => $weapon->serial_number,
            'caliber' => $weapon->caliber,
            'permit_type' => $weapon->permit_type ? Str::ucfirst($weapon->permit_type) : '-',
            'permit_number' => $weapon->permit_number ?? '-',
            'expires_at' => $weapon->permit_expires_at?->format('Y-m-d') ?? '-',
        ];
    }

    private function weaponExportRow(Weapon $weapon): array
    {
        $renewalDocument = $weapon->documents->firstWhere('is_renewal', true)
            ?? $weapon->documents->firstWhere('is_permit', true);
        $renewalAlert = WeaponDocumentAlert::forComplianceDocument($renewalDocument);
        $manualInProcess = $weapon->documents
            ->filter(fn ($doc) => !($doc->is_permit || $doc->is_renewal))
            ->first(fn ($doc) => ($doc->status ?? '') === 'En proceso');
        $openIncident = $weapon->openIncidents->first();
        $internalAssignment = $weapon->activeWorkerAssignment ?? $weapon->activePostAssignment;
        $statusText = $manualInProcess
            ? trim(($manualInProcess->document_name ?: 'Documento') . ': ' . ($manualInProcess->observations ?: 'En proceso'))
            : ($renewalAlert['observation'] !== '-'
                ? $renewalAlert['observation']
                : ($weapon->activeClientAssignment ? 'Asignada' : 'Sin destino'));
        $incidentText = $openIncident
            ? trim(($openIncident->type?->name ?? 'Novedad') . ($openIncident->modality ? ' / ' . $openIncident->modality->name : ''))
            : 'Sin novedades';

        $destination = '-';
        if ($weapon->activeWorkerAssignment) {
            $destination = $weapon->activeWorkerAssignment->worker?->name ?? '-';
        } elseif ($weapon->activePostAssignment) {
            $destination = $weapon->activePostAssignment->post?->name ?? '-';
        }

        return [
            $weapon->activeClientAssignment?->client?->name ?? 'Sin destino',
            $weapon->weapon_type,
            $weapon->brand,
            $weapon->serial_number,
            $weapon->caliber,
            $weapon->capacity ?? '-',
            ($weapon->permit_type ? Str::ucfirst($weapon->permit_type) : '-'),
            $weapon->permit_number ?? '-',
            $weapon->permit_expires_at?->format('Y-m-d') ?? '-',
            $statusText,
            $incidentText,
            $internalAssignment?->ammo_count ?? '-',
            $internalAssignment?->provider_count ?? '-',
            $weapon->activeClientAssignment?->responsible?->name ?? '-',
            $destination,
            $weapon->activeWorkerAssignment?->worker?->document ?? '-',
            $weapon->imprint_month ? 'Recibida ' . $weapon->imprint_month : 'Pendiente',
        ];
    }

    private function generateInternalCode(): string
    {
        $prefix = 'SJ-';
        $latestCode = Weapon::where('internal_code', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(internal_code, 4) AS UNSIGNED) DESC')
            ->value('internal_code');
        $lastNumber = $latestCode ? (int) preg_replace('/\D/', '', $latestCode) : 0;
        $code = sprintf('%s%04d', $prefix, $lastNumber + 1);

        return $code;
    }

    public function toggleImprint(Request $request, Weapon $weapon)
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            abort(403);
        }

        $month = now()->format('Y-m');
        $received = $request->boolean('received');

        if ($received) {
            $weapon->update([
                'imprint_month' => $month,
                'imprint_received_by' => $user->id,
                'imprint_received_at' => now(),
            ]);
        } else {
            $weapon->update([
                'imprint_month' => null,
                'imprint_received_by' => null,
                'imprint_received_at' => null,
            ]);
        }

        event(new WeaponChanged('updated', $weapon->id));

        return back();
    }
}





