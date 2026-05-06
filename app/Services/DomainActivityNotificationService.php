<?php

namespace App\Services;

use App\Events\AssignmentChanged;
use App\Events\ClientChanged;
use App\Events\DomainBroadcastEvent;
use App\Events\PortfolioAssignmentsChanged;
use App\Events\PostChanged;
use App\Events\TransferChanged;
use App\Events\UserInboxUpdated;
use App\Events\WeaponChanged;
use App\Events\WorkerChanged;
use App\Models\Client;
use App\Models\Post;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponClientAssignment;
use App\Models\WeaponTransfer;
use App\Models\Worker;
use App\Notifications\DomainActivityNotification;

class DomainActivityNotificationService
{
    public function notifyFromDomainEvent(DomainBroadcastEvent $event): void
    {
        $payload = $this->buildPayload($event);
        if ($payload === null) {
            return;
        }

        $clientIds = $this->resolveClientIds($event);
        $recipientIds = $this->resolveRecipientUserIds($clientIds);

        foreach ($recipientIds as $userId) {
            $user = User::query()->find($userId);
            if ($user === null || ! $user->is_active) {
                continue;
            }
            if ($user->isAuditor()) {
                continue;
            }

            $user->notify(new DomainActivityNotification($payload));
            broadcast(new UserInboxUpdated((int) $user->id, $user->unreadNotifications()->count()));
        }
    }

    /**
     * @return array{title: string, body: string, action_url?: string|null, module: string, actor_name?: string}|null
     */
    private function buildPayload(DomainBroadcastEvent $event): ?array
    {
        $action = $event->action;
        $id = $event->entityId;
        $actor = $this->actorName();

        if ($event instanceof PostChanged) {
            $actionUrl = route('posts.index');
            $name = $this->postTitle($id) ?: (string) $id;

            return match ($action) {
                'created' => [
                    'title' => __('Nuevo puesto'),
                    'body' => __(':actor creó el puesto «:name».', ['actor' => $actor, 'name' => $name]),
                    'action_url' => $actionUrl,
                    'module' => 'posts',
                    'actor_name' => $actor,
                ],
                'updated' => [
                    'title' => __('Puesto actualizado'),
                    'body' => __(':actor modificó el puesto «:name».', ['actor' => $actor, 'name' => $name]),
                    'action_url' => $actionUrl,
                    'module' => 'posts',
                    'actor_name' => $actor,
                ],
                'archived' => [
                    'title' => __('Puesto archivado'),
                    'body' => __(':actor archivó el puesto «:name».', ['actor' => $actor, 'name' => $name]),
                    'action_url' => $actionUrl,
                    'module' => 'posts',
                    'actor_name' => $actor,
                ],
                'restored' => [
                    'title' => __('Puesto reactivado'),
                    'body' => __(':actor reactivó el puesto «:name».', ['actor' => $actor, 'name' => $name]),
                    'action_url' => $actionUrl,
                    'module' => 'posts',
                    'actor_name' => $actor,
                ],
                default => null,
            };
        }

        if ($event instanceof WorkerChanged) {
            $actionUrl = route('workers.index');
            $name = $this->workerTitle($id) ?: (string) $id;

            return match ($action) {
                'created' => [
                    'title' => __('Nuevo trabajador de vigilancia'),
                    'body' => __(':actor registró al trabajador :name.', ['actor' => $actor, 'name' => $name]),
                    'action_url' => $actionUrl,
                    'module' => 'workers',
                    'actor_name' => $actor,
                ],
                'updated' => [
                    'title' => __('Trabajador actualizado'),
                    'body' => __(':actor modificó al trabajador :name.', ['actor' => $actor, 'name' => $name]),
                    'action_url' => $actionUrl,
                    'module' => 'workers',
                    'actor_name' => $actor,
                ],
                'archived' => [
                    'title' => __('Trabajador archivado'),
                    'body' => __(':actor archivó al trabajador :name.', ['actor' => $actor, 'name' => $name]),
                    'action_url' => $actionUrl,
                    'module' => 'workers',
                    'actor_name' => $actor,
                ],
                'restored' => [
                    'title' => __('Trabajador reactivado'),
                    'body' => __(':actor reactivó al trabajador :name.', ['actor' => $actor, 'name' => $name]),
                    'action_url' => $actionUrl,
                    'module' => 'workers',
                    'actor_name' => $actor,
                ],
                default => null,
            };
        }

        if ($event instanceof ClientChanged) {
            $actionUrl = route('clients.edit', ['client' => $id]);
            $name = $this->clientTitle($id);

            return match ($action) {
                'created' => [
                    'title' => __('Nuevo cliente'),
                    'body' => __(':actor creó el cliente :name.', ['actor' => $actor, 'name' => $name]),
                    'action_url' => $actionUrl,
                    'module' => 'clients',
                    'actor_name' => $actor,
                ],
                'updated' => [
                    'title' => __('Cliente actualizado'),
                    'body' => __(':actor modificó el cliente :name.', ['actor' => $actor, 'name' => $name]),
                    'action_url' => $actionUrl,
                    'module' => 'clients',
                    'actor_name' => $actor,
                ],
                'deleted' => [
                    'title' => __('Cliente eliminado'),
                    'body' => __(':actor eliminó el cliente :name.', ['actor' => $actor, 'name' => $name]),
                    'action_url' => route('clients.index'),
                    'module' => 'clients',
                    'actor_name' => $actor,
                ],
                default => null,
            };
        }

        if ($event instanceof WeaponChanged) {
            $actionUrl = route('weapons.show', ['weapon' => $id]);
            $code = $this->weaponDisplay($id);

            return match ($action) {
                'created' => [
                    'title' => __('Nueva arma'),
                    'body' => __(':actor registró el arma :code.', ['actor' => $actor, 'code' => $code]),
                    'action_url' => $actionUrl,
                    'module' => 'weapons',
                    'actor_name' => $actor,
                ],
                'updated' => [
                    'title' => __('Arma actualizada'),
                    'body' => __(':actor modificó el arma :code.', ['actor' => $actor, 'code' => $code]),
                    'action_url' => $actionUrl,
                    'module' => 'weapons',
                    'actor_name' => $actor,
                ],
                default => null,
            };
        }

        if ($event instanceof AssignmentChanged) {
            $actionUrl = route('weapons.show', ['weapon' => $id]);
            $code = $this->weaponDisplay($id);
            $related = $event->relatedIds;
            $isInternalPayload = array_key_exists('post_id', $related) || array_key_exists('worker_id', $related);

            if ($action === 'assigned' && $isInternalPayload) {
                $segments = [];
                if (! empty($related['post_id'])) {
                    $pn = $this->postTitle($related['post_id']);
                    if ($pn !== '') {
                        $segments[] = __('puesto :name', ['name' => $pn]);
                    }
                }
                if (! empty($related['worker_id'])) {
                    $wn = $this->workerTitle($related['worker_id']);
                    if ($wn !== '') {
                        $segments[] = __('trabajador :name', ['name' => $wn]);
                    }
                }
                $detail = $segments === []
                    ? __('sin puesto ni trabajador')
                    : implode(', ', $segments);

                return [
                    'title' => __('Asignación interna'),
                    'body' => __(':actor actualizó la asignación interna del arma :code (:detail).', [
                        'actor' => $actor,
                        'code' => $code,
                        'detail' => $detail,
                    ]),
                    'action_url' => $actionUrl,
                    'module' => 'assignments',
                    'actor_name' => $actor,
                ];
            }

            if ($action === 'assigned' && ! $isInternalPayload) {
                $cname = $this->clientTitle($related['client_id'] ?? null);

                return [
                    'title' => __('Destino operativo'),
                    'body' => __(':actor asignó o actualizó el destino operativo del arma :code (cliente :client).', [
                        'actor' => $actor,
                        'code' => $code,
                        'client' => $cname,
                    ]),
                    'action_url' => $actionUrl,
                    'module' => 'assignments',
                    'actor_name' => $actor,
                ];
            }

            if ($action === 'unassigned') {
                return [
                    'title' => __('Asignación retirada'),
                    'body' => __(':actor retiró o liberó la asignación interna del arma :code.', ['actor' => $actor, 'code' => $code]),
                    'action_url' => $actionUrl,
                    'module' => 'assignments',
                    'actor_name' => $actor,
                ];
            }

            if ($action === 'updated') {
                return [
                    'title' => __('Asignación por transferencia'),
                    'body' => __(':actor aceptó una transferencia y el arma :code quedó con destino actualizado.', ['actor' => $actor, 'code' => $code]),
                    'action_url' => $actionUrl,
                    'module' => 'assignments',
                    'actor_name' => $actor,
                ];
            }

            return null;
        }

        if ($event instanceof TransferChanged) {
            $actionUrl = route('transfers.index');
            $transfer = WeaponTransfer::query()->with('weapon')->find($event->entityId);
            $code = $transfer?->weapon ? $this->weaponDisplay($transfer->weapon->id) : __('arma');

            return match ($action) {
                'requested' => [
                    'title' => __('Transferencia solicitada'),
                    'body' => __(':actor solicitó una transferencia del arma :code.', ['actor' => $actor, 'code' => $code]),
                    'action_url' => $actionUrl,
                    'module' => 'transfers',
                    'actor_name' => $actor,
                ],
                'accepted' => [
                    'title' => __('Transferencia aceptada'),
                    'body' => __(':actor aceptó una transferencia del arma :code.', ['actor' => $actor, 'code' => $code]),
                    'action_url' => $actionUrl,
                    'module' => 'transfers',
                    'actor_name' => $actor,
                ],
                'rejected' => [
                    'title' => __('Transferencia rechazada'),
                    'body' => __(':actor rechazó una transferencia del arma :code.', ['actor' => $actor, 'code' => $code]),
                    'action_url' => $actionUrl,
                    'module' => 'transfers',
                    'actor_name' => $actor,
                ],
                default => null,
            };
        }

        if ($event instanceof PortfolioAssignmentsChanged) {
            $actionUrl = route('portfolios.index');
            $related = $event->relatedIds;
            $target = User::query()->whereKey($id)->value('name') ?? __('responsable');
            $toName = isset($related['to_user_id']) ? (User::query()->whereKey((int) $related['to_user_id'])->value('name') ?? __('responsable')) : '';

            return match ($action) {
                'updated' => [
                    'title' => __('Cartera de clientes'),
                    'body' => __(':actor actualizó la cartera de clientes asignada a :target.', ['actor' => $actor, 'target' => $target]),
                    'action_url' => $actionUrl,
                    'module' => 'portfolios',
                    'actor_name' => $actor,
                ],
                'transferred' => [
                    'title' => __('Cartera transferida'),
                    'body' => $toName !== ''
                        ? __(':actor transfirió clientes de la cartera de :from hacia :to.', ['actor' => $actor, 'from' => $target, 'to' => $toName])
                        : __(':actor transfirió clientes entre responsables (:from).', ['actor' => $actor, 'from' => $target]),
                    'action_url' => $actionUrl,
                    'module' => 'portfolios',
                    'actor_name' => $actor,
                ],
                default => null,
            };
        }

        return null;
    }

    private function actorName(): string
    {
        return auth()->user()?->name ?? __('Sistema');
    }

    private function weaponDisplay(int|string $weaponId): string
    {
        $weapon = Weapon::query()->find($weaponId);
        if ($weapon === null) {
            return (string) $weaponId;
        }
        if ($weapon->internal_code) {
            return (string) $weapon->internal_code;
        }

        return (string) ($weapon->serial_number ?: $weapon->id);
    }

    private function clientTitle(int|string|null $clientId): string
    {
        if ($clientId === null || $clientId === '') {
            return __('cliente');
        }
        $name = Client::query()->whereKey((int) $clientId)->value('name');

        return $name ?: __('cliente');
    }

    private function postTitle(int|string|null $postId): string
    {
        if ($postId === null || $postId === '') {
            return '';
        }
        $name = Post::query()->whereKey((int) $postId)->value('name');

        return $name ?: '';
    }

    private function workerTitle(int|string|null $workerId): string
    {
        if ($workerId === null || $workerId === '') {
            return '';
        }
        $name = Worker::query()->whereKey((int) $workerId)->value('name');

        return $name ?: '';
    }

    /**
     * @return list<int>
     */
    private function resolveClientIds(DomainBroadcastEvent $event): array
    {
        $related = $event->relatedIds;

        if ($event instanceof PostChanged || $event instanceof WorkerChanged) {
            $cid = isset($related['client_id']) ? (int) $related['client_id'] : null;

            return $cid ? [$cid] : [];
        }

        if ($event instanceof ClientChanged) {
            return [(int) $event->entityId];
        }

        if ($event instanceof AssignmentChanged) {
            if (! empty($related['client_id'])) {
                return [(int) $related['client_id']];
            }
            $weapon = Weapon::query()->with('activeClientAssignment')->find($event->entityId);
            $cid = $weapon?->activeClientAssignment?->client_id;

            return $cid ? [(int) $cid] : [];
        }

        if ($event instanceof WeaponChanged) {
            $weapon = Weapon::query()->with('activeClientAssignment')->find($event->entityId);
            $cid = $weapon?->activeClientAssignment?->client_id;

            return $cid ? [(int) $cid] : [];
        }

        if ($event instanceof TransferChanged) {
            $transfer = WeaponTransfer::query()->find($event->entityId);
            if ($transfer === null) {
                return [];
            }
            $ids = array_filter([
                $transfer->from_client_id ? (int) $transfer->from_client_id : null,
                $transfer->new_client_id ? (int) $transfer->new_client_id : null,
            ]);

            return array_values(array_unique($ids));
        }

        if ($event instanceof PortfolioAssignmentsChanged) {
            $raw = $related['client_ids'] ?? [];
            if (! is_array($raw)) {
                return [];
            }

            return array_values(array_unique(array_map(static fn ($v): int => (int) $v, $raw)));
        }

        return [];
    }

    /**
     * @param  list<int>  $clientIds
     * @return list<int>
     */
    private function resolveRecipientUserIds(array $clientIds): array
    {
        $actorId = auth()->id();

        $adminIds = User::query()
            ->where('role', 'ADMIN')
            ->where('is_active', true)
            ->pluck('id');

        $responsibleIds = collect();
        if ($clientIds !== []) {
            $fromPortfolio = User::query()
                ->where('role', 'RESPONSABLE')
                ->where('is_active', true)
                ->whereHas('clients', function ($q) use ($clientIds): void {
                    $q->whereIn('clients.id', $clientIds);
                })
                ->pluck('id');

            $fromAssignments = WeaponClientAssignment::query()
                ->where('is_active', true)
                ->whereIn('client_id', $clientIds)
                ->whereNotNull('responsible_user_id')
                ->distinct()
                ->pluck('responsible_user_id');

            $validResponsibleIds = User::query()
                ->where('is_active', true)
                ->whereIn('role', ['RESPONSABLE', 'ADMIN'])
                ->whereIn('id', $fromAssignments->all())
                ->pluck('id');

            $responsibleIds = $fromPortfolio->merge($validResponsibleIds)->unique()->values();
        }

        $merged = $adminIds->merge($responsibleIds)->unique()->values();

        if ($actorId !== null) {
            $merged = $merged->reject(fn (int $userId): bool => $userId === (int) $actorId)->values();
        }

        return $merged->all();
    }
}
