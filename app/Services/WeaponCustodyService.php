<?php

namespace App\Services;

use App\Events\AssignmentChanged;
use App\Models\AuditLog;
use App\Models\Post;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponPostAssignment;
use App\Models\WeaponWorkerAssignment;
use App\Support\MapCoordinates;
use App\Support\PostCustodyRole;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WeaponCustodyService
{
    public function __construct(
        private readonly ResponsibleCustodyPostService $custodyPosts,
        private readonly WeaponHistoryService $weaponHistory,
    ) {}

    public function moveToArmerillo(Weapon $weapon, User $actor, ?string $reason = null): void
    {
        $responsible = $this->custodyPosts->resolveResponsibleForWeapon($weapon);
        $this->authorizeActor($actor, $responsible);
        $client = $this->custodyPosts->resolveClientForWeapon($weapon);
        $post = $this->custodyPosts->armerilloPost($responsible, $client);

        $this->assignWeaponToPostOnly(
            $weapon,
            $actor,
            $post,
            $reason ?: __('Arma enviada al armerillo del responsable.'),
        );
    }

    public function moveToParaMantenimiento(Weapon $weapon, User $actor, ?string $reason = null): void
    {
        $responsible = $this->custodyPosts->resolveResponsibleForWeapon($weapon);
        $this->authorizeActor($actor, $responsible);
        $client = $this->custodyPosts->resolveClientForWeapon($weapon);
        $post = $this->custodyPosts->armerilloParaMantenimientoPost($responsible, $client);

        $this->assignWeaponToPostOnly(
            $weapon,
            $actor,
            $post,
            $reason ?: __('Arma en armerillo pendiente de mantenimiento.'),
        );
    }

    public function moveToArmero(Weapon $weapon, User $actor, Post $armeroPost, ?string $reason = null): void
    {
        $responsible = $this->custodyPosts->resolveResponsibleForWeapon($weapon);
        $this->authorizeActor($actor, $responsible);
        $client = $this->custodyPosts->resolveClientForWeapon($weapon);

        if ((int) $armeroPost->owner_responsible_user_id !== (int) $responsible->id) {
            throw new RuntimeException(__('El armero seleccionado no pertenece a este responsable.'));
        }

        if ($armeroPost->custody_role !== PostCustodyRole::ARMERO) {
            throw new RuntimeException(__('El puesto seleccionado no es un armero válido.'));
        }

        if ((int) $armeroPost->client_id !== (int) $client->id) {
            throw new RuntimeException(__('El armero debe pertenecer al mismo cliente operativo del arma.'));
        }

        if ($armeroPost->isArchived()) {
            throw new RuntimeException(__('El puesto de armero está archivado.'));
        }

        if (! MapCoordinates::isFilled($armeroPost->latitude, $armeroPost->longitude)) {
            throw new RuntimeException(__('El armero debe tener ubicación en el mapa antes de asignar el arma.'));
        }

        $this->assignWeaponToPostOnly(
            $weapon,
            $actor,
            $armeroPost,
            $reason ?: __('Arma enviada a mantenimiento en :post.', ['post' => $armeroPost->name]),
        );
    }

    private function assignWeaponToPostOnly(Weapon $weapon, User $actor, Post $post, string $reason): void
    {
        if ($msg = $weapon->pendingTransferBlockMessage($actor)) {
            throw new RuntimeException($msg);
        }

        $activeClientAssignment = $weapon->activeClientAssignment()->first();
        if (! $activeClientAssignment) {
            throw new RuntimeException(__('El arma no tiene destino operativo activo.'));
        }

        if ((int) $post->client_id !== (int) $activeClientAssignment->client_id) {
            throw new RuntimeException(__('El puesto de custodia debe pertenecer al cliente operativo del arma.'));
        }

        if (! MapCoordinates::isFilled($post->latitude, $post->longitude)) {
            throw new RuntimeException(__('El puesto debe tener ubicación definida. Edite el puesto en el mapa.'));
        }

        $hadActiveInternal = $weapon->activePostAssignment()->exists() || $weapon->activeWorkerAssignment()->exists();
        $startAt = now()->toDateString();

        DB::transaction(function () use ($weapon, $actor, $post, $reason, $startAt, $hadActiveInternal) {
            $before = $this->currentInternalState($weapon);
            $this->closeActiveAssignments($weapon);

            $assignment = WeaponPostAssignment::create([
                'weapon_id' => $weapon->id,
                'post_id' => $post->id,
                'assigned_by' => $actor->id,
                'start_at' => $startAt,
                'is_active' => true,
                'reason' => $reason,
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'internal_assigned_post',
                'auditable_type' => Weapon::class,
                'auditable_id' => $weapon->id,
                'before' => $before,
                'after' => [
                    'post_id' => $post->id,
                    'assignment_id' => $assignment->id,
                    'custody_role' => $post->custody_role,
                    'start_at' => $startAt,
                ],
            ]);

            $this->weaponHistory->recordInternalAssignment(
                $weapon,
                $actor,
                $post,
                null,
                $reason,
                null,
                null,
                $hadActiveInternal,
            );
        });

        app()->terminating(function () use ($weapon, $activeClientAssignment, $post): void {
            event(new AssignmentChanged('assigned', $weapon->id, [
                'client_id' => $activeClientAssignment->client_id,
                'post_id' => $post->id,
                'worker_id' => null,
                'custody_role' => $post->custody_role,
            ]));
        });
    }

    private function authorizeActor(User $actor, User $responsible): void
    {
        if ($actor->isAdmin()) {
            return;
        }

        if ($actor->isResponsibleLevelOne() && (int) $actor->id === (int) $responsible->id) {
            return;
        }

        throw new RuntimeException(__('No tiene permiso para mover armas de este responsable.'));
    }

    private function closeActiveAssignments(Weapon $weapon): void
    {
        $weapon->activePostAssignment()?->update([
            'is_active' => false,
            'end_at' => now()->toDateString(),
        ]);
        $weapon->activeWorkerAssignment()?->update([
            'is_active' => false,
            'end_at' => now()->toDateString(),
        ]);
    }

    /**
     * @return array{post_id: int|null, worker_id: int|null}
     */
    private function currentInternalState(Weapon $weapon): array
    {
        return [
            'post_id' => $weapon->activePostAssignment?->post_id,
            'worker_id' => $weapon->activeWorkerAssignment?->worker_id,
        ];
    }
}
