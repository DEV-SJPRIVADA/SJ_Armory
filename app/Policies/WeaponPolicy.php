<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Weapon;

class WeaponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isResponsible() || $user->isAuditor();
    }

    public function view(User $user, Weapon $weapon): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isResponsible()) {
            return $weapon->activeClientAssignment?->responsible_user_id === $user->id;
        }

        return $user->isAuditor();
    }

    public function assignToClient(User $user, Weapon $weapon): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isResponsibleLevelOne()) {
            return $weapon->activeClientAssignment?->responsible_user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Weapon $weapon): bool
    {
        return $user->isAdmin();
    }

    public function updatePhotos(User $user, Weapon $weapon): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isResponsibleLevelOne()) {
            return $weapon->activeClientAssignment?->responsible_user_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, Weapon $weapon): bool
    {
        return false;
    }
}

