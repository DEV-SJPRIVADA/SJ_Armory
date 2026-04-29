<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Worker;

class WorkerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isResponsible() || $user->isAuditor();
    }

    public function view(User $user, Worker $worker): bool
    {
        if ($user->isAdmin() || $user->isAuditor()) {
            return true;
        }

        if ($user->isResponsible()) {
            return $user->clients()->whereKey($worker->client_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isResponsibleLevelOne();
    }

    public function update(User $user, Worker $worker): bool
    {
        if ($worker->isArchived()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isResponsibleLevelOne() && $this->workerInPortfolio($user, $worker);
    }

    public function delete(User $user, Worker $worker): bool
    {
        if ($worker->isArchived()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isResponsibleLevelOne() && $this->workerInPortfolio($user, $worker);
    }

    public function restore(User $user, Worker $worker): bool
    {
        if (!$worker->isArchived()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isResponsibleLevelOne() && $this->workerInPortfolio($user, $worker);
    }

    private function workerInPortfolio(User $user, Worker $worker): bool
    {
        return $user->clients()->whereKey($worker->client_id)->exists();
    }
}
