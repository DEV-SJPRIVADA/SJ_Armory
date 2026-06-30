<?php

namespace App\Services;

use App\Models\User;
use App\Models\Weapon;
use Illuminate\Database\Eloquent\Builder;

class RevistaArmasScopeService
{
    public function weaponsQueryForStaff(User $user): Builder
    {
        $query = Weapon::query()
            ->with([
                'activeClientAssignment.client',
                'activeClientAssignment.responsible',
                'activePendingTransfer.fromClient',
                'activePendingTransfer.fromUser',
                'activePostAssignment.post',
                'activeWorkerAssignment.worker',
            ])
            ->orderBy('serial_number');

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isResponsibleLevelOne()) {
            return $query->whereHas('clientAssignments', function (Builder $assignmentQuery) use ($user) {
                $assignmentQuery
                    ->where('is_active', true)
                    ->where('responsible_user_id', $user->id);
            });
        }

        abort(403);
    }

    public function canStaffManageWeapon(User $user, Weapon $weapon): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isResponsibleLevelOne()) {
            return false;
        }

        return $weapon->activeClientAssignment?->responsible_user_id === $user->id;
    }

    public function temporaryUsersQueryForStaff(User $user): Builder
    {
        $query = \App\Models\TemporaryPhotoUser::query()
            ->with(['ownerResponsible:id,name,email', 'authorizedResponsibles:id,name'])
            ->orderBy('name');

        if ($user->isAdmin()) {
            return $query;
        }

        if (! $user->isResponsibleLevelOne()) {
            abort(403);
        }

        return $query->where(function (Builder $outer) use ($user) {
            $outer
                ->where('owner_responsible_user_id', $user->id)
                ->orWhere(function (Builder $inner) use ($user) {
                    $inner
                        ->where('is_shared', true)
                        ->whereHas('authorizedResponsibles', function (Builder $responsibleQuery) use ($user) {
                            $responsibleQuery->where('users.id', $user->id);
                        });
                });
        });
    }
}
