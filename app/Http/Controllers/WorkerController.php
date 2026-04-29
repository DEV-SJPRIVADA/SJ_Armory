<?php

namespace App\Http\Controllers;

use App\Events\AssignmentChanged;
use App\Events\WorkerChanged;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponWorkerAssignment;
use App\Models\Worker;
use App\Models\WorkerHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkerController extends Controller
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
        $query = Worker::with(['client', 'responsible']);
        $user = $request->user();
        $search = trim((string) $request->input('q', ''));
        $clientId = $request->integer('client_id');
        $role = $request->input('role');
        $showResponsibleFilter = ! ($user?->isResponsible() && ! $user?->isAdmin());
        $responsibleId = $showResponsibleFilter ? $request->integer('responsible_user_id') : 0;
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
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($responsibleId) {
            $query->where('responsible_user_id', $responsibleId);
        }

        $workers = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        if ($user?->isResponsible() && !$user?->isAdmin()) {
            $clients = $user->clients()->orderBy('name')->get();
            $responsibles = collect([$user]);
        } else {
            $clients = Client::orderBy('name')->get();
            $responsibles = User::where('role', 'RESPONSABLE')->orderBy('name')->get();
        }
        $roles = $this->roleOptions();

        return view('workers.index', compact(
            'workers',
            'clients',
            'responsibles',
            'roles',
            'search',
            'clientId',
            'role',
            'responsibleId',
            'archiveFilter',
            'showResponsibleFilter'
        ));
    }

    private function clientIdValidationRules(Request $request): array
    {
        $user = $request->user();
        $rules = ['required', 'exists:clients,id'];
        if ($user && $user->isResponsibleLevelOne() && !$user->isAdmin()) {
            $rules[] = Rule::in($user->clients()->pluck('clients.id')->all());
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
        $this->authorize('create', Worker::class);

        $clients = $this->clientsForForm($request);
        $user = $request->user();
        $lockResponsible = $user && ! $user->isAdmin() && $user->isResponsibleLevelOne();
        $responsibles = $lockResponsible
            ? collect([$user])
            : User::where('role', 'RESPONSABLE')->orderBy('name')->get();
        $roles = $this->roleOptions();

        return view('workers.create', compact('clients', 'responsibles', 'roles', 'lockResponsible'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Worker::class);

        $user = $request->user();
        $lockResponsible = $user && ! $user->isAdmin() && $user->isResponsibleLevelOne();

        $data = $request->validate([
            'client_id' => $this->clientIdValidationRules($request),
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'in:' . implode(',', array_keys($this->roleOptions()))],
            'responsible_user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($lockResponsible) {
            $data['responsible_user_id'] = $user->id;
        }

        $data['archived_at'] = null;

        $worker = Worker::create($data);

        WorkerHistory::create([
            'worker_id' => $worker->id,
            'user_id' => $request->user()?->id,
            'body' => $this->initialHistoryBody($request->user()?->name, $data['notes'] ?? null),
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'worker_created',
            'auditable_type' => Worker::class,
            'auditable_id' => $worker->id,
            'before' => null,
            'after' => $worker->only(['client_id', 'name', 'document', 'role', 'responsible_user_id']),
        ]);

        event(new WorkerChanged('created', $worker->id, ['client_id' => $worker->client_id]));

        return redirect()->route('workers.index')->with('status', 'Trabajador creado.');
    }

    public function histories(Request $request, Worker $worker)
    {
        $this->authorize('view', $worker);

        $entries = $worker->histories()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (WorkerHistory $h) => [
                'id' => $h->id,
                'body' => $h->body,
                'user' => $h->user?->name,
                'at' => $h->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            ]);

        return response()->json(['entries' => $entries]);
    }

    public function edit(Request $request, Worker $worker)
    {
        $this->authorize('update', $worker);

        $clients = $this->clientsForForm($request);
        $user = $request->user();
        $lockResponsible = $user && ! $user->isAdmin() && $user->isResponsibleLevelOne();
        $responsibles = $lockResponsible
            ? collect([$user])
            : User::where('role', 'RESPONSABLE')->orderBy('name')->get();
        $roles = $this->roleOptions();

        return view('workers.edit', compact('worker', 'clients', 'responsibles', 'roles', 'lockResponsible'));
    }

    public function update(Request $request, Worker $worker)
    {
        $this->authorize('update', $worker);

        $user = $request->user();
        $lockResponsible = $user && ! $user->isAdmin() && $user->isResponsibleLevelOne();

        $data = $request->validate([
            'client_id' => $this->clientIdValidationRules($request),
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'in:' . implode(',', array_keys($this->roleOptions()))],
            'responsible_user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'change_note' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        if ($lockResponsible) {
            $data['responsible_user_id'] = $user->id;
        }

        $changeNote = trim((string) ($data['change_note'] ?? ''));
        unset($data['change_note']);

        $before = $worker->only(['client_id', 'name', 'document', 'role', 'responsible_user_id', 'notes']);
        $worker->update($data);

        WorkerHistory::create([
            'worker_id' => $worker->id,
            'user_id' => $request->user()?->id,
            'body' => $changeNote,
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'worker_updated',
            'auditable_type' => Worker::class,
            'auditable_id' => $worker->id,
            'before' => $before,
            'after' => $worker->only(['client_id', 'name', 'document', 'role', 'responsible_user_id', 'notes']),
        ]);

        event(new WorkerChanged('updated', $worker->id, ['client_id' => $worker->client_id]));

        return redirect()->route('workers.index')->with('status', 'Trabajador actualizado.');
    }

    public function destroy(Worker $worker)
    {
        $this->authorize('delete', $worker);

        $before = $worker->only(['client_id', 'name', 'document', 'role', 'responsible_user_id']);

        $weaponIdsAffected = [];

        DB::transaction(function () use ($worker, &$weaponIdsAffected) {
            $weaponIdsAffected = WeaponWorkerAssignment::query()
                ->where('worker_id', $worker->id)
                ->where('is_active', true)
                ->pluck('weapon_id')
                ->all();

            WeaponWorkerAssignment::query()
                ->where('worker_id', $worker->id)
                ->where('is_active', true)
                ->update([
                    'end_at' => now()->toDateString(),
                    'is_active' => null,
                ]);

            $worker->archived_at = now();
            $worker->save();
        });

        foreach ($weaponIdsAffected as $weaponId) {
            $w = Weapon::query()->with('activeClientAssignment')->find($weaponId);
            $clientId = $w?->activeClientAssignment?->client_id;
            event(new AssignmentChanged('unassigned', $weaponId, [
                'client_id' => $clientId,
            ]));
        }

        AuditLog::create([
            'user_id' => request()->user()?->id,
            'action' => 'worker_archived',
            'auditable_type' => Worker::class,
            'auditable_id' => $worker->id,
            'before' => $before,
            'after' => ['archived_at' => $worker->archived_at?->toDateTimeString()],
        ]);

        event(new WorkerChanged('archived', $worker->id, ['client_id' => $worker->client_id]));

        WorkerHistory::create([
            'worker_id' => $worker->id,
            'user_id' => request()->user()?->id,
            'body' => __('Registro: trabajador archivado.'),
        ]);

        return redirect()
            ->route('workers.index', ['archive' => 'archived'])
            ->with('status', 'Trabajador archivado. Las armas asignadas a este trabajador quedaron sin ubicación interna activa.');
    }

    public function restore(Request $request, Worker $worker)
    {
        $this->authorize('restore', $worker);

        $wasArchivedAt = $worker->archived_at;

        $worker->archived_at = null;
        $worker->save();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'worker_restored',
            'auditable_type' => Worker::class,
            'auditable_id' => $worker->id,
            'before' => ['archived_at' => $wasArchivedAt?->toDateTimeString()],
            'after' => ['archived_at' => null],
        ]);

        event(new WorkerChanged('restored', $worker->id, ['client_id' => $worker->client_id]));

        WorkerHistory::create([
            'worker_id' => $worker->id,
            'user_id' => $request->user()?->id,
            'body' => __('Registro: trabajador reactivado.'),
        ]);

        return redirect()->route('workers.index')->with('status', 'Trabajador reactivado.');
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

    private function roleOptions(): array
    {
        return [
            Worker::ROLE_ESCOLTA => 'Escolta',
            Worker::ROLE_SUPERVISOR => 'Supervisor',
        ];
    }
}
