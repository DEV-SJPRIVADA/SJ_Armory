<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isResponsible() || $user->isAuditor();
    }

    public function view(User $user, Client $client): bool
    {
        if ($user->isAdmin() || $user->isAuditor()) {
            return true;
        }

        if ($user->isResponsible()) {
            return $user->clients()->whereKey($client->id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Client $client): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isResponsibleLevelOne()) {
            return $user->clients()->whereKey($client->id)->exists();
        }

        return false;
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->isAdmin();
    }
}

