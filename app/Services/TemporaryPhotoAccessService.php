<?php

namespace App\Services;

use App\Mail\RevistaTemporaryAccessMail;
use App\Models\TemporaryPhotoAccessGrant;
use App\Models\TemporaryPhotoAccessWeapon;
use App\Models\TemporaryPhotoUser;
use App\Models\User;
use App\Models\Weapon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class TemporaryPhotoAccessService
{
    public function __construct(
        private readonly RevistaArmasScopeService $scopeService,
    ) {
    }

    /**
     * @param  array<int, int>  $weaponIds
     * @return array{grant: TemporaryPhotoAccessGrant, plain_code: ?string, appended: bool}
     */
    public function createGrant(User $actor, TemporaryPhotoUser $temporaryUser, array $weaponIds): array
    {
        $this->ensureCanManageTemporaryUser($actor, $temporaryUser);

        $weaponIds = collect($weaponIds)->map(fn ($id) => (int) $id)->unique()->values();

        if ($weaponIds->isEmpty()) {
            throw new RuntimeException(__('Debe seleccionar al menos un arma.'));
        }

        $weapons = $this->scopeService->weaponsQueryForStaff($actor)
            ->whereIn('id', $weaponIds)
            ->get();

        if ($weapons->count() !== $weaponIds->count()) {
            throw new RuntimeException(__('Una o más armas no están disponibles para su cartera.'));
        }

        foreach ($weapons as $weapon) {
            if (! $this->scopeService->canStaffManageWeapon($actor, $weapon)) {
                throw new RuntimeException(__('No tiene permiso sobre todas las armas seleccionadas.'));
            }
        }

        if ($temporaryUser->is_shared) {
            $activeGrant = $this->activeGrantFor($temporaryUser);
            if ($activeGrant) {
                return $this->appendWeaponsToGrant($actor, $temporaryUser, $activeGrant, $weaponIds);
            }
        }

        $plainCode = $this->generatePlainCode();

        $grant = DB::transaction(function () use ($actor, $temporaryUser, $weaponIds, $plainCode) {
            $this->revokeActiveGrants($temporaryUser);

            $grant = TemporaryPhotoAccessGrant::create([
                'temporary_photo_user_id' => $temporaryUser->id,
                'created_by_user_id' => $actor->id,
                'access_code_hash' => Hash::make($plainCode),
                'expires_at' => now()->addHours(12),
            ]);

            foreach ($weaponIds as $weaponId) {
                TemporaryPhotoAccessWeapon::create([
                    'temporary_photo_access_grant_id' => $grant->id,
                    'weapon_id' => $weaponId,
                ]);
            }

            return $grant;
        });

        $this->sendAccessEmail($temporaryUser, $grant, $plainCode);

        return [
            'grant' => $grant->load('weapons.weapon'),
            'plain_code' => $plainCode,
            'appended' => false,
        ];
    }

    public function revokeGrant(User $actor, TemporaryPhotoAccessGrant $grant): void
    {
        $grant->loadMissing('temporaryPhotoUser');
        $this->ensureCanManageTemporaryUser($actor, $grant->temporaryPhotoUser);

        if ($grant->revoked_at !== null) {
            return;
        }

        $grant->update(['revoked_at' => now()]);
    }

    public function validateGuestLogin(string $email, string $code): ?TemporaryPhotoAccessGrant
    {
        $temporaryUser = TemporaryPhotoUser::query()
            ->active()
            ->where('email', mb_strtolower(trim($email)))
            ->first();

        if (! $temporaryUser) {
            return null;
        }

        $grant = TemporaryPhotoAccessGrant::query()
            ->where('temporary_photo_user_id', $temporaryUser->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $grant || ! Hash::check($code, $grant->access_code_hash)) {
            return null;
        }

        return $grant->load(['temporaryPhotoUser.ownerResponsible', 'weapons']);
    }

    public function grantWeaponIds(TemporaryPhotoAccessGrant $grant): Collection
    {
        return $grant->weapons()->pluck('weapon_id');
    }

    public function activeGrantFor(TemporaryPhotoUser $temporaryUser): ?TemporaryPhotoAccessGrant
    {
        return TemporaryPhotoAccessGrant::query()
            ->where('temporary_photo_user_id', $temporaryUser->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    public function latestGrantFor(TemporaryPhotoUser $temporaryUser): ?TemporaryPhotoAccessGrant
    {
        return TemporaryPhotoAccessGrant::query()
            ->where('temporary_photo_user_id', $temporaryUser->id)
            ->latest('id')
            ->first();
    }

    /**
     * @param  Collection<int, int>  $weaponIds
     * @return array{grant: TemporaryPhotoAccessGrant, plain_code: ?string, appended: bool}
     */
    private function appendWeaponsToGrant(
        User $actor,
        TemporaryPhotoUser $temporaryUser,
        TemporaryPhotoAccessGrant $grant,
        Collection $weaponIds,
    ): array {
        $existingWeaponIds = $grant->weapons()->pluck('weapon_id')->map(fn ($id) => (int) $id);

        DB::transaction(function () use ($grant, $weaponIds, $existingWeaponIds) {
            foreach ($weaponIds as $weaponId) {
                if ($existingWeaponIds->contains($weaponId)) {
                    continue;
                }

                TemporaryPhotoAccessWeapon::create([
                    'temporary_photo_access_grant_id' => $grant->id,
                    'weapon_id' => $weaponId,
                ]);
            }

            $grant->update([
                'expires_at' => now()->addHours(12),
            ]);
        });

        return [
            'grant' => $grant->fresh()->load('weapons.weapon'),
            'plain_code' => null,
            'appended' => true,
        ];
    }

    private function revokeActiveGrants(TemporaryPhotoUser $temporaryUser): void
    {
        TemporaryPhotoAccessGrant::query()
            ->where('temporary_photo_user_id', $temporaryUser->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->update(['revoked_at' => now()]);
    }

    private function generatePlainCode(): string
    {
        return Str::upper(Str::random(8));
    }

    private function sendAccessEmail(TemporaryPhotoUser $temporaryUser, TemporaryPhotoAccessGrant $grant, string $plainCode): void
    {
        try {
            Mail::to($temporaryUser->email)->send(new RevistaTemporaryAccessMail(
                recipientName: $temporaryUser->name,
                loginUrl: route('revista-armas.guest.login'),
                loginEmail: $temporaryUser->email,
                accessCode: $plainCode,
                expiresAt: $grant->expires_at,
                appName: (string) config('app.name'),
            ));
        } catch (\Throwable) {
            // El responsable puede copiar credenciales desde el modal de éxito.
        }
    }

    public function ensureCanManageTemporaryUser(User $actor, TemporaryPhotoUser $temporaryUser): void
    {
        if ($temporaryUser->canBeManagedBy($actor)) {
            return;
        }

        abort(403);
    }
}
