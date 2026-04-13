<?php

namespace App\Http\Controllers;

use App\Events\PortfolioAssignmentsChanged;
use App\Events\UserChanged;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use App\Models\WeaponClientAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResponsiblePortfolioController extends Controller
{
    public function index()
    {
        $user = request()->user();
        if (!$user || !$user->isAdmin()) {
            abort(403);
        }

        $activeWeaponCount = WeaponClientAssignment::query()
            ->selectRaw('count(*)')
            ->whereColumn('responsible_user_id', 'users.id')
            ->where('is_active', true);

        $responsibles = User::query()
            ->select('users.*')
            ->whereIn('role', ['RESPONSABLE', 'ADMIN'])
            ->withCount('clients')
            ->addSelect(['active_weapons_count' => $activeWeaponCount])
            ->orderBy('name')
            ->get();

        return view('portfolios.index', compact('responsibles'));
    }

    public function edit(User $user)
    {
        $authUser = request()->user();
        if (!$authUser || !$authUser->isAdmin()) {
            abort(403);
        }

        if (!$user->isResponsible() && !$user->isAdmin()) {
            abort(404);
        }

        $clients = Client::orderBy('name')->get();
        $assigned = $user->clients()->pluck('clients.id')->all();
        $blockedClientCounts = WeaponClientAssignment::query()
            ->where('responsible_user_id', $user->id)
            ->where('is_active', true)
            ->selectRaw('client_id, count(*) as total')
            ->groupBy('client_id')
            ->pluck('total', 'client_id')
            ->all();
        $responsibles = User::whereIn('role', ['RESPONSABLE', 'ADMIN'])->orderBy('name')->get();
        $assignedCount = count($assigned);
        $blockedCount = count(array_filter($blockedClientCounts, fn ($count) => $count > 0));
        $availableCount = max($clients->count() - $assignedCount, 0);

        return view('portfolios.edit', compact(
            'user',
            'clients',
            'assigned',
            'blockedClientCounts',
            'responsibles',
            'assignedCount',
            'blockedCount',
            'availableCount',
        ));
    }

    public function update(Request $request, User $user)
    {
        $authUser = $request->user();
        if (!$authUser || !$authUser->isAdmin()) {
            abort(403);
        }

        if (!$user->isResponsible() && !$user->isAdmin()) {
            abort(404);
        }

        $data = $request->validate([
            'clients' => ['array'],
            'clients.*' => ['exists:clients,id'],
        ]);

        $before = $user->clients()->pluck('clients.id')->all();
        $after = $data['clients'] ?? [];
        $removed = array_values(array_diff($before, $after));

        if (!empty($removed)) {
            $blocked = WeaponClientAssignment::query()
                ->where('responsible_user_id', $user->id)
                ->where('is_active', true)
                ->whereIn('client_id', $removed)
                ->selectRaw('client_id, count(*) as total')
                ->groupBy('client_id')
                ->pluck('total', 'client_id')
                ->all();

            if (!empty($blocked)) {
                $blockedNames = Client::whereIn('id', array_keys($blocked))
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all();

                $details = collect($blocked)
                    ->map(function ($count, $clientId) use ($blockedNames) {
                        $name = $blockedNames[$clientId] ?? ('Cliente ' . $clientId);
                        return $name . ' (' . $count . ')';
                    })
                    ->implode(', ');

                return back()
                    ->withInput()
                    ->withErrors([
                        'clients' => 'No se pueden quitar las asignaciones porque tiene armas asignadas: ' . $details . '. Debe reasignarlas o transferirlas primero.',
                    ]);
            }
        }

        $user->clients()->sync($after);

        AuditLog::create([
            'user_id' => $authUser->id,
            'action' => 'portfolio_updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'before' => ['client_ids' => $before],
            'after' => ['client_ids' => $after],
        ]);

        $afterIds = array_values($after);
        app()->terminating(function () use ($user, $afterIds): void {
            event(new UserChanged('updated', $user->id, ['client_ids' => $afterIds]));
            event(new PortfolioAssignmentsChanged('updated', $user->id, ['client_ids' => $afterIds]));
        });

        return redirect()->route('portfolios.index')->with('status', 'Asignaciones actualizadas.');
    }

    public function transfer(Request $request, User $user)
    {
        $authUser = $request->user();
        if (!$authUser || !$authUser->isAdmin()) {
            abort(403);
        }

        if (!$user->isResponsible() && !$user->isAdmin()) {
            abort(404);
        }

        $data = $request->validate([
            'to_user_id' => ['required', 'exists:users,id'],
            'clients' => ['required', 'array', 'min:1'],
            'clients.*' => ['exists:clients,id'],
        ]);

        $toUser = User::whereIn('role', ['RESPONSABLE', 'ADMIN'])->find($data['to_user_id']);
        if (!$toUser) {
            return back()->withErrors(['to_user_id' => 'El usuario destino no es válido.']);
        }

        if ($toUser->id === $user->id) {
            return back()->withErrors(['to_user_id' => 'El usuario destino debe ser diferente.']);
        }

        $clientIds = array_values(array_unique($data['clients']));
        $ownedClientIds = $user->clients()->whereIn('clients.id', $clientIds)->pluck('clients.id')->all();
        if (count($ownedClientIds) !== count($clientIds)) {
            return back()->withErrors(['clients' => 'Seleccione solo clientes que pertenezcan a las asignaciones actuales.']);
        }

        DB::transaction(function () use ($authUser, $user, $toUser, $clientIds) {
            foreach ($clientIds as $clientId) {
                $toUser->clients()->syncWithoutDetaching([$clientId]);
                $user->clients()->detach($clientId);
            }

            $assignments = WeaponClientAssignment::query()
                ->where('responsible_user_id', $user->id)
                ->where('is_active', true)
                ->whereIn('client_id', $clientIds)
                ->get();

            foreach ($assignments as $assignment) {
                $before = [
                    'responsible_user_id' => $assignment->responsible_user_id,
                    'client_id' => $assignment->client_id,
                ];

                $assignment->update([
                    'responsible_user_id' => $toUser->id,
                ]);

                AuditLog::create([
                    'user_id' => $authUser->id,
                    'action' => 'client_responsible_transferred',
                    'auditable_type' => WeaponClientAssignment::class,
                    'auditable_id' => $assignment->id,
                    'before' => $before,
                    'after' => [
                        'responsible_user_id' => $toUser->id,
                        'client_id' => $assignment->client_id,
                    ],
                ]);
            }

            AuditLog::create([
                'user_id' => $authUser->id,
                'action' => 'portfolio_transferred',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'before' => [
                    'from_user_id' => $user->id,
                    'to_user_id' => $toUser->id,
                    'client_ids' => $clientIds,
                ],
                'after' => [
                    'from_user_id' => $user->id,
                    'to_user_id' => $toUser->id,
                    'client_ids' => $clientIds,
                ],
            ]);
        });

        app()->terminating(function () use ($user, $toUser, $clientIds): void {
            event(new UserChanged('updated', $user->id, ['client_ids' => $clientIds]));
            event(new UserChanged('updated', $toUser->id, ['client_ids' => $clientIds]));
            event(new PortfolioAssignmentsChanged('transferred', $user->id, [
                'from_user_id' => $user->id,
                'to_user_id' => $toUser->id,
                'client_ids' => $clientIds,
            ]));
        });

        return redirect()->route('portfolios.index')->with('status', 'Asignaciones transferidas.');
    }
}
