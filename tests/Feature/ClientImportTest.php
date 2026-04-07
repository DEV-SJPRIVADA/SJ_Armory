<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Models\WeaponImportBatch;
use App\Models\WeaponImportRow;
use App\Services\Imports\ClientImportProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_import_processor_prepares_preview_rows_for_create_update_and_no_change(): void
    {
        $existingClient = Client::query()->create([
            'name' => 'Cliente Antiguo',
            'nit' => '900123456-7',
            'legal_representative' => 'Ana Perez',
            'address' => 'Cra 1 #2-3',
            'city' => 'Bogota',
            'department' => 'Cundinamarca',
        ]);

        $processor = app(ClientImportProcessor::class);

        [$rows, $counts] = $processor->prepareRows([
            'NIT./CC',
            'RAZON SOCIAL',
            'NOMBRE REP. LEGAL',
            'DIRECCION PRINCIPAL',
            'CIUDAD',
        ], [
            [
                'row_number' => 2,
                'cells' => ['900123456-7', 'Cliente Antiguo', 'Ana Perez', 'Cra 1 #2-3', 'Bogota'],
            ],
            [
                'row_number' => 3,
                'cells' => ['900987654-3', 'Cliente Nuevo', 'Pedro Gomez', 'Cra 4 #5-6', 'Cali'],
            ],
        ]);

        $this->assertSame(1, $counts[WeaponImportRow::ACTION_NO_CHANGE]);
        $this->assertSame(1, $counts[WeaponImportRow::ACTION_CREATE]);
        $this->assertSame(0, $counts[WeaponImportRow::ACTION_ERROR]);

        $this->assertSame(WeaponImportRow::ACTION_NO_CHANGE, $rows[0]['action']);
        $this->assertSame($existingClient->id, $rows[0]['client_id']);

        $this->assertSame(WeaponImportRow::ACTION_CREATE, $rows[1]['action']);
        $this->assertNull($rows[1]['client_id']);
    }

    public function test_client_import_processor_executes_create_and_update_rows(): void
    {
        $user = User::factory()->create(['role' => 'ADMIN']);
        $processor = app(ClientImportProcessor::class);

        $batch = WeaponImportBatch::query()->create([
            'status' => 'draft',
            'type' => WeaponImportBatch::TYPE_CLIENT,
            'source_name' => 'clientes.csv',
            'total_rows' => 2,
            'create_count' => 1,
            'update_count' => 1,
            'no_change_count' => 0,
            'error_count' => 0,
        ]);

        $existingClient = Client::query()->create([
            'name' => 'Cliente Base',
            'nit' => '900111222-3',
            'legal_representative' => 'Maria Lopez',
            'address' => 'Cra 10 #11-12',
            'city' => 'Bogota',
            'contact_name' => 'Contacto Base',
            'email' => 'base@example.com',
        ]);

        $updateRow = WeaponImportRow::query()->create([
            'batch_id' => $batch->id,
            'client_id' => $existingClient->id,
            'row_number' => 2,
            'action' => WeaponImportRow::ACTION_UPDATE,
            'summary' => 'Actualiza cliente base',
            'raw_payload' => [
                'nit' => '900111222-3',
                'name' => 'Cliente Base Nuevo',
                'legal_representative' => 'Maria Lopez',
                'address' => 'Cra 20 #21-22',
                'city' => 'Bogota',
            ],
            'normalized_payload' => [
                'nit' => '900111222-3',
                'name' => 'Cliente Base Nuevo',
                'legal_representative' => 'Maria Lopez',
                'address' => 'Cra 20 #21-22',
                'city' => 'Bogota',
            ],
            'before_payload' => null,
            'after_payload' => null,
            'errors' => [],
        ]);

        $processor->executeRow($updateRow, $user);

        $existingClient->refresh();
        $updateRow->refresh();

        $this->assertSame('Cliente Base Nuevo', $existingClient->name);
        $this->assertSame('Contacto Base', $existingClient->contact_name);
        $this->assertSame('base@example.com', $existingClient->email);
        $this->assertSame(WeaponImportRow::EXECUTION_COMPLETED, $updateRow->execution_status);
        $this->assertSame($existingClient->id, $updateRow->client_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'client_import_updated',
            'auditable_type' => Client::class,
            'auditable_id' => $existingClient->id,
        ]);

        $createRow = WeaponImportRow::query()->create([
            'batch_id' => $batch->id,
            'row_number' => 3,
            'action' => WeaponImportRow::ACTION_CREATE,
            'summary' => 'Crear cliente nuevo',
            'raw_payload' => [
                'nit' => '900333444-5',
                'name' => 'Cliente Nuevo',
                'legal_representative' => 'Carlos Ruiz',
                'address' => 'Cra 30 #31-32',
                'city' => 'Cali',
            ],
            'normalized_payload' => [
                'nit' => '900333444-5',
                'name' => 'Cliente Nuevo',
                'legal_representative' => 'Carlos Ruiz',
                'address' => 'Cra 30 #31-32',
                'city' => 'Cali',
            ],
            'before_payload' => null,
            'after_payload' => null,
            'errors' => [],
        ]);

        $processor->executeRow($createRow, $user);

        $createRow->refresh();

        $this->assertDatabaseHas('clients', [
            'nit' => '900333444-5',
            'name' => 'Cliente Nuevo',
            'city' => 'Cali',
            'contact_name' => null,
            'email' => null,
        ]);

        $this->assertSame(WeaponImportRow::EXECUTION_COMPLETED, $createRow->execution_status);
        $this->assertNotNull($createRow->client_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'client_import_created',
            'auditable_type' => Client::class,
            'auditable_id' => $createRow->client_id,
        ]);
    }

    public function test_client_import_processor_marks_error_when_multiple_clients_share_same_nit(): void
    {
        Client::query()->create([
            'name' => 'Cliente A',
            'nit' => '900123456-7',
        ]);

        Client::query()->create([
            'name' => 'Cliente B',
            'nit' => '9001234567',
        ]);

        $processor = app(ClientImportProcessor::class);

        [$rows, $counts] = $processor->prepareRows([
            'NIT./CC',
            'RAZON SOCIAL',
            'NOMBRE REP. LEGAL',
            'DIRECCION PRINCIPAL',
            'CIUDAD',
        ], [
            [
                'row_number' => 2,
                'cells' => ['9001234567', 'Cliente Duplicado', 'Ana Perez', 'Cra 1 #2-3', 'Bogota'],
            ],
        ]);

        $this->assertSame(1, $counts[WeaponImportRow::ACTION_ERROR]);
        $this->assertSame(WeaponImportRow::ACTION_ERROR, $rows[0]['action']);
        $this->assertContains('Ya existen varios clientes con el mismo NIT./CC en el sistema.', $rows[0]['errors']);
    }
}

