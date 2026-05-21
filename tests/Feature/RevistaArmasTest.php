<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\File;
use App\Models\TemporaryPhotoAccessGrant;
use App\Models\TemporaryPhotoUser;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponClientAssignment;
use App\Models\WeaponHistory;
use App\Models\WeaponPhotoStaging;
use App\Support\RevistaWeaponPhotoSlots;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RevistaArmasTest extends TestCase
{
    use RefreshDatabase;

    private function createResponsibleWithWeapon(): array
    {
        $responsible = User::factory()->create([
            'role' => 'RESPONSABLE',
            'responsibility_level_id' => 1,
        ]);

        $client = Client::create([
            'name' => 'Cliente Prueba',
            'nit' => '900123456',
        ]);

        $weapon = Weapon::create([
            'internal_code' => 'SJ-REV-001',
            'serial_number' => 'REV-001',
            'weapon_type' => 'Pistola',
            'caliber' => '9MM',
            'brand' => 'Glock',
            'capacity' => '15',
            'ownership_type' => 'company_owned',
            'permit_type' => 'porte',
            'permit_number' => 'P-001',
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

    public function test_responsible_can_create_temporary_user_and_assign_access(): void
    {
        Storage::fake('public');

        [$responsible, $weapon] = $this->createResponsibleWithWeapon();

        $this->actingAs($responsible)
            ->post(route('revista-armas.temporary-users.store'), [
                'name' => 'Escolta Campo',
                'email' => 'escolta@example.com',
                'owner_responsible_user_id' => $responsible->id,
            ])
            ->assertRedirect(route('revista-armas.temporary-users.index'));

        $temporaryUser = TemporaryPhotoUser::query()->where('email', 'escolta@example.com')->first();
        $this->assertNotNull($temporaryUser);

        $this->actingAs($responsible)
            ->post(route('revista-armas.access.store'), [
                'temporary_photo_user_id' => $temporaryUser->id,
                'weapon_ids' => [$weapon->id],
            ])
            ->assertRedirect(route('revista-armas.index'));

        $grant = TemporaryPhotoAccessGrant::query()->latest('id')->first();
        $this->assertNotNull($grant);
        $this->assertTrue($grant->expires_at->isFuture());
    }

    public function test_guest_can_login_and_upload_staging_photo(): void
    {
        Storage::fake('public');

        [$responsible, $weapon] = $this->createResponsibleWithWeapon();

        $temporaryUser = TemporaryPhotoUser::create([
            'owner_responsible_user_id' => $responsible->id,
            'created_by_user_id' => $responsible->id,
            'name' => 'Temporal',
            'email' => 'temp@example.com',
            'is_active' => true,
        ]);

        $code = 'ABCD1234';
        $grant = TemporaryPhotoAccessGrant::create([
            'temporary_photo_user_id' => $temporaryUser->id,
            'created_by_user_id' => $responsible->id,
            'access_code_hash' => Hash::make($code),
            'expires_at' => now()->addHours(12),
        ]);
        $grant->weapons()->create(['weapon_id' => $weapon->id]);

        $this->post(route('revista-armas.guest.login.store'), [
            'email' => 'temp@example.com',
            'access_code' => $code,
        ])->assertRedirect(route('revista-armas.guest.weapons.index'));

        $description = RevistaWeaponPhotoSlots::keys()[0];

        $this->post(route('revista-armas.guest.weapons.photos.store', $weapon), [
            'photo' => UploadedFile::fake()->image('arma.jpg'),
            'description' => $description,
        ])->assertJson(['ok' => true]);

        $this->assertDatabaseHas('weapon_photo_staging', [
            'temporary_photo_user_id' => $temporaryUser->id,
            'weapon_id' => $weapon->id,
            'description' => $description,
        ]);
    }

    public function test_deactivating_temporary_user_preserves_staging(): void
    {
        [$responsible, $weapon] = $this->createResponsibleWithWeapon();

        $temporaryUser = TemporaryPhotoUser::create([
            'owner_responsible_user_id' => $responsible->id,
            'created_by_user_id' => $responsible->id,
            'name' => 'Temporal',
            'email' => 'temp2@example.com',
            'is_active' => true,
        ]);

        $file = File::create([
            'disk' => 'public',
            'path' => 'test/staging.jpg',
            'original_name' => 'staging.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 100,
        ]);

        WeaponPhotoStaging::create([
            'temporary_photo_user_id' => $temporaryUser->id,
            'weapon_id' => $weapon->id,
            'description' => 'serie',
            'file_id' => $file->id,
        ]);

        $this->actingAs($responsible)
            ->delete(route('revista-armas.temporary-users.destroy', $temporaryUser))
            ->assertRedirect();

        $temporaryUser->refresh();
        $this->assertFalse($temporaryUser->is_active);
        $this->assertDatabaseHas('weapon_photo_staging', [
            'temporary_photo_user_id' => $temporaryUser->id,
            'weapon_id' => $weapon->id,
        ]);
    }

    public function test_index_with_temporary_user_shows_only_weapons_from_active_grant(): void
    {
        [$responsible, $weaponAssigned] = $this->createResponsibleWithWeapon();

        $weaponOther = Weapon::create([
            'internal_code' => 'SJ-REV-002',
            'serial_number' => 'REV-OTHER-999',
            'weapon_type' => 'Pistola',
            'caliber' => '9MM',
            'brand' => 'Other',
            'capacity' => '15',
            'ownership_type' => 'company_owned',
            'permit_type' => 'porte',
        ]);

        WeaponClientAssignment::create([
            'weapon_id' => $weaponOther->id,
            'client_id' => $weaponAssigned->activeClientAssignment?->client_id
                ?? Client::query()->first()->id,
            'responsible_user_id' => $responsible->id,
            'start_at' => now()->toDateString(),
            'is_active' => true,
            'assigned_by' => $responsible->id,
        ]);

        $temporaryUser = TemporaryPhotoUser::create([
            'owner_responsible_user_id' => $responsible->id,
            'created_by_user_id' => $responsible->id,
            'name' => 'Filtro Grant',
            'email' => 'grant-filter@example.com',
            'is_active' => true,
        ]);

        $this->actingAs($responsible)
            ->post(route('revista-armas.access.store'), [
                'temporary_photo_user_id' => $temporaryUser->id,
                'weapon_ids' => [$weaponAssigned->id],
            ]);

        $this->actingAs($responsible)
            ->get(route('revista-armas.index', ['temporary_photo_user_id' => $temporaryUser->id]))
            ->assertOk()
            ->assertSee('REV-001', false)
            ->assertDontSee('REV-OTHER-999');

        $this->actingAs($responsible)
            ->get(route('revista-armas.index'))
            ->assertOk()
            ->assertSee('REV-001', false)
            ->assertSee('REV-OTHER-999', false);
    }

    public function test_approve_staging_photos_records_weapon_history(): void
    {
        Storage::fake('public');

        [$responsible, $weapon] = $this->createResponsibleWithWeapon();

        $temporaryUser = TemporaryPhotoUser::create([
            'owner_responsible_user_id' => $responsible->id,
            'created_by_user_id' => $responsible->id,
            'name' => 'Temporal Historial',
            'email' => 'hist@example.com',
            'is_active' => true,
        ]);

        foreach (RevistaWeaponPhotoSlots::keys() as $description) {
            $path = 'weapons/'.$weapon->id.'/staging/'.$temporaryUser->id.'/'.$description.'.jpg';
            Storage::disk('public')->put($path, 'fake-image');

            $file = File::create([
                'disk' => 'public',
                'path' => $path,
                'original_name' => $description.'.jpg',
                'mime_type' => 'image/jpeg',
                'size' => 100,
            ]);

            WeaponPhotoStaging::create([
                'temporary_photo_user_id' => $temporaryUser->id,
                'weapon_id' => $weapon->id,
                'description' => $description,
                'file_id' => $file->id,
            ]);
        }

        $this->actingAs($responsible)
            ->post(route('revista-armas.review.approve', [$weapon, $temporaryUser]), [], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $entry = WeaponHistory::query()
            ->where('weapon_id', $weapon->id)
            ->where('kind', WeaponHistory::KIND_PHOTOS)
            ->first();

        $this->assertNotNull($entry);
        $this->assertStringContainsString('actualizadas', strtolower($entry->body));
        $this->assertStringContainsString('Temporal Historial', $entry->body);
        $this->assertStringContainsString('Fecha:', $entry->body);
    }
}
