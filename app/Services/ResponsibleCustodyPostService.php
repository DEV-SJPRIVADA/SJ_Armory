<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Post;
use App\Models\User;
use App\Models\Weapon;
use App\Support\PostCustodyRole;
use RuntimeException;

class ResponsibleCustodyPostService
{
    public function resolveResponsibleForWeapon(Weapon $weapon): User
    {
        $weapon->loadMissing('activeClientAssignment.responsible');
        $responsible = $weapon->activeClientAssignment?->responsible;

        if (! $responsible || ! $responsible->isResponsibleLevelOne()) {
            throw new RuntimeException(__('El arma no tiene un responsable activo asignado.'));
        }

        return $responsible;
    }

    public function resolveClientForWeapon(Weapon $weapon): Client
    {
        $weapon->loadMissing('activeClientAssignment.client');
        $client = $weapon->activeClientAssignment?->client;

        if (! $client) {
            throw new RuntimeException(__('El arma no tiene un cliente operativo activo.'));
        }

        return $client;
    }

    public function armerilloPost(User $responsible, Client $client): Post
    {
        return $this->ensureCustodyPost(
            $responsible,
            $client,
            PostCustodyRole::ARMERILLO,
            __('Armerillo — :name', ['name' => $responsible->name]),
        );
    }

    public function armerilloParaMantenimientoPost(User $responsible, Client $client): Post
    {
        return $this->ensureCustodyPost(
            $responsible,
            $client,
            PostCustodyRole::ARMERILLO_PARA_MANTENIMIENTO,
            __('Armerillo — Para mantenimiento — :name', ['name' => $responsible->name]),
        );
    }

    public function createArmeroPost(User $responsible, Client $client, string $name, ?string $address = null): Post
    {
        $label = trim($name);
        if ($label === '') {
            throw new RuntimeException(__('Indique el nombre del armero o taller.'));
        }

        if (! str_contains(mb_strtolower($label), 'armero') && ! str_contains(mb_strtolower($label), 'mantenimiento')) {
            $label = __('Armero — :name — En mantenimiento', ['name' => $label]);
        }

        return Post::query()->create([
            'client_id' => $client->id,
            'custody_role' => PostCustodyRole::ARMERO,
            'owner_responsible_user_id' => $responsible->id,
            'name' => $label,
            'address' => $address ?: $client->address,
            'city' => $client->city,
            'department' => $client->department,
            'latitude' => $client->latitude,
            'longitude' => $client->longitude,
            'notes' => __('Puesto de taller registrado por :name.', ['name' => $responsible->name]),
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Post>
     */
    public function armeroPostsForResponsible(User $responsible, ?int $clientId = null)
    {
        $query = Post::query()
            ->active()
            ->where('custody_role', PostCustodyRole::ARMERO)
            ->where('owner_responsible_user_id', $responsible->id)
            ->orderBy('name');

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        return $query->get();
    }

    private function ensureCustodyPost(User $responsible, Client $client, string $role, string $name): Post
    {
        $existing = Post::query()
            ->active()
            ->where('client_id', $client->id)
            ->where('owner_responsible_user_id', $responsible->id)
            ->where('custody_role', $role)
            ->first();

        if ($existing) {
            $this->syncCoordinatesFromClientIfEmpty($existing, $client);

            return $existing;
        }

        return Post::query()->create([
            'client_id' => $client->id,
            'custody_role' => $role,
            'owner_responsible_user_id' => $responsible->id,
            'name' => $name,
            'address' => $client->address,
            'city' => $client->city,
            'department' => $client->department,
            'latitude' => $client->latitude,
            'longitude' => $client->longitude,
            'notes' => __('Ubicación de custodia del responsable :name.', ['name' => $responsible->name]),
        ]);
    }

    private function syncCoordinatesFromClientIfEmpty(Post $post, Client $client): void
    {
        if ($post->latitude !== null && $post->longitude !== null) {
            return;
        }

        if ($client->latitude === null || $client->longitude === null) {
            return;
        }

        $post->update([
            'latitude' => $client->latitude,
            'longitude' => $client->longitude,
            'address' => $post->address ?: $client->address,
            'city' => $post->city ?: $client->city,
            'department' => $post->department ?: $client->department,
        ]);
    }
}
