<?php

namespace App\Http\Controllers;

use App\Events\AssignmentChanged;
use App\Events\TransferChanged;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Post;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponTransfer;
use App\Models\Worker;
use App\Services\WeaponAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeaponTransferController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }
        $canManageTransfers = $user->isAdmin() || $user->isResponsibleLevelOne();

        $search = trim((string) $request->input('q', ''));
        $status = $request->input('status', WeaponTransfer::STATUS_PENDING);
        if (!in_array($status, [
            WeaponTransfer::STATUS_PENDING,
            WeaponTransfer::STATUS_ACCEPTED,
            WeaponTransfer::STATUS_REJECTED,
            WeaponTransfer::STATUS_CANCELLED,
        ], true)) {
            $status = WeaponTransfer::STATUS_PENDING;
        }

        $incomingQuery = WeaponTransfer::with(['weapon', 'fromUser', 'fromClient', 'newClient'])
            ->where('status', $status);

        if ($user->isResponsible()) {
            $incomingQuery->where('to_user_id', $user->id);
        }

        if ($search !== '') {
            $incomingQuery->where(function ($builder) use ($search) {
                $builder->whereHas('weapon', function ($weaponQuery) use ($search) {
                    $weaponQuery->where('internal_code', 'like', '%' . $search . '%')
                        ->orWhere('serial_number', 'like', '%' . $search . '%');
                })->orWhereHas('fromClient', function ($clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', '%' . $search . '%');
                })->orWhereHas('newClient', function ($clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', '%' . $search . '%');
                });
            });
        }

        $incoming = $incomingQuery->orderByDesc('requested_at')->get();

        $outgoingQuery = WeaponTransfer::with(['weapon', 'toUser', 'fromClient', 'newClient'])
            ->where('status', $status);

        if (!$user->isAdmin() && !$user->isAuditor()) {
            $outgoingQuery->where('requested_by', $user->id);
        }

        if ($search !== '') {
            $outgoingQuery->where(function ($builder) use ($search) {
                $builder->whereHas('weapon', function ($weaponQuery) use ($search) {
                    $weaponQuery->where('internal_code', 'like', '%' . $search . '%')
                        ->orWhere('serial_number', 'like', '%' . $search . '%');
                })->orWhereHas('fromClient', function ($clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', '%' . $search . '%');
                })->orWhereHas('newClient', function ($clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', '%' . $search . '%');
                });
            });
        }

        $outgoing = $outgoingQuery->orderByDesc('requested_at')->get();

        $weaponsQuery = Weapon::query()->with([
            'activeClientAssignment.client',
            'activeClientAssignment.responsible',
        ])->orderByDesc('id');

        if ($user->isResponsible() && !$user->isAdmin()) {
            $weaponsQuery->whereHas('clientAssignments', function ($assignmentQuery) use ($user) {
                $assignmentQuery->where('responsible_user_id', $user->id)->where('is_active', true);
            });
        }

        $weapons = $canManageTransfers ? $weaponsQuery->get() : collect();
        $transferRecipients = User::whereIn('role', ['RESPONSABLE', 'ADMIN'])
            ->with(['clients:id,name'])
            ->orderBy('name')
            ->get();
        $transferClients = Client::orderBy('name')->get(['id', 'name']);
        $transferPosts = Post::orderBy('name')->get(['id', 'client_id', 'name']);
        $acceptClients = $user->isAdmin()
            ? Client::orderBy('name')->get()
            : $user->clients()->orderBy('name')->get();

        $acceptPosts = Post::whereIn('client_id', $acceptClients->pluck('id'))->orderBy('name')->get();

        $acceptWorkersQuery = Worker::whereIn('client_id', $acceptClients->pluck('id'))->orderBy('name');
        if (!$user->isAdmin()) {
            $acceptWorkersQuery->where('responsible_user_id', $user->id);
        }
        $acceptWorkers = $acceptWorkersQuery->get();

        return view('transfers.index', compact(
            'incoming',
            'outgoing',
            'search',
            'status',
            'weapons',
            'transferRecipients',
            'transferClients',
            'transferPosts',
            'acceptClients',
            'acceptPosts',
            'acceptWorkers',
            'canManageTransfers'
        ));
    }

    public function bulkStore(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }
        if (!$user->isAdmin() && !$user->isResponsibleLevelOne()) {
            abort(403);
        }

        $data = $request->validate([
            'weapon_ids' => ['required', 'array', 'min:1'],
            'weapon_ids.*' => ['integer', 'exists:weapons,id'],
            'to_user_id' => ['required', 'exists:users,id'],
            'client_id' => ['required', 'exists:clients,id'],
            'post_id' => ['nullable', 'exists:posts,id'],
            'note' => ['nullable', 'string'],
        ]);

        $toUser = User::whereIn('role', ['RESPONSABLE', 'ADMIN'])->find($data['to_user_id']);
        if (!$toUser) {
            return back()->withErrors([
                'to_user_id' => 'El destinatario no es válido.',
            ])->withInput();
        }

        $clientId = (int) $data['client_id'];
        $postId = isset($data['post_id']) && $data['post_id'] !== '' ? (int) $data['post_id'] : null;

        $inPortfolio = $toUser->clients()->whereKey($clientId)->exists();
        if (!$inPortfolio) {
            return back()->withErrors([
                'client_id' => 'El cliente no pertenece a las asignaciones del destinatario.',
            ])->withInput();
        }

        if ($postId) {
            $post = Post::findOrFail($postId);
            if ($post->client_id !== $clientId) {
                return back()->withErrors([
                    'post_id' => 'El puesto seleccionado no pertenece al cliente destino.',
                ])->withInput();
            }
        }

        $weaponsQuery = Weapon::query()
            ->with('activeClientAssignment')
            ->whereIn('id', $data['weapon_ids']);

        if ($user->isResponsible() && !$user->isAdmin()) {
            $weaponsQuery->whereHas('clientAssignments', function ($assignmentQuery) use ($user) {
                $assignmentQuery->where('responsible_user_id', $user->id)->where('is_active', true);
            });
        }

        $weapons = $weaponsQuery->get();

        if ($weapons->count() !== count($data['weapon_ids'])) {
            return back()->withErrors([
                'weapon_ids' => 'Algunas armas seleccionadas no son válidas para transferir.',
            ])->withInput();
        }

        foreach ($weapons as $weapon) {
            $activeAssignment = $weapon->activeClientAssignment;

            if ($activeAssignment) {
                $fromUserId = $activeAssignment->responsible_user_id;
                if (!$user->isAdmin() && $fromUserId !== $user->id) {
                    abort(403);
                }

                if ($toUser->id === $fromUserId) {
                    return back()->withErrors([
                        'to_user_id' => 'El destinatario debe ser diferente al responsable actual.',
                    ])->withInput();
                }

                continue;
            }

            if (!$user->isAdmin()) {
                abort(403, 'Solo el administrador puede transferir armas sin destino operativo.');
            }
        }

        $dispatchedTransfers = [];

        DB::transaction(function () use ($weapons, $user, $toUser, $data, $clientId, $postId, &$dispatchedTransfers) {
            foreach ($weapons as $weapon) {
                $activeAssignment = $weapon->activeClientAssignment;
                $this->closeInternalAssignments($weapon, $user);
                $this->retireClientAssignment($weapon, $user);

                $transfer = WeaponTransfer::create([
                    'weapon_id' => $weapon->id,
                    'from_user_id' => $activeAssignment?->responsible_user_id ?? $user->id,
                    'to_user_id' => $toUser->id,
                    'requested_by' => $user->id,
                    'from_client_id' => $activeAssignment?->client_id,
                    'new_client_id' => $clientId,
                    'status' => WeaponTransfer::STATUS_PENDING,
                    'requested_at' => now(),
                    'note' => $data['note'] ?? null,
                ]);

                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'transfer_requested',
                    'auditable_type' => WeaponTransfer::class,
                    'auditable_id' => $transfer->id,
                    'before' => null,
                    'after' => [
                        'weapon_id' => $weapon->id,
                        'from_user_id' => $activeAssignment?->responsible_user_id ?? $user->id,
                        'to_user_id' => $toUser->id,
                        'from_client_id' => $activeAssignment?->client_id,
                        'new_client_id' => $clientId,
                        'post_id' => $postId,
                    ],
                ]);

                $dispatchedTransfers[] = [
                    'transfer_id' => $transfer->id,
                    'weapon_id' => $weapon->id,
                ];
            }
        });

        foreach ($dispatchedTransfers as $row) {
            event(new TransferChanged('requested', $row['transfer_id'], ['weapon_id' => $row['weapon_id']]));
        }

        return redirect()->route('transfers.index')->with('status', 'Transferencias enviadas.');
    }

    public function accept(Request $request, WeaponTransfer $transfer, WeaponAssignmentService $service)
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }
        if (!$user->isAdmin() && !$user->isResponsibleLevelOne()) {
            abort(403);
        }

        if ($transfer->status !== WeaponTransfer::STATUS_PENDING) {
            abort(422, 'La transferencia ya fue resuelta.');
        }

        if (!$user->isAdmin() && $transfer->to_user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'post_id' => ['nullable', 'exists:posts,id'],
            'worker_id' => ['nullable', 'exists:workers,id'],
        ]);

        $postId = $data['post_id'] ?? null;
        $workerId = $data['worker_id'] ?? null;
        if ($postId && $workerId) {
            abort(422, 'Seleccione solo un puesto o un trabajador.');
        }

        $transfer->load(['weapon', 'toUser']);
        $weapon = $transfer->weapon;
        $clientId = (int) $data['client_id'];
        $inPortfolio = $transfer->toUser->clients()->whereKey($clientId)->exists();
        if (!$inPortfolio) {
            abort(422, 'El cliente no pertenece a las asignaciones del destinatario.');
        }

        if ($postId) {
            $post = Post::findOrFail($postId);
            if ($post->client_id !== $clientId) {
                abort(422, 'El puesto seleccionado no pertenece al cliente.');
            }
        }

        if ($workerId) {
            $worker = Worker::findOrFail($workerId);
            if ($worker->client_id !== $clientId) {
                abort(422, 'El trabajador seleccionado no pertenece al cliente.');
            }
            if (!$user->isAdmin() && $worker->responsible_user_id !== $user->id) {
                abort(403, 'Solo puede asignar trabajadores a su cargo.');
            }
        }

        DB::transaction(function () use ($transfer, $weapon, $service, $clientId, $user, $postId, $workerId) {
            $service->assignClient(
                $weapon,
                $clientId,
                $transfer->toUser,
                $user,
                now()->toDateString(),
                $transfer->note
            );

            $this->closeInternalAssignments($weapon, $user);
            $this->assignInternalDestination($weapon, $user, $clientId, $postId, $workerId);

            $transfer->update([
                'status' => WeaponTransfer::STATUS_ACCEPTED,
                'accepted_by' => $user->id,
                'answered_at' => now(),
                'new_client_id' => $clientId,
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'transfer_accepted',
                'auditable_type' => WeaponTransfer::class,
                'auditable_id' => $transfer->id,
                'before' => ['status' => WeaponTransfer::STATUS_PENDING],
                'after' => ['status' => WeaponTransfer::STATUS_ACCEPTED, 'client_id' => $clientId],
            ]);
        });

        event(new TransferChanged('accepted', $transfer->id, ['weapon_id' => $weapon->id]));
        event(new AssignmentChanged('updated', $weapon->id, ['transfer_id' => $transfer->id]));

        return redirect()->route('transfers.index')->with('status', 'Transferencia aceptada.');
    }

    public function reject(Request $request, WeaponTransfer $transfer)
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }
        if (!$user->isAdmin() && !$user->isResponsibleLevelOne()) {
            abort(403);
        }

        if ($transfer->status !== WeaponTransfer::STATUS_PENDING) {
            abort(422, 'La transferencia ya fue resuelta.');
        }

        if (!$user->isAdmin() && $transfer->to_user_id !== $user->id) {
            abort(403);
        }

        $transfer->update([
            'status' => WeaponTransfer::STATUS_REJECTED,
            'accepted_by' => $user->id,
            'answered_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'transfer_rejected',
            'auditable_type' => WeaponTransfer::class,
            'auditable_id' => $transfer->id,
            'before' => ['status' => WeaponTransfer::STATUS_PENDING],
            'after' => ['status' => WeaponTransfer::STATUS_REJECTED],
        ]);

        event(new TransferChanged('rejected', $transfer->id, ['weapon_id' => $transfer->weapon_id]));

        return redirect()->route('transfers.index')->with('status', 'Transferencia rechazada.');
    }

    private function retireClientAssignment(Weapon $weapon, User $actor): void
    {
        $active = $weapon->activeClientAssignment()->first();
        if (!$active) {
            return;
        }

        $active->update([
            'end_at' => now()->toDateString(),
            'is_active' => null,
        ]);

        AuditLog::create([
            'user_id' => $actor->id,
            'action' => 'client_assignment_closed_for_transfer',
            'auditable_type' => $active::class,
            'auditable_id' => $active->id,
            'before' => [
                'client_id' => $active->client_id,
                'start_at' => $active->start_at?->toDateString(),
                'end_at' => null,
                'is_active' => true,
            ],
            'after' => [
                'client_id' => $active->client_id,
                'start_at' => $active->start_at?->toDateString(),
                'end_at' => $active->end_at?->toDateString(),
                'is_active' => null,
            ],
        ]);
    }

    private function closeInternalAssignments(Weapon $weapon, User $actor): void
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
                'action' => 'internal_post_closed_for_transfer',
                'auditable_type' => $activePost::class,
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
                'action' => 'internal_worker_closed_for_transfer',
                'auditable_type' => $activeWorker::class,
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

    private function assignInternalDestination(Weapon $weapon, User $actor, int $clientId, ?int $postId, ?int $workerId): void
    {
        if (!$postId && !$workerId) {
            return;
        }

        if ($postId) {
            $post = Post::findOrFail($postId);
            if ($post->client_id !== $clientId) {
                abort(422, 'El puesto seleccionado no pertenece al cliente.');
            }

            $assignment = $weapon->postAssignments()->create([
                'post_id' => $post->id,
                'assigned_by' => $actor->id,
                'start_at' => now()->toDateString(),
                'is_active' => true,
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'internal_assigned_post',
                'auditable_type' => $assignment::class,
                'auditable_id' => $assignment->id,
                'before' => null,
                'after' => [
                    'post_id' => $post->id,
                    'assignment_id' => $assignment->id,
                ],
            ]);

            return;
        }

        $worker = Worker::findOrFail($workerId);
        if ($worker->client_id !== $clientId) {
            abort(422, 'El trabajador seleccionado no pertenece al cliente.');
        }

        $assignment = $weapon->workerAssignments()->create([
            'worker_id' => $worker->id,
            'assigned_by' => $actor->id,
            'start_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        AuditLog::create([
            'user_id' => $actor->id,
            'action' => 'internal_assigned_worker',
            'auditable_type' => $assignment::class,
            'auditable_id' => $assignment->id,
            'before' => null,
            'after' => [
                'worker_id' => $worker->id,
                'assignment_id' => $assignment->id,
            ],
        ]);
    }
}
