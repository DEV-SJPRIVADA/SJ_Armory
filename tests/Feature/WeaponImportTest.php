<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WeaponImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_weapon_upload_from_csv(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => 'ADMIN',
        ]);

        Weapon::create([
            'internal_code' => 'SJ-0001',
            'serial_number' => 'IM1509AD',
            'weapon_type' => 'Revólver',
            'caliber' => '38L',
            'brand' => 'LLAMA',
            'capacity' => '6',
            'ownership_type' => 'company_owned',
            'permit_type' => 'porte',
            'permit_number' => 'OLD001',
            'permit_expires_at' => '2025-01-01',
        ]);

        $csv = implode("\n", [
            'TIPO DE ARMA,MARCA ARMA,No. SERIE,CALIBRE,CAPACIDAD,TIPO PERMISO,No. PERMISO,FECHA VENCIMIENTO SALVOCONDUCTO',
            'REVOLVER,LLAMA,IM1509AD,38L,6,PORTE,P0035769,09/05/2026',
            'PISTOLA,JERICHO,45430330,9MM,9,PORTE,P0035768,09/05/2026',
        ]);

        $file = UploadedFile::fake()->createWithContent('weapons.csv', $csv);

        $response = $this
            ->actingAs($admin)
            ->post(route('weapon-imports.preview'), [
                'document' => $file,
            ]);

        $batch = WeaponImportBatch::query()->firstOrFail();

        $response->assertRedirect(route('weapon-imports.index', [
            'batch' => $batch->id,
            'preview' => 1,
        ]));

        $this->assertDatabaseHas('weapon_import_batches', [
            'id' => $batch->id,
            'create_count' => 1,
            'update_count' => 1,
            'no_change_count' => 0,
            'error_count' => 0,
        ]);

        $this->assertDatabaseHas('weapon_import_rows', [
            'batch_id' => $batch->id,
            'row_number' => 2,
            'action' => 'update',
        ]);

        $this->assertDatabaseHas('weapon_import_rows', [
            'batch_id' => $batch->id,
            'row_number' => 3,
            'action' => 'create',
        ]);
    }

    public function test_admin_can_execute_import_batch_with_progress_endpoints(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => 'ADMIN',
        ]);

        Weapon::create([
            'internal_code' => 'SJ-0001',
            'serial_number' => 'IM1509AD',
            'weapon_type' => 'RevÃ³lver',
            'caliber' => '38L',
            'brand' => 'LLAMA',
            'capacity' => '6',
            'ownership_type' => 'company_owned',
            'permit_type' => 'porte',
            'permit_number' => 'P0035769',
            'permit_expires_at' => '2026-05-09',
        ]);

        $csv = implode("\n", [
            'TIPO DE ARMA,MARCA ARMA,No. SERIE,CALIBRE,CAPACIDAD,TIPO PERMISO,No. PERMISO,FECHA VENCIMIENTO SALVOCONDUCTO',
            'REVOLVER,LLAMA,IM1509AD,38L,6,PORTE,P0035769,09/05/2026',
        ]);

        $file = UploadedFile::fake()->createWithContent('weapons.csv', $csv);

        $this->actingAs($admin)->post(route('weapon-imports.preview'), [
            'document' => $file,
        ]);

        $batch = WeaponImportBatch::query()->firstOrFail();

        $startResponse = $this
            ->actingAs($admin)
            ->postJson(route('weapon-imports.start', $batch));

        $startResponse
            ->assertOk()
            ->assertJsonPath('progress.status', 'processing');

        $processResponse = $this
            ->actingAs($admin)
            ->postJson(route('weapon-imports.process', $batch));

        $processResponse
            ->assertOk()
            ->assertJsonPath('progress.status', 'executed')
            ->assertJsonPath('progress.processed_rows', 1)
            ->assertJsonPath('progress.successful_rows', 1)
            ->assertJsonPath('progress.failed_rows', 0);

        $this->assertDatabaseHas('weapon_import_batches', [
            'id' => $batch->id,
            'status' => 'executed',
            'processed_rows' => 1,
            'successful_rows' => 1,
            'failed_rows' => 0,
        ]);

        $this->assertDatabaseHas('weapon_import_rows', [
            'batch_id' => $batch->id,
            'row_number' => 2,
            'execution_status' => 'completed',
        ]);
    }
}
