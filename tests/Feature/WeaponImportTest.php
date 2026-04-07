<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
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

        $response->assertRedirect(route('weapon-imports.show', [
            'weaponImportBatch' => $batch->id,
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
            'weapon_type' => 'Revólver',
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

    public function test_admin_can_execute_import_batch_without_creating_duplicates_when_data_matches(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => 'ADMIN',
        ]);

        Weapon::create([
            'internal_code' => 'SJ-0001',
            'serial_number' => 'SER-1000',
            'weapon_type' => 'Pistola',
            'caliber' => '9MM',
            'brand' => 'Jericho',
            'capacity' => '15',
            'ownership_type' => 'company_owned',
            'permit_type' => 'porte',
            'permit_number' => 'P0001000',
            'permit_expires_at' => '2026-05-09',
        ]);

        $csv = implode("\n", [
            'TIPO DE ARMA,MARCA ARMA,No. SERIE,CALIBRE,CAPACIDAD,TIPO PERMISO,No. PERMISO,FECHA VENCIMIENTO SALVOCONDUCTO',
            'PISTOLA,JERICHO,SER-1000,9MM,15,PORTE,P0001000,09/05/2026',
        ]);

        $file = UploadedFile::fake()->createWithContent('weapons.csv', $csv);

        $this->actingAs($admin)->post(route('weapon-imports.preview'), [
            'document' => $file,
        ]);

        $batch = WeaponImportBatch::query()->firstOrFail();

        $response = $this->actingAs($admin)->post(route('weapon-imports.execute', $batch));

        $response->assertRedirect(route('weapon-imports.show', $batch));

        $this->assertDatabaseCount('weapons', 1);

        $this->assertDatabaseHas('weapon_import_batches', [
            'id' => $batch->id,
            'status' => 'executed',
            'processed_rows' => 1,
            'successful_rows' => 1,
            'failed_rows' => 0,
        ]);

        $this->assertDatabaseHas('weapons', [
            'internal_code' => 'SJ-0001',
            'serial_number' => 'SER-1000',
        ]);
    }

    public function test_admin_can_preview_and_execute_client_uploads_with_minimum_format(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => 'ADMIN',
        ]);

        Client::create([
            'name' => 'Alpha Seguridad SAS',
            'nit' => '900200300-2',
            'legal_representative' => 'Ana Perez',
            'contact_name' => 'Maria Gomez',
            'email' => 'old@example.com',
            'address' => 'Cra 10 # 20-30',
            'city' => 'Bogota',
            'department' => 'Cundinamarca',
        ]);

        $csv = implode("\n", [
            'NIT./CC,RAZON SOCIAL,NOMBRE REP. LEGAL,DIRECCION PRINCIPAL,CIUDAD',
            '900200300-2,Alpha Seguridad Integral,Andrea Perez,Cra 50 # 60-70,Bogota',
            '900200300-3,Beta Seguridad,Carlos Ruiz,Calle 80 # 10-15,Medellin',
        ]);

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);

        $response = $this
            ->actingAs($admin)
            ->post(route('weapon-imports.preview'), [
                'document' => $file,
                'type' => 'client',
            ]);

        $batch = WeaponImportBatch::query()->firstOrFail();

        $response->assertRedirect(route('weapon-imports.show', [
            'weaponImportBatch' => $batch->id,
            'preview' => 1,
        ]));

        $this->assertDatabaseHas('weapon_import_batches', [
            'id' => $batch->id,
            'type' => 'client',
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

        $startResponse = $this
            ->actingAs($admin)
            ->postJson(route('weapon-imports.start', $batch));

        $startResponse
            ->assertOk()
            ->assertJsonPath('progress.status', 'processing')
            ->assertJsonPath('progress.type', 'client');

        $processResponse = $this
            ->actingAs($admin)
            ->postJson(route('weapon-imports.process', $batch));

        $processResponse
            ->assertOk()
            ->assertJsonPath('progress.status', 'executed')
            ->assertJsonPath('progress.processed_rows', 2)
            ->assertJsonPath('progress.successful_rows', 2)
            ->assertJsonPath('progress.failed_rows', 0);

        $this->assertDatabaseHas('clients', [
            'nit' => '900200300-2',
            'name' => 'Alpha Seguridad Integral',
            'legal_representative' => 'Andrea Perez',
            'address' => 'Cra 50 # 60-70',
            'contact_name' => 'Maria Gomez',
            'email' => 'old@example.com',
        ]);

        $this->assertDatabaseHas('clients', [
            'nit' => '900200300-3',
            'name' => 'Beta Seguridad',
            'legal_representative' => 'Carlos Ruiz',
            'address' => 'Calle 80 # 10-15',
            'city' => 'Medellin',
            'contact_name' => null,
            'email' => null,
        ]);
    }
}



