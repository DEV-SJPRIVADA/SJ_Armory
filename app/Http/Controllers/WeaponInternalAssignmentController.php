<?php

namespace App\Http\Controllers;

use App\Events\AssignmentChanged;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Post;
use App\Models\Weapon;
use App\Models\WeaponPostAssignment;
use App\Models\WeaponWorkerAssignment;
use App\Models\Worker;
use App\Support\MapCoordinates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WeaponInternalAssignmentController extends Controller
{
    public function store(Request $request, Weapon $weapon)
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $data = $request->validate([
            'post_id' => [
                'nullable',
                Rule::exists('posts', 'id')->where(fn ($q) => $q->whereNull('archived_at')),
            ],
            'worker_id' => [
                'nullable',
                Rule::exists('workers', 'id')->where(fn ($q) => $q->whereNull('archived_at')),
            ],
            'start_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string'],
            'ammo_count' => ['nullable', 'integer', 'min:0'],
            'provider_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $postId = $data['post_id'] ?? null;
        $workerId = $data['worker_id'] ?? null;
        $replace = $request->boolean('replace');

        if (! $postId && ! $workerId) {
            abort(422, 'Seleccione un puesto o un trabajador.');
        }

        $activeClientAssignment = $weapon->activeClientAssignment()->first();
        if (!$activeClientAssignment) {
            abort(422, 'El arma no tiene destino operativo activo.');
        }

        $this->authorizeInternalAssignment($weapon, $user, $activeClientAssignment->responsible_user_id);

        $activePost = $weapon->activePostAssignment()->first();
        $activeWorker = $weapon->activeWorkerAssignment()->first();
        $isSamePost = $postId && $activePost && $activePost->post_id === (int) $postId;
        $isSameWorker = $workerId && $activeWorker && $activeWorker->worker_id === (int) $workerId;

        $unchanged = match (true) {
            $postId && $workerId => $isSamePost && $isSameWorker,
            (bool) $postId => $isSamePost && ! $activeWorker,
            (bool) $workerId => $isSameWorker && ! $activePost,
            default => false,
        };

        if (($activePost || $activeWorker) && !$replace && !$unchanged) {
            return back()
                ->withInput()
                ->with('replace_warning', true)
                ->with('replace_message', 'Ya existe una asignación interna activa. Confirma si deseas reemplazarla.');
        }

        $startAt = $data['start_at'] ?? now()->toDateString();
        $reason = $data['reason'] ?? null;
        $ammoCount = $data['ammo_count'] ?? null;
        $providerCount = $data['provider_count'] ?? null;

        if ($postId) {
            $post = Post::findOrFail($postId);
            if ($post->isArchived()) {
                abort(422, 'El puesto está archivado.');
            }
            $this->ensureClientMatches($user, $post->client_id, $activeClientAssignment->client_id, $post->name);
            if (! MapCoordinates::isFilled($post->latitude, $post->longitude)) {
                return back()
                    ->withInput()
                    ->with('internal_assignment_location_modal', [
                        'kind' => 'post',
                        'name' => $post->name,
                        'edit_url' => route('posts.edit', $post),
                    ]);
            }
        }

        if ($workerId) {
            $worker = Worker::findOrFail($workerId);
            if ($worker->isArchived()) {
                abort(422, 'El trabajador está archivado.');
            }
            $this->ensureClientMatches($user, $worker->client_id, $activeClientAssignment->client_id, $worker->name);
            if (! $user->isAdmin() && $worker->responsible_user_id !== $user->id) {
                abort(403, 'Solo puede asignar trabajadores a su cargo.');
            }
            if (! $postId) {
                $client = Client::query()->find($worker->client_id);
                if (! $client || ! MapCoordinates::isFilled($client->latitude, $client->longitude)) {
                    return back()
                        ->withInput()
                        ->with('internal_assignment_location_modal', [
                            'kind' => 'client',
                            'name' => $client?->name ?? __('Cliente'),
                            'edit_url' => $client ? route('clients.edit', $client) : route('clients.index'),
                        ]);
                }
            }
        }

        DB::transaction(function () use ($weapon, $user, $postId, $workerId, $startAt, $reason, $ammoCount, $providerCount, $activeClientAssignment) {
            $before = $this->currentInternalState($weapon);
            $this->closeActiveAssignments($weapon);

            $post = null;
            $postAssignment = null;
            if ($postId) {
                $post = Post::findOrFail($postId);
                if ($post->isArchived()) {
                    abort(422, 'El puesto está archivado.');
                }
                $this->ensureClientMatches($user, $post->client_id, $activeClientAssignment->client_id, $post->name);

                $postAssignment = WeaponPostAssignment::create([
                    'weapon_id' => $weapon->id,
                    'post_id' => $post->id,
                    'assigned_by' => $user->id,
                    'start_at' => $startAt,
                    'is_active' => true,
                    'reason' => $reason,
                    'ammo_count' => $ammoCount,
                    'provider_count' => $providerCount,
                ]);
            }

            $worker = null;
            $workerAssignment = null;
            if ($workerId) {
                $worker = Worker::findOrFail($workerId);
                if ($worker->isArchived()) {
                    abort(422, 'El trabajador está archivado.');
                }
                $this->ensureClientMatches($user, $worker->client_id, $activeClientAssignment->client_id, $worker->name);

                if (!$user->isAdmin() && $worker->responsible_user_id !== $user->id) {
                    abort(403, 'Solo puede asignar trabajadores a su cargo.');
                }

                $workerAssignment = WeaponWorkerAssignment::create([
                    'weapon_id' => $weapon->id,
                    'worker_id' => $worker->id,
                    'assigned_by' => $user->id,
                    'start_at' => $startAt,
                    'is_active' => true,
                    'reason' => $reason,
                    'ammo_count' => $ammoCount,
                    'provider_count' => $providerCount,
                ]);
            }

            if ($postId && $workerId) {
                $this->logInternalAssignment($user, $weapon, 'internal_assigned_worker_and_post', $before, [
                    'post_id' => $post->id,
                    'worker_id' => $worker->id,
                    'post_assignment_id' => $postAssignment->id,
                    'worker_assignment_id' => $workerAssignment->id,
                    'start_at' => $startAt,
                ]);
            } elseif ($postId) {
                $this->logInternalAssignment($user, $weapon, 'internal_assigned_post', $before, [
                    'post_id' => $post->id,
                    'assignment_id' => $postAssignment->id,
                    'start_at' => $startAt,
                ]);
            } else {
                $this->logInternalAssignment($user, $weapon, 'internal_assigned_worker', $before, [
                    'worker_id' => $worker->id,
                    'assignment_id' => $workerAssignment->id,
                    'start_at' => $startAt,
                ]);
            }
        });

        app()->terminating(function () use ($weapon, $activeClientAssignment, $postId, $workerId): void {
            event(new AssignmentChanged('assigned', $weapon->id, [
                'client_id' => $activeClientAssignment->client_id,
                'post_id' => $postId,
                'worker_id' => $workerId,
            ]));
        });

        return redirect()->route('weapons.show', $weapon)->with('status', 'Asignación interna actualizada.');
    }

    public function retire(Request $request, Weapon $weapon)
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $activeClientAssignment = $weapon->activeClientAssignment()->first();
        if ($activeClientAssignment) {
            $this->authorizeInternalAssignment($weapon, $user, $activeClientAssignment->responsible_user_id);
        } elseif (!$user->isAdmin()) {
            abort(403);
        }

        $before = $this->currentInternalState($weapon);
        $closed = $this->closeActiveAssignments($weapon);

        if (!$closed) {
            return redirect()->route('weapons.show', $weapon)->with('status', 'El arma no tiene asignación interna activa.');
        }

        $this->logInternalAssignment($user, $weapon, 'internal_assignment_retired', $before, [
            'post_id' => null,
            'worker_id' => null,
        ]);

        app()->terminating(function () use ($weapon, $activeClientAssignment): void {
            event(new AssignmentChanged('unassigned', $weapon->id, [
                'client_id' => $activeClientAssignment?->client_id,
            ]));
        });

        return redirect()->route('weapons.show', $weapon)->with('status', 'Asignación interna retirada.');
    }

    private function authorizeInternalAssignment(Weapon $weapon, $user, ?int $responsibleUserId): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if (!$user->isResponsibleLevelOne()) {
            abort(403);
        }

        if (!$responsibleUserId || $responsibleUserId !== $user->id) {
            abort(403);
        }
    }

    private function ensureClientMatches($user, int $targetClientId, int $weaponClientId, string $label): void
    {
        if ($targetClientId !== $weaponClientId) {
            abort(403, 'El destino interno debe pertenecer al cliente actual.');
        }

        if ($user->isAdmin()) {
            return;
        }

        $inPortfolio = $user->clients()->whereKey($targetClientId)->exists();
        if (!$inPortfolio) {
            abort(403, 'El cliente no pertenece a sus asignaciones.');
        }
    }

    private function closeActiveAssignments(Weapon $weapon): bool
    {
        $now = now()->toDateString();
        $closed = false;

        $activePost = $weapon->activePostAssignment()->first();
        if ($activePost) {
            $activePost->update([
                'end_at' => $now,
                'is_active' => null,
            ]);
            $closed = true;
        }

        $activeWorker = $weapon->activeWorkerAssignment()->first();
        if ($activeWorker) {
            $activeWorker->update([
                'end_at' => $now,
                'is_active' => null,
            ]);
            $closed = true;
        }

        return $closed;
    }

    private function currentInternalState(Weapon $weapon): array
    {
        $activePost = $weapon->activePostAssignment()->first();
        $activeWorker = $weapon->activeWorkerAssignment()->first();

        return [
            'post_id' => $activePost?->post_id,
            'post_assignment_id' => $activePost?->id,
            'worker_id' => $activeWorker?->worker_id,
            'worker_assignment_id' => $activeWorker?->id,
        ];
    }

    private function logInternalAssignment($user, Weapon $weapon, string $action, array $before, array $after): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'auditable_type' => Weapon::class,
            'auditable_id' => $weapon->id,
            'before' => $before,
            'after' => $after,
        ]);
    }
}
