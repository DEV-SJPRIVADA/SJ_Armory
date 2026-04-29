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
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponClientAssignment;
use App\Models\WeaponTransfer;
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
     * @return array{title: string, body: string, action_url?: string|null, module: string}|null
     */
    private function buildPayload(DomainBroadcastEvent $event): ?array
    {
        $action = $event->action;
        $id = $event->entityId;

        if ($event instanceof PostChanged) {
            $actionUrl = route('posts.index');

            return match ($action) {
                'created' => [
                    'title' => __('Nuevo puesto'),
                    'body' => __('Se creó un puesto operativo.'),
                    'action_url' => $actionUrl,
                    'module' => 'posts',
                ],
                'updated' => [
                    'title' => __('Puesto actualizado'),
                    'body' => __('Se modificó un puesto operativo.'),
                    'action_url' => $actionUrl,
                    'module' => 'posts',
                ],
                'archived' => [
                    'title' => __('Puesto archivado'),
                    'body' => __('Se archivó un puesto operativo.'),
                    'action_url' => $actionUrl,
                    'module' => 'posts',
                ],
                'restored' => [
                    'title' => __('Puesto reactivado'),
                    'body' => __('Se reactivó un puesto operativo.'),
                    'action_url' => $actionUrl,
                    'module' => 'posts',
                ],
                default => null,
            };
        }

        if ($event instanceof WorkerChanged) {
            $actionUrl = route('workers.index');

            return match ($action) {
                'created' => [
                    'title' => __('Nuevo trabajador de vigilancia'),
                    'body' => __('Se registró un trabajador.'),
                    'action_url' => $actionUrl,
                    'module' => 'workers',
                ],
                'updated' => [
                    'title' => __('Trabajador actualizado'),
                    'body' => __('Se modificó un trabajador.'),
                    'action_url' => $actionUrl,
                    'module' => 'workers',
                ],
                'archived' => [
                    'title' => __('Trabajador archivado'),
                    'body' => __('Se archivó un trabajador.'),
                    'action_url' => $actionUrl,
                    'module' => 'workers',
                ],
                'restored' => [
                    'title' => __('Trabajador reactivado'),
                    'body' => __('Se reactivó un trabajador.'),
                    'action_url' => $actionUrl,
                    'module' => 'workers',
                ],
                default => null,
            };
        }

        if ($event instanceof ClientChanged) {
            $actionUrl = route('clients.edit', ['client' => $id]);

            return match ($action) {
                'created' => [
                    'title' => __('Nuevo cliente'),
                    'body' => __('Se creó un cliente.'),
                    'action_url' => $actionUrl,
                    'module' => 'clients',
                ],
                'updated' => [
                    'title' => __('Cliente actualizado'),
                    'body' => __('Se modificó un cliente.'),
                    'action_url' => $actionUrl,
                    'module' => 'clients',
                ],
                'deleted' => [
                    'title' => __('Cliente eliminado'),
                    'body' => __('Se eliminó un cliente.'),
                    'action_url' => route('clients.index'),
                    'module' => 'clients',
                ],
                default => null,
            };
        }

        if ($event instanceof WeaponChanged) {
            $actionUrl = route('weapons.show', ['weapon' => $id]);

            return match ($action) {
                'created' => [
                    'title' => __('Nuevo arma'),
                    'body' => __('Se registró un arma.'),
                    'action_url' => $actionUrl,
                    'module' => 'weapons',
                ],
                'updated' => [
                    'title' => __('Arma actualizada'),
                    'body' => __('Se modificó un arma.'),
                    'action_url' => $actionUrl,
                    'module' => 'weapons',
                ],
                default => null,
            };
        }

        if ($event instanceof AssignmentChanged) {
            $actionUrl = route('weapons.show', ['weapon' => $id]);

            return match ($action) {
                'assigned' => [
                    'title' => __('Asignación operativa'),
                    'body' => __('Cambió la asignación de cliente o ubicación de un arma.'),
                    'action_url' => $actionUrl,
                    'module' => 'assignments',
                ],
                'unassigned' => [
                    'title' => __('Desasignación'),
                    'body' => __('Se retiró o cambió la asignación de un arma.'),
                    'action_url' => $actionUrl,
                    'module' => 'assignments',
                ],
                'updated' => [
                    'title' => __('Asignación actualizada'),
                    'body' => __('Se actualizó la asignación de un arma.'),
                    'action_url' => $actionUrl,
                    'module' => 'assignments',
                ],
                default => null,
            };
        }

        if ($event instanceof TransferChanged) {
            $actionUrl = route('transfers.index');

            return match ($action) {
                'requested' => [
                    'title' => __('Transferencia solicitada'),
                    'body' => __('Hay una solicitud de transferencia de arma.'),
                    'action_url' => $actionUrl,
                    'module' => 'transfers',
                ],
                'accepted' => [
                    'title' => __('Transferencia aceptada'),
                    'body' => __('Se aceptó una transferencia de arma.'),
                    'action_url' => $actionUrl,
                    'module' => 'transfers',
                ],
                'rejected' => [
                    'title' => __('Transferencia rechazada'),
                    'body' => __('Se rechazó una transferencia de arma.'),
                    'action_url' => $actionUrl,
                    'module' => 'transfers',
                ],
                default => null,
            };
        }

        if ($event instanceof PortfolioAssignmentsChanged) {
            $actionUrl = route('portfolios.index');

            return match ($action) {
                'updated' => [
                    'title' => __('Cartera de clientes'),
                    'body' => __('Se actualizaron las asignaciones de clientes a un responsable.'),
                    'action_url' => $actionUrl,
                    'module' => 'portfolios',
                ],
                'transferred' => [
                    'title' => __('Cartera transferida'),
                    'body' => __('Se transfirieron clientes entre responsables.'),
                    'action_url' => $actionUrl,
                    'module' => 'portfolios',
                ],
                default => null,
            };
        }

        return null;
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
