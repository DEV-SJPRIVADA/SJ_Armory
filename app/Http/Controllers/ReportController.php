<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Post;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponClientAssignment;
use App\Models\WeaponDocument;
use App\Models\WeaponIncident;
use App\Models\WeaponTransfer;
use App\Models\Worker;
use App\Services\WeaponIncidentReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();

        return view('reports.index');
    }

    public function weaponsByClient(Request $request)
    {
        $this->authorizeAdmin();

        $clients = Client::orderBy('name')->get();
        $clientId = $request->integer('client_id');

        $query = Weapon::query()->with(['activeClientAssignment.client', 'activeClientAssignment.responsible']);
        if ($clientId) {
            $query->whereHas('clientAssignments', function ($assignmentQuery) use ($clientId) {
                $assignmentQuery->where('is_active', true)->where('client_id', $clientId);
            });
        } else {
            $query->whereHas('clientAssignments', function ($assignmentQuery) {
                $assignmentQuery->where('is_active', true);
            });
        }

        $weapons = $query->orderBy('internal_code')->paginate(50)->withQueryString();

        return view('reports.weapons_by_client', compact('weapons', 'clients', 'clientId'));
    }

    public function weaponsWithoutDestination()
    {
        $this->authorizeAdmin();

        $weapons = Weapon::whereDoesntHave('clientAssignments', function ($assignmentQuery) {
            $assignmentQuery->where('is_active', true);
        })->orderBy('internal_code')->paginate(50)->withQueryString();

        return view('reports.weapons_without_destination', compact('weapons'));
    }

    public function history(Request $request, WeaponIncidentReportService $weaponSearch)
    {
        $this->authorizeAdmin();

        $weaponId = $request->integer('weapon_id');
        $weapon = null;
        $selectedWeapon = null;
        $assignments = collect();
        $documents = collect();
        $incidents = collect();

        if ($weaponId) {
            $weapon = Weapon::query()
                ->with([
                    'activeClientAssignment.client',
                    'activeClientAssignment.responsible',
                ])
                ->find($weaponId);

            if ($weapon) {
                $selectedWeapon = $weaponSearch->findSearchWeapon($request->user(), $weapon->id);
                $assignments = WeaponClientAssignment::with(['client', 'assignedBy', 'responsible'])
                    ->where('weapon_id', $weaponId)
                    ->orderByDesc('start_at')
                    ->get();
                $documents = WeaponDocument::with('file')
                    ->where('weapon_id', $weaponId)
                    ->orderByDesc('created_at')
                    ->get();
                $incidents = WeaponIncident::with([
                    'type',
                    'modality',
                    'reporter',
                    'latestUpdate',
                    'attachmentFile',
                ])
                    ->where('weapon_id', $weaponId)
                    ->orderByDesc('event_at')
                    ->orderByDesc('id')
                    ->get();
            }
        }

        return view('reports.weapon_history', compact('weapon', 'selectedWeapon', 'assignments', 'documents', 'incidents'));
    }

    public function audit(Request $request)
    {
        $this->authorizeAdmin();

        $days = (int) $request->input('days', 30);
        if (! in_array($days, [30, 90], true)) {
            $days = 30;
        }

        $module = (string) $request->input('module', 'all');
        $modules = [
            'all' => 'Todos',
            'clients' => 'Clientes',
            'weapons' => 'Armas',
            'transfers' => 'Transferencias',
            'posts' => 'Puestos',
            'workers' => 'Trabajadores',
            'users' => 'Usuarios',
            'portfolios' => 'Asignaciones',
        ];
        if (! array_key_exists($module, $modules)) {
            $module = 'all';
        }

        $since = now()->subDays($days);
        $logsQuery = AuditLog::with([
            'user',
            'auditable',
        ])
            ->where('created_at', '>=', $since);

        $moduleFilters = [
            'clients' => ['types' => [Client::class], 'actions' => []],
            'weapons' => ['types' => [Weapon::class, WeaponClientAssignment::class], 'actions' => []],
            'transfers' => ['types' => [WeaponTransfer::class], 'actions' => []],
            'posts' => ['types' => [Post::class], 'actions' => []],
            'workers' => ['types' => [Worker::class], 'actions' => []],
            'users' => ['types' => [User::class], 'actions' => []],
            'portfolios' => [
                'types' => [User::class],
                'actions' => ['portfolio_updated', 'portfolio_transferred', 'client_responsible_transferred'],
            ],
        ];

        if ($module !== 'all') {
            $filters = $moduleFilters[$module] ?? ['types' => [], 'actions' => []];
            $types = $filters['types'] ?? [];
            $actions = $filters['actions'] ?? [];
            $logsQuery->where(function ($builder) use ($types, $actions) {
                if (! empty($types)) {
                    $builder->whereIn('auditable_type', $types);
                }
                if (! empty($actions)) {
                    $builder->orWhereIn('action', $actions);
                }
            });
        }

        $logs = $logsQuery
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $entityLabels = [
            WeaponClientAssignment::class => 'AsignaciÃ³n de cliente',
            User::class => 'Usuario',
            Client::class => 'Cliente',
            Weapon::class => 'Arma',
            WeaponTransfer::class => 'Transferencia',
            Post::class => 'Puesto',
            Worker::class => 'Trabajador',
        ];

        $actionLabels = [
            'created' => 'Creado',
            'updated' => 'Actualizado',
            'deleted' => 'Eliminado',
            'client_created' => 'Cliente creado',
            'client_updated' => 'Cliente actualizado',
            'client_deleted' => 'Cliente eliminado',
            'client_assigned' => 'Cliente asignado al arma',
            'client_reassigned' => 'Cliente reasignado al arma',
            'client_assignment_closed' => 'AsignaciÃ³n de cliente cerrada',
            'client_assignment_retired' => 'AsignaciÃ³n de cliente retirada',
            'client_assignment_closed_for_transfer' => 'AsignaciÃ³n de cliente cerrada por transferencia',
            'client_responsible_transferred' => 'Responsable de cliente transferido',
            'post_created' => 'Puesto creado',
            'post_updated' => 'Puesto actualizado',
            'post_deleted' => 'Puesto eliminado',
            'post_archived' => 'Puesto archivado',
            'post_restored' => 'Puesto reactivado',
            'worker_created' => 'Trabajador creado',
            'worker_updated' => 'Trabajador actualizado',
            'worker_deleted' => 'Trabajador eliminado',
            'worker_archived' => 'Trabajador archivado',
            'worker_restored' => 'Trabajador reactivado',
            'user_created' => 'Usuario creado',
            'user_updated' => 'Usuario actualizado',
            'user_deleted' => 'Usuario eliminado',
            'user_status_updated' => 'Estado de usuario actualizado',
            'user_credentials_emailed' => 'Credenciales de acceso enviadas por correo',
            'user_logged_in' => 'Inicio de sesión',
            'user_logged_out' => 'Cierre de sesiÃ³n',
            'password_updated' => 'ContraseÃ±a actualizada',
            'password_reset_requested' => 'Solicitud de restablecimiento de contraseÃ±a',
            'password_reset_completed' => 'Restablecimiento de contraseÃ±a completado',
            'profile_updated' => 'Perfil actualizado',
            'profile_deleted' => 'Perfil eliminado',
            'weapon_created' => 'Arma creada',
            'weapon_updated' => 'Arma actualizada',
            'weapon_deleted' => 'Arma eliminada',
            'portfolio_updated' => 'Asignaciones actualizadas',
            'portfolio_transferred' => 'Asignaciones transferidas',
            'internal_assigned_post' => 'Arma asignada a puesto',
            'internal_assigned_worker' => 'Arma asignada a trabajador',
            'internal_assigned_worker_and_post' => 'Arma asignada a trabajador con ubicación de puesto',
            'internal_assignment_retired' => 'AsignaciÃ³n interna retirada',
            'internal_post_closed_for_transfer' => 'Puesto cerrado por transferencia',
            'internal_worker_closed_for_transfer' => 'Trabajador cerrado por transferencia',
            'internal_post_cleared_on_client_change' => 'Puesto limpiado por cambio de cliente',
            'internal_worker_cleared_on_client_change' => 'Trabajador limpiado por cambio de cliente',
            'transfer_requested' => 'Transferencia solicitada',
            'transfer_accepted' => 'Transferencia aceptada',
            'transfer_rejected' => 'Transferencia rechazada',
            'upload_photo' => 'Foto cargada',
            'update_photo' => 'Foto actualizada',
            'upload_document' => 'Documento cargado',
            'weapon_incident_created' => 'Novedad registrada',
            'weapon_incident_update_added' => 'Seguimiento de novedad agregado',
            'weapon_incident_closed' => 'Novedad cerrada',
            'weapon_incident_reopened' => 'Novedad reabierta',
        ];

        return view('reports.audit', compact('logs', 'days', 'actionLabels', 'entityLabels', 'modules', 'module'));
    }

    private function authorizeAdmin(): void
    {
        if (! request()->user()?->isAdmin() && ! request()->user()?->isAuditor()) {
            abort(403);
        }
    }
}
