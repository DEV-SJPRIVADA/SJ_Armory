<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WeaponPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_photo_replaces_file_without_deleting_weapon_photo_row(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'ADMIN']);

        $weapon = Weapon::create([
            'internal_code' => 'SJ-PHOTO-001',
            'serial_number' => 'PHOTO-001',
            'weapon_type' => 'Pistola',
            'caliber' => '9MM',
            'brand' => 'Glock',
            'capacity' => '15',
            'ownership_type' => 'company_owned',
            'permit_type' => 'porte',
        ]);

        $oldPath = 'weapons/'.$weapon->id.'/photos/old.jpg';
        Storage::disk('public')->put($oldPath, 'old-image');

        $oldFile = File::create([
            'disk' => 'public',
            'path' => $oldPath,
            'original_name' => 'old.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 10,
            'uploaded_by' => $admin->id,
        ]);

        $photo = WeaponPhoto::create([
            'weapon_id' => $weapon->id,
            'file_id' => $oldFile->id,
            'description' => 'lado_izquierdo',
        ]);

        $this->actingAs($admin)
            ->post(route('weapons.photos.update', [$weapon, $photo]), [
                '_method' => 'PATCH',
                'photo' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertJson(['ok' => true]);

        $photo->refresh();

        $this->assertDatabaseHas('weapon_photos', [
            'id' => $photo->id,
            'weapon_id' => $weapon->id,
            'description' => 'lado_izquierdo',
        ]);

        $this->assertNotSame($oldFile->id, $photo->file_id);
        $this->assertDatabaseMissing('files', ['id' => $oldFile->id]);
        $this->assertNotNull($photo->file);
        Storage::disk('public')->assertExists($photo->file->path);
    }
}
