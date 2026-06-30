<?php

namespace App\Policies;

use App\Models\TemporaryPhotoUser;
use App\Models\User;

class TemporaryPhotoUserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isResponsibleLevelOne();
    }

    public function view(User $user, TemporaryPhotoUser $temporaryPhotoUser): bool
    {
        return $temporaryPhotoUser->canBeManagedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isResponsibleLevelOne();
    }

    public function update(User $user, TemporaryPhotoUser $temporaryPhotoUser): bool
    {
        return $temporaryPhotoUser->canBeEditedBy($user);
    }

    public function delete(User $user, TemporaryPhotoUser $temporaryPhotoUser): bool
    {
        return $temporaryPhotoUser->canBeEditedBy($user);
    }
}
