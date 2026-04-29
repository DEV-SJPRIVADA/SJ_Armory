<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isResponsible() || $user->isAuditor();
    }

    public function view(User $user, Post $post): bool
    {
        if ($user->isAdmin() || $user->isAuditor()) {
            return true;
        }

        if ($user->isResponsible()) {
            return $user->clients()->whereKey($post->client_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isResponsibleLevelOne();
    }

    public function update(User $user, Post $post): bool
    {
        if ($post->isArchived()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isResponsibleLevelOne() && $this->postBelongsToResponsiblePortfolio($user, $post);
    }

    public function delete(User $user, Post $post): bool
    {
        if ($post->isArchived()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isResponsibleLevelOne() && $this->postBelongsToResponsiblePortfolio($user, $post);
    }

    public function restore(User $user, Post $post): bool
    {
        if (!$post->isArchived()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isResponsibleLevelOne() && $this->postBelongsToResponsiblePortfolio($user, $post);
    }

    private function postBelongsToResponsiblePortfolio(User $user, Post $post): bool
    {
        return $user->clients()->whereKey($post->client_id)->exists();
    }
}
