<?php

namespace App\Http\Controllers;

use App\Events\AssignmentChanged;
use App\Events\PostChanged;
use App\Models\Client;
use App\Models\AuditLog;
use App\Models\Post;
use App\Models\PostHistory;
use App\Models\Weapon;
use App\Models\WeaponPostAssignment;
use App\Services\GeocodingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            $action = $request->route()?->getActionMethod();

            if ($action === 'index' || $action === 'histories') {
                if (!$user?->isAdmin() && !$user?->isResponsible() && !$user?->isAuditor()) {
                    abort(403);
                }

                return $next($request);
            }

            $writes = ['create', 'store', 'edit', 'update', 'destroy', 'restore'];
            if (in_array($action, $writes, true)) {
                if ($user?->isAdmin()) {
                    return $next($request);
                }
                if ($user?->isResponsibleLevelOne()) {
                    return $next($request);
                }

                abort(403);
            }

            abort(403);
        });
    }

    public function index(Request $request)
    {
        $query = Post::with('client');
        $user = $request->user();
        $search = trim((string) $request->input('q', ''));
        $clientId = $request->integer('client_id');
        $archiveFilter = $request->input('archive', 'active');
        if (!in_array($archiveFilter, ['active', 'archived', 'all'], true)) {
            $archiveFilter = 'active';
        }

        if ($user?->isResponsible() && !$user?->isAdmin()) {
            $clientIds = $user->clients()->pluck('clients.id');
            $query->whereIn('client_id', $clientIds);
        }

        if ($archiveFilter === 'archived') {
            $query->archived();
        } elseif ($archiveFilter === 'active') {
            $query->active();
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $posts = $query->orderBy('name')
            ->paginate(15)
            ->appends($request->except(['_rt']));

        if ($request->ajax()) {
            return response()->json([
                'tbody' => view('posts.partials.index_rows', compact('posts'))->render(),
                'pagination' => view('posts.partials.index_pagination', compact('posts'))->render(),
            ])->header('Cache-Control', 'private, no-store, must-revalidate');
        }

        if ($user?->isResponsible() && !$user?->isAdmin()) {
            $clients = $user->clients()->orderBy('name')->get();
        } else {
            $clients = Client::orderBy('name')->get();
        }

        return response()
            ->view('posts.index', compact('posts', 'clients', 'search', 'clientId', 'archiveFilter'))
            ->header('Cache-Control', 'private, no-store, must-revalidate');
    }

    private function clientIdValidationRules(Request $request): array
    {
        $user = $request->user();
        $rules = ['required', 'exists:clients,id'];
        if ($user && $user->isResponsibleLevelOne() && !$user->isAdmin()) {
            $ids = $user->clients()->pluck('clients.id')->all();
            $rules[] = Rule::in($ids);
        }

        return $rules;
    }

    private function clientsForForm(Request $request)
    {
        $user = $request->user();
        if ($user?->isAdmin()) {
            return Client::orderBy('name')->get();
        }
        if ($user?->isResponsibleLevelOne()) {
            return $user->clients()->orderBy('name')->get();
        }

        return collect();
    }

    public function create(Request $request)
    {
        $this->authorize('create', Post::class);

        $clients = $this->clientsForForm($request);

        return view('posts.create', compact('clients'));
    }

    public function store(Request $request, GeocodingService $geocodingService)
    {
        $this->authorize('create', Post::class);

        $data = $request->validate([
            'client_id' => $this->clientIdValidationRules($request),
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('posts', 'name')->where(
                    fn ($q) => $q->where('client_id', (int) $request->input('client_id'))->whereNull('archived_at')
                ),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'coords_source' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);

        $client = Client::find($data['client_id']);
        $useMapCoords = ($data['coords_source'] ?? null) === 'map';
        if ($useMapCoords && !empty($data['latitude']) && !empty($data['longitude'])) {
            $data['latitude'] = (float) $data['latitude'];
            $data['longitude'] = (float) $data['longitude'];
        } elseif (!empty($data['address'])) {
            $coords = $geocodingService->geocode(
                $data['address'] ?? null,
                $data['city'] ?? ($client?->city),
                $data['department'] ?? ($client?->department),
            );
            if ($coords) {
                $data['latitude'] = $coords['lat'];
                $data['longitude'] = $coords['lng'];
            }
        }

        unset($data['coords_source']);
        $data['archived_at'] = null;

        $post = Post::create($data);

        PostHistory::create([
            'post_id' => $post->id,
            'user_id' => $request->user()?->id,
            'body' => $this->initialHistoryBody($request->user()?->name, $data['notes'] ?? null),
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'post_created',
            'auditable_type' => Post::class,
            'auditable_id' => $post->id,
            'before' => null,
            'after' => $post->only(['client_id', 'name', 'address', 'city', 'department']),
        ]);

        event(new PostChanged('created', $post->id, ['client_id' => $post->client_id]));

        return redirect()->route('posts.index')->with('status', 'Puesto creado.');
    }

    public function histories(Request $request, Post $post)
    {
        $this->authorize('view', $post);

        $entries = $post->histories()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PostHistory $h) => [
                'id' => $h->id,
                'body' => $h->body,
                'user' => $h->user?->name,
                'at' => $h->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            ]);

        return response()->json(['entries' => $entries]);
    }

    public function edit(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $clients = $this->clientsForForm($request);

        return view('posts.edit', compact('post', 'clients'));
    }

    public function update(Request $request, Post $post, GeocodingService $geocodingService)
    {
        $this->authorize('update', $post);

        $data = $request->validate([
            'client_id' => $this->clientIdValidationRules($request),
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('posts', 'name')
                    ->where(
                        fn ($q) => $q->where('client_id', (int) $request->input('client_id'))->whereNull('archived_at')
                    )
                    ->ignore($post->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'coords_source' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'change_note' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        $changeNote = trim((string) ($data['change_note'] ?? ''));
        unset($data['change_note']);

        $addressChanged = ($data['address'] ?? null) !== $post->address
            || ($data['city'] ?? null) !== $post->city
            || ($data['department'] ?? null) !== $post->department
            || ($data['client_id'] ?? null) !== $post->client_id;
        $useMapCoords = ($data['coords_source'] ?? null) === 'map';
        if ($useMapCoords && !empty($data['latitude']) && !empty($data['longitude'])) {
            $data['latitude'] = (float) $data['latitude'];
            $data['longitude'] = (float) $data['longitude'];
        } elseif ($addressChanged && !empty($data['address'])) {
            $client = Client::find($data['client_id']);
            $coords = $geocodingService->geocode(
                $data['address'] ?? null,
                $data['city'] ?? ($client?->city),
                $data['department'] ?? ($client?->department),
            );
            if ($coords) {
                $data['latitude'] = $coords['lat'];
                $data['longitude'] = $coords['lng'];
            }
        }

        $before = $post->only(['client_id', 'name', 'address', 'city', 'department', 'notes']);
        unset($data['coords_source']);

        $post->update($data);

        PostHistory::create([
            'post_id' => $post->id,
            'user_id' => $request->user()?->id,
            'body' => $changeNote,
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'post_updated',
            'auditable_type' => Post::class,
            'auditable_id' => $post->id,
            'before' => $before,
            'after' => $post->only(['client_id', 'name', 'address', 'city', 'department', 'notes']),
        ]);

        event(new PostChanged('updated', $post->id, ['client_id' => $post->client_id]));

        return redirect()->route('posts.index')->with('status', 'Puesto actualizado.');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $before = $post->only(['client_id', 'name']);

        $weaponIdsAffected = [];

        DB::transaction(function () use ($post, &$weaponIdsAffected) {
            $weaponIdsAffected = WeaponPostAssignment::query()
                ->where('post_id', $post->id)
                ->where('is_active', true)
                ->pluck('weapon_id')
                ->all();

            WeaponPostAssignment::query()
                ->where('post_id', $post->id)
                ->where('is_active', true)
                ->update([
                    'end_at' => now()->toDateString(),
                    'is_active' => null,
                ]);

            $post->archived_at = now();
            $post->save();
        });

        foreach ($weaponIdsAffected as $weaponId) {
            $weapon = Weapon::query()->with('activeClientAssignment')->find($weaponId);
            $clientId = $weapon?->activeClientAssignment?->client_id;
            event(new AssignmentChanged('unassigned', $weaponId, [
                'client_id' => $clientId,
            ]));
        }

        AuditLog::create([
            'user_id' => request()->user()?->id,
            'action' => 'post_archived',
            'auditable_type' => Post::class,
            'auditable_id' => $post->id,
            'before' => $before,
            'after' => ['archived_at' => $post->archived_at?->toDateTimeString()],
        ]);

        event(new PostChanged('archived', $post->id, ['client_id' => $post->client_id]));

        PostHistory::create([
            'post_id' => $post->id,
            'user_id' => request()->user()?->id,
            'body' => __('Registro: puesto archivado.'),
        ]);

        return redirect()
            ->route('posts.index', ['archive' => 'archived'])
            ->with('status', 'Puesto archivado. Las armas que estaban asignadas a este puesto quedaron sin ubicación interna activa.');
    }

    public function restore(Request $request, Post $post)
    {
        $this->authorize('restore', $post);

        $conflict = Post::query()
            ->where('client_id', $post->client_id)
            ->where('name', $post->name)
            ->active()
            ->where('id', '!=', $post->id)
            ->exists();

        if ($conflict) {
            return redirect()
                ->route('posts.index', ['archive' => 'archived'])
                ->withErrors([
                    'restore' => 'Ya existe un puesto activo con el mismo nombre para este cliente. Edite el nombre (puesto archivado o activo) antes de reactivar.',
                ]);
        }

        $wasArchivedAt = $post->archived_at;

        $post->archived_at = null;
        $post->save();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'post_restored',
            'auditable_type' => Post::class,
            'auditable_id' => $post->id,
            'before' => ['archived_at' => $wasArchivedAt?->toDateTimeString()],
            'after' => ['archived_at' => null],
        ]);

        event(new PostChanged('restored', $post->id, ['client_id' => $post->client_id]));

        PostHistory::create([
            'post_id' => $post->id,
            'user_id' => $request->user()?->id,
            'body' => __('Registro: puesto reactivado.'),
        ]);

        return redirect()->route('posts.index')->with('status', 'Puesto reactivado.');
    }

    private function initialHistoryBody(?string $userName, ?string $notes): string
    {
        $header = __('Registro inicial.');
        if ($userName) {
            $header .= ' ' . __('Usuario: :name.', ['name' => $userName]);
        }
        $notes = $notes !== null ? trim($notes) : '';
        if ($notes !== '') {
            return $header . "\n\n" . __('Notas:') . "\n" . $notes;
        }

        return $header;
    }
}
