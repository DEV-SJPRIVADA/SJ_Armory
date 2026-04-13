<?php

namespace App\Http\Controllers;

use App\Events\AssignmentChanged;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponPostAssignment;
use App\Models\WeaponWorkerAssignment;
use App\Services\WeaponAssignmentService;
use Illuminate\Http\Request;

class WeaponClientAssignmentController extends Controller
{
    public function store(Request $request, Weapon $weapon, WeaponAssignmentService $service)
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $this->authorize('assignToClient', $weapon);

        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'reason' => ['nullable', 'string'],
        ]);

        $activeAssignment = $weapon->activeClientAssignment;
        $clientId = (int) $data['client_id'];
        $responsibleUser = null;

        if ($user->isResponsible() && !$user->isAdmin()) {
            $inPortfolio = $user->clients()->whereKey($clientId)->exists();
            if (!$inPortfolio) {
                return back()->withErrors(['client_id' => 'El cliente no pertenece a sus asignaciones.'])->withInput();
            }
            $responsibleUser = $user;
        }

        if ($user->isAdmin()) {
            $responsibleUser = User::query()
                ->whereIn('role', ['RESPONSABLE', 'ADMIN'])
                ->whereHas('clients', fn ($query) => $query->whereKey($clientId))
                ->orderByRaw("CASE WHEN role = 'RESPONSABLE' THEN 0 WHEN role = 'ADMIN' THEN 1 ELSE 2 END")
                ->orderBy('name')
                ->first();
        }

        if (!$responsibleUser) {
            return back()->withErrors([
                'client_id' => 'Primero debe realizar la asignación del responsable.',
            ])->withInput();
        }

        $clientChanged = $activeAssignment?->client_id !== $clientId;

        if ($clientChanged) {
            $this->clearInternalAssignments($weapon, $user);
        }

        $service->assignClient(
            $weapon,
            $clientId,
            $responsibleUser,
            $user,
            now()->toDateString(),
            $data['reason'] ?? null
        );

        app()->terminating(function () use ($weapon, $clientId, $responsibleUser): void {
            event(new AssignmentChanged('assigned', $weapon->id, [
                'client_id' => $clientId,
                'responsible_user_id' => $responsibleUser->id,
            ]));
        });

        return redirect()->route('weapons.show', $weapon)->with('status', 'Destino operativo actualizado.');
    }

    private function clearInternalAssignments(Weapon $weapon, User $actor): void
    {
        $now = now()->toDateString();

        $activePost = $weapon->activePostAssignment()->first();
        if ($activePost) {
            $activePost->update([
                'end_at' => $now,
                'is_active' => null,
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'internal_post_cleared_on_client_change',
                'auditable_type' => WeaponPostAssignment::class,
                'auditable_id' => $activePost->id,
                'before' => [
                    'post_id' => $activePost->post_id,
                    'start_at' => $activePost->start_at?->toDateString(),
                    'end_at' => null,
                    'is_active' => true,
                ],
                'after' => [
                    'post_id' => $activePost->post_id,
                    'start_at' => $activePost->start_at?->toDateString(),
                    'end_at' => $activePost->end_at?->toDateString(),
                    'is_active' => null,
                ],
            ]);
        }

        $activeWorker = $weapon->activeWorkerAssignment()->first();
        if ($activeWorker) {
            $activeWorker->update([
                'end_at' => $now,
                'is_active' => null,
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'internal_worker_cleared_on_client_change',
                'auditable_type' => WeaponWorkerAssignment::class,
                'auditable_id' => $activeWorker->id,
                'before' => [
                    'worker_id' => $activeWorker->worker_id,
                    'start_at' => $activeWorker->start_at?->toDateString(),
                    'end_at' => null,
                    'is_active' => true,
                ],
                'after' => [
                    'worker_id' => $activeWorker->worker_id,
                    'start_at' => $activeWorker->start_at?->toDateString(),
                    'end_at' => $activeWorker->end_at?->toDateString(),
                    'is_active' => null,
                ],
            ]);
        }
    }
}
