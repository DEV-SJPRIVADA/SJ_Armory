<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\TemporaryPhotoAccessGrant;
use App\Models\TemporaryPhotoUser;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponClientAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevistaSharedTemporaryUserTest extends TestCase
{
    use RefreshDatabase;

    private function createResponsibleWithWeapon(string $serial, string $suffix): array
    {
        $responsible = User::factory()->create([
            'role' => 'RESPONSABLE',
            'responsibility_level_id' => 1,
            'email' => "resp-{$suffix}@example.com",
        ]);

        $client = Client::create([
            'name' => "Cliente {$suffix}",
            'nit' => "900{$suffix}",
        ]);

        $weapon = Weapon::create([
            'internal_code' => "SJ-{$suffix}",
            'serial_number' => $serial,
            'weapon_type' => 'Pistola',
            'caliber' => '9MM',
            'brand' => 'Glock',
            'capacity' => '15',
            'ownership_type' => 'company_owned',
            'permit_type' => 'porte',
            'permit_number' => "P-{$suffix}",
            'permit_expires_at' => now()->addYear(),
        ]);

        WeaponClientAssignment::create([
            'weapon_id' => $weapon->id,
            'client_id' => $client->id,
            'responsible_user_id' => $responsible->id,
            'start_at' => now()->toDateString(),
            'is_active' => true,
            'assigned_by' => $responsible->id,
        ]);

        return [$responsible, $weapon];
    }

    public function test_admin_can_create_shared_temporary_user_for_multiple_responsibles(): void
    {
        [$owner, ] = $this->createResponsibleWithWeapon('OWN-001', '001');
        [$authorized, ] = $this->createResponsibleWithWeapon('AUTH-001', '002');
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)
            ->post(route('revista-armas.temporary-users.store'), [
                'owner_responsible_user_id' => $owner->id,
                'name' => 'Supervisor Zona',
                'email' => 'supervisor@example.com',
                'is_shared' => '1',
                'authorized_responsible_ids' => [$authorized->id],
            ])
            ->assertRedirect(route('revista-armas.temporary-users.index'));

        $temporaryUser = TemporaryPhotoUser::query()->where('email', 'supervisor@example.com')->first();
        $this->assertNotNull($temporaryUser);
        $this->assertTrue($temporaryUser->is_shared);
        $this->assertSame($owner->id, $temporaryUser->owner_responsible_user_id);
        $this->assertTrue($temporaryUser->authorizedResponsibles()->where('users.id', $authorized->id)->exists());
    }

    public function test_authorized_responsible_can_see_and_assign_shared_temporary_user(): void
    {
        [$owner, $ownerWeapon] = $this->createResponsibleWithWeapon('OWN-002', '003');
        [$authorized, $authorizedWeapon] = $this->createResponsibleWithWeapon('AUTH-002', '004');
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)
            ->post(route('revista-armas.temporary-users.store'), [
                'owner_responsible_user_id' => $owner->id,
                'name' => 'Supervisor Compartido',
                'email' => 'shared@example.com',
                'is_shared' => '1',
                'authorized_responsible_ids' => [$authorized->id],
            ]);

        $temporaryUser = TemporaryPhotoUser::query()->where('email', 'shared@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('revista-armas.access.store'), [
                'temporary_photo_user_id' => $temporaryUser->id,
                'weapon_ids' => [$ownerWeapon->id],
            ])
            ->assertRedirect(route('revista-armas.index'));

        $this->actingAs($authorized)
            ->get(route('revista-armas.temporary-users.index'))
            ->assertOk()
            ->assertSee('shared@example.com', false);

        $this->actingAs($authorized)
            ->post(route('revista-armas.access.store'), [
                'temporary_photo_user_id' => $temporaryUser->id,
                'weapon_ids' => [$authorizedWeapon->id],
            ])
            ->assertRedirect(route('revista-armas.index'));

        $grant = TemporaryPhotoAccessGrant::query()->where('temporary_photo_user_id', $temporaryUser->id)->first();
        $this->assertNotNull($grant);
        $this->assertSame(2, $grant->weapons()->count());
    }

    public function test_non_authorized_responsible_cannot_see_shared_temporary_user(): void
    {
        [$owner, ] = $this->createResponsibleWithWeapon('OWN-003', '005');
        [$authorized, ] = $this->createResponsibleWithWeapon('AUTH-003', '006');
        [$other, ] = $this->createResponsibleWithWeapon('OTH-003', '007');
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)
            ->post(route('revista-armas.temporary-users.store'), [
                'owner_responsible_user_id' => $owner->id,
                'name' => 'Supervisor Restringido',
                'email' => 'restricted@example.com',
                'is_shared' => '1',
                'authorized_responsible_ids' => [$authorized->id],
            ]);

        $temporaryUser = TemporaryPhotoUser::query()->where('email', 'restricted@example.com')->firstOrFail();

        $this->actingAs($other)
            ->get(route('revista-armas.temporary-users.index'))
            ->assertOk()
            ->assertDontSee('restricted@example.com');

        $this->actingAs($other)
            ->post(route('revista-armas.access.store'), [
                'temporary_photo_user_id' => $temporaryUser->id,
                'weapon_ids' => [1],
            ])
            ->assertForbidden();
    }

    public function test_disabling_shared_removes_access_for_authorized_responsibles(): void
    {
        [$owner, ] = $this->createResponsibleWithWeapon('OWN-004', '008');
        [$authorized, ] = $this->createResponsibleWithWeapon('AUTH-004', '009');
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)
            ->post(route('revista-armas.temporary-users.store'), [
                'owner_responsible_user_id' => $owner->id,
                'name' => 'Supervisor Toggle',
                'email' => 'toggle@example.com',
                'is_shared' => '1',
                'authorized_responsible_ids' => [$authorized->id],
            ]);

        $temporaryUser = TemporaryPhotoUser::query()->where('email', 'toggle@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('revista-armas.temporary-users.update', $temporaryUser), [
                'owner_responsible_user_id' => $owner->id,
                'name' => 'Supervisor Toggle',
                'email' => 'toggle@example.com',
                'is_shared' => '0',
            ])
            ->assertRedirect(route('revista-armas.temporary-users.index'));

        $temporaryUser->refresh();
        $this->assertFalse($temporaryUser->is_shared);
        $this->assertSame(0, $temporaryUser->authorizedResponsibles()->count());

        $this->actingAs($authorized)
            ->get(route('revista-armas.temporary-users.index'))
            ->assertOk()
            ->assertDontSee('toggle@example.com');

        $this->actingAs($owner)
            ->get(route('revista-armas.temporary-users.index'))
            ->assertOk()
            ->assertSee('toggle@example.com', false);
    }
}
