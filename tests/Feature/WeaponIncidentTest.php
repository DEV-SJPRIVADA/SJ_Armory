<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponClientAssignment;
use App\Models\WeaponIncident;
use App\Models\WeaponIncidentUpdate;
use Database\Seeders\IncidentModalitySeeder;
use Database\Seeders\IncidentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WeaponIncidentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            IncidentTypeSeeder::class,
            IncidentModalitySeeder::class,
        ]);
    }

    public function test_admin_can_create_weapon_incident_with_attachment(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext($admin);

        $type = \App\Models\IncidentType::query()->where('code', 'hurtada')->firstOrFail();
        $modality = $type->modalities()->firstOrFail();
        $file = UploadedFile::fake()->create('soporte.pdf', 250, 'application/pdf');

        $response = $this
            ->actingAs($admin)
            ->post(route('weapon-incidents.store'), [
                'weapon_id' => $weapon->id,
                'incident_type_id' => $type->id,
                'incident_modality_id' => $modality->id,
                'status' => WeaponIncident::STATUS_OPEN,
                'observation' => 'Hurto reportado en relevo',
                'note' => 'Se activa seguimiento con supervision.',
                'event_at' => now()->format('Y-m-d H:i:s'),
                'attachment' => $file,
                'redirect_to' => route('reports.weapon-incidents.index'),
            ]);

        $response->assertRedirect(route('reports.weapon-incidents.index'));

        $incident = WeaponIncident::query()->firstOrFail();

        $this->assertDatabaseHas('weapon_incidents', [
            'id' => $incident->id,
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'incident_modality_id' => $modality->id,
            'status' => WeaponIncident::STATUS_OPEN,
            'observation' => 'Hurto reportado en relevo',
        ]);

        $this->assertNotNull($incident->attachment_file_id);
        Storage::disk('local')->assertExists($incident->attachmentFile->path);
    }

    public function test_admin_can_create_hurtada_incident_without_initial_attachment(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext($admin);

        $type = \App\Models\IncidentType::query()->where('code', 'hurtada')->firstOrFail();
        $modality = $type->modalities()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('weapon-incidents.store'), [
                'weapon_id' => $weapon->id,
                'incident_type_id' => $type->id,
                'incident_modality_id' => $modality->id,
                'status' => WeaponIncident::STATUS_OPEN,
                'observation' => 'Hurto sin soporte inicial',
                'note' => 'Se deja constancia del reporte inicial sin adjunto.',
                'event_at' => now()->format('Y-m-d H:i:s'),
                'redirect_to' => route('reports.weapon-incidents.index'),
            ])
            ->assertRedirect(route('reports.weapon-incidents.index'));

        $this->assertDatabaseHas('weapon_incidents', [
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'attachment_file_id' => null,
            'observation' => 'Hurto sin soporte inicial',
        ]);
    }

    public function test_admin_can_create_incautada_incident_with_required_modality(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext($admin);

        $type = \App\Models\IncidentType::query()->where('code', 'incautada')->firstOrFail();
        $modality = $type->modalities()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('weapon-incidents.store'), [
                'weapon_id' => $weapon->id,
                'incident_type_id' => $type->id,
                'incident_modality_id' => $modality->id,
                'status' => WeaponIncident::STATUS_OPEN,
                'observation' => 'Incautacion en control vial',
                'note' => 'Se reporta apertura del caso por incautacion.',
                'event_at' => now()->format('Y-m-d H:i:s'),
                'redirect_to' => route('reports.weapon-incidents.index'),
            ])
            ->assertRedirect(route('reports.weapon-incidents.index'));

        $this->assertDatabaseHas('weapon_incidents', [
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'incident_modality_id' => $modality->id,
            'observation' => 'Incautacion en control vial',
        ]);
    }

    public function test_admin_can_create_perdida_incident_with_required_modality(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext($admin);

        $type = \App\Models\IncidentType::query()->where('code', 'perdida')->firstOrFail();
        $modality = $type->modalities()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('weapon-incidents.store'), [
                'weapon_id' => $weapon->id,
                'incident_type_id' => $type->id,
                'incident_modality_id' => $modality->id,
                'status' => WeaponIncident::STATUS_OPEN,
                'observation' => 'Perdida en desplazamiento operativo',
                'note' => 'Se registra la perdida inicial con modalidad.',
                'event_at' => now()->format('Y-m-d H:i:s'),
                'redirect_to' => route('reports.weapon-incidents.index'),
            ])
            ->assertRedirect(route('reports.weapon-incidents.index'));

        $this->assertDatabaseHas('weapon_incidents', [
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'incident_modality_id' => $modality->id,
            'observation' => 'Perdida en desplazamiento operativo',
        ]);
    }

    public function test_admin_can_view_incident_dashboard_and_type_drilldown(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext($admin);

        $type = \App\Models\IncidentType::query()->where('code', 'hurtada')->firstOrFail();
        $modality = $type->modalities()->firstOrFail();

        WeaponIncident::create([
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'incident_modality_id' => $modality->id,
            'status' => WeaponIncident::STATUS_OPEN,
            'observation' => 'Hurto en relevo',
            'note' => 'Caso registrado',
            'event_at' => now()->subDay(),
            'reported_at' => now()->subDay(),
            'reported_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('reports.weapon-incidents.index'))
            ->assertOk()
            ->assertSee('Novedades operativas')
            ->assertSee($weapon->serial_number)
            ->assertSee('Hurto en relevo');

        $this->actingAs($admin)
            ->get(route('reports.weapon-incidents.show', $type))
            ->assertOk()
            ->assertSee('Novedades: Hurtada')
            ->assertSee($modality->name);
    }

    public function test_dashboard_defaults_to_all_years_scope(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext($admin);
        $type = \App\Models\IncidentType::query()->where('code', 'hurtada')->firstOrFail();
        $modality = $type->modalities()->firstOrFail();

        WeaponIncident::create([
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'incident_modality_id' => $modality->id,
            'status' => WeaponIncident::STATUS_OPEN,
            'observation' => 'Hurto historico',
            'note' => 'Caso 2023',
            'event_at' => now()->setYear(2023)->startOfDay(),
            'reported_at' => now()->subDay(),
            'reported_by' => $admin->id,
        ]);

        WeaponIncident::create([
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'incident_modality_id' => $modality->id,
            'status' => WeaponIncident::STATUS_IN_PROGRESS,
            'observation' => 'Hurto vigente',
            'note' => 'Caso 2026',
            'event_at' => now()->setYear(2026)->startOfDay(),
            'reported_at' => now(),
            'reported_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('reports.weapon-incidents.index'))
            ->assertOk()
            ->assertSee('Todos')
            ->assertSee('Novedades registradas en todos los años')
            ->assertSee('Hurto historico')
            ->assertSee('Hurto vigente');
    }

    public function test_admin_can_search_weapons_for_incident_modal(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon, $client] = $this->createWeaponContext($admin);

        $weapon->update([
            'brand' => 'Stoeger Cougar',
            'permit_expires_at' => now()->addYear()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->getJson(route('reports.weapon-incidents.weapons.search', ['q' => 'Stoeger']))
            ->assertOk()
            ->assertJsonFragment([
                'client' => $client->name,
                'brand' => 'Stoeger Cougar',
                'serial' => $weapon->serial_number,
            ]);
    }

    public function test_admin_can_add_updates_close_and_reopen_incident_case_file(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext($admin);
        $type = \App\Models\IncidentType::query()->where('code', 'hurtada')->firstOrFail();

        $incident = WeaponIncident::query()->create([
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'status' => WeaponIncident::STATUS_OPEN,
            'observation' => 'Hurto reportado en desplazamiento',
            'note' => 'Caso base',
            'event_at' => now()->subDay(),
            'reported_at' => now()->subDay(),
            'reported_by' => $admin->id,
        ]);

        $attachment = UploadedFile::fake()->create('recuperacion.pdf', 100, 'application/pdf');

        $this->actingAs($admin)
            ->post(route('weapon-incidents.updates.store', $incident), [
                'event_type' => WeaponIncidentUpdate::EVENT_RECOVERY,
                'status' => WeaponIncident::STATUS_IN_PROGRESS,
                'note' => 'Rastreo con autoridad local y recuperacion inicial.',
                'happened_at' => now()->format('Y-m-d H:i:s'),
                'attachment' => $attachment,
                'redirect_to' => route('weapons.show', $weapon),
            ])
            ->assertRedirect(route('weapons.show', $weapon));

        $incident->refresh();

        $this->assertSame(WeaponIncident::STATUS_IN_PROGRESS, $incident->status);
        $this->assertDatabaseHas('weapon_incident_updates', [
            'weapon_incident_id' => $incident->id,
            'event_type' => WeaponIncidentUpdate::EVENT_RECOVERY,
            'status_from' => WeaponIncident::STATUS_OPEN,
            'status_to' => WeaponIncident::STATUS_IN_PROGRESS,
        ]);

        $update = WeaponIncidentUpdate::query()->where('weapon_incident_id', $incident->id)->firstOrFail();
        $this->assertNotNull($update->attachment_file_id);
        Storage::disk('local')->assertExists($update->attachmentFile->path);

        $this->actingAs($admin)
            ->patch(route('weapon-incidents.close', $incident), [
                'status' => WeaponIncident::STATUS_RESOLVED,
                'closure_outcome' => WeaponIncident::OUTCOME_REINTEGRATED,
                'resolution_note' => 'Arma asegurada y expediente cerrado.',
                'redirect_to' => route('weapons.show', $weapon),
            ])
            ->assertRedirect(route('weapons.show', $weapon));

        $incident->refresh();
        $this->assertSame(WeaponIncident::STATUS_RESOLVED, $incident->status);
        $this->assertSame('Arma asegurada y expediente cerrado.', $incident->resolution_note);

        $this->actingAs($admin)
            ->patch(route('weapon-incidents.reopen', $incident), [
                'status' => WeaponIncident::STATUS_OPEN,
                'message' => 'Se reabre para reintegracion operativa.',
                'redirect_to' => route('weapons.show', $weapon),
            ])
            ->assertRedirect(route('weapons.show', $weapon));

        $incident->refresh();
        $this->assertSame(WeaponIncident::STATUS_OPEN, $incident->status);
        $this->assertNull($incident->resolved_at);
        $this->assertDatabaseHas('weapon_incident_updates', [
            'weapon_incident_id' => $incident->id,
            'event_type' => WeaponIncidentUpdate::EVENT_REOPEN,
            'status_to' => WeaponIncident::STATUS_OPEN,
        ]);
    }

    public function test_open_incident_dashboard_exposes_follow_up_and_close_actions(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext($admin);
        $type = \App\Models\IncidentType::query()->where('code', 'hurtada')->firstOrFail();

        WeaponIncident::query()->create([
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'status' => WeaponIncident::STATUS_IN_PROGRESS,
            'observation' => 'Hurto con investigacion activa',
            'note' => 'Caso abierto para seguimiento',
            'event_at' => now()->subDay(),
            'reported_at' => now()->subDay(),
            'reported_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('reports.weapon-incidents.index'))
            ->assertOk()
            ->assertSee('Gestionar')
            ->assertSee('Guardar seguimiento')
            ->assertSee('Cerrar expediente');
    }

    public function test_resolved_incident_dashboard_exposes_reopen_action(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext($admin);
        $type = \App\Models\IncidentType::query()->where('code', 'perdida')->firstOrFail();

        WeaponIncident::query()->create([
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'status' => WeaponIncident::STATUS_RESOLVED,
            'observation' => 'Perdida cerrada',
            'note' => 'Caso cerrado previamente',
            'event_at' => now()->subDays(2),
            'reported_at' => now()->subDays(2),
            'reported_by' => $admin->id,
            'resolved_at' => now()->subDay(),
            'resolved_by' => $admin->id,
            'resolution_note' => 'Arma recuperada y expediente finalizado.',
            'closure_outcome' => WeaponIncident::OUTCOME_REINTEGRATED,
        ]);

        $this->actingAs($admin)
            ->get(route('reports.weapon-incidents.index'))
            ->assertOk()
            ->assertSee('Reabrir expediente')
            ->assertSee('Expediente cerrado');
    }

    public function test_closed_incident_requires_reopen_before_new_follow_up(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext($admin);
        $type = \App\Models\IncidentType::query()->where('code', 'perdida')->firstOrFail();

        $incident = WeaponIncident::query()->create([
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'status' => WeaponIncident::STATUS_RESOLVED,
            'observation' => 'Caso cerrado',
            'note' => 'Reporte inicial',
            'event_at' => now()->subDay(),
            'reported_at' => now()->subDay(),
            'reported_by' => $admin->id,
            'resolved_at' => now()->subHours(12),
            'resolved_by' => $admin->id,
            'resolution_note' => 'Cierre previo',
            'closure_outcome' => WeaponIncident::OUTCOME_REINTEGRATED,
        ]);

        $this->actingAs($admin)
            ->post(route('weapon-incidents.updates.store', $incident), [
                'event_type' => WeaponIncidentUpdate::EVENT_NOTE,
                'note' => 'Intento de seguimiento cerrado',
                'happened_at' => now()->format('Y-m-d H:i:s'),
                'redirect_to' => route('weapons.show', $weapon),
            ])
            ->assertSessionHasErrors('incident_update');

        $this->assertDatabaseMissing('weapon_incident_updates', [
            'weapon_incident_id' => $incident->id,
            'note' => 'Intento de seguimiento cerrado',
        ]);
    }

    public function test_manual_follow_up_cannot_use_reported_event_type(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext($admin);
        $type = \App\Models\IncidentType::query()->where('code', 'perdida')->firstOrFail();

        $incident = WeaponIncident::query()->create([
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'status' => WeaponIncident::STATUS_OPEN,
            'observation' => 'Caso abierto',
            'event_at' => now()->subDay(),
            'reported_at' => now()->subDay(),
            'reported_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('weapon-incidents.updates.store', $incident), [
                'event_type' => WeaponIncidentUpdate::EVENT_REPORTED,
                'note' => 'Intento invalido',
                'happened_at' => now()->format('Y-m-d H:i:s'),
                'redirect_to' => route('weapons.show', $weapon),
            ])
            ->assertSessionHasErrors('event_type');

        $this->assertDatabaseMissing('weapon_incident_updates', [
            'weapon_incident_id' => $incident->id,
            'event_type' => WeaponIncidentUpdate::EVENT_REPORTED,
            'note' => 'Intento invalido',
        ]);
    }

    public function test_closing_incident_requires_resolution_note_when_type_demands_it(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext($admin);
        $type = \App\Models\IncidentType::query()->where('code', 'dar_de_baja')->firstOrFail();

        $incident = WeaponIncident::query()->create([
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'status' => WeaponIncident::STATUS_IN_PROGRESS,
            'observation' => 'Proceso de baja',
            'note' => 'Caso de baja abierto',
            'event_at' => now()->subDay(),
            'reported_at' => now()->subDay(),
            'reported_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('weapon-incidents.close', $incident), [
                'status' => WeaponIncident::STATUS_RESOLVED,
                'closure_outcome' => WeaponIncident::OUTCOME_RETIRED_DEFINITIVE,
                'resolution_note' => '',
                'redirect_to' => route('weapons.show', $weapon),
            ])
            ->assertSessionHasErrors('resolution_note');

        $incident->refresh();
        $this->assertSame(WeaponIncident::STATUS_IN_PROGRESS, $incident->status);
        $this->assertNull($incident->resolved_at);
    }

    public function test_incautada_definitive_close_keeps_weapon_out_of_operation(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext($admin);
        $type = \App\Models\IncidentType::query()->where('code', 'incautada')->firstOrFail();

        $incident = WeaponIncident::query()->create([
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'status' => WeaponIncident::STATUS_IN_PROGRESS,
            'observation' => 'Incautación en control',
            'note' => 'Caso abierto',
            'event_at' => now()->subDay(),
            'reported_at' => now()->subDay(),
            'reported_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('weapon-incidents.close', $incident), [
                'status' => WeaponIncident::STATUS_RESOLVED,
                'closure_outcome' => WeaponIncident::OUTCOME_SEIZURE_DEFINITIVE,
                'resolution_note' => 'Incautación definitiva confirmada.',
                'redirect_to' => route('reports.weapon-incidents.index'),
            ])
            ->assertRedirect(route('reports.weapon-incidents.index'));

        $incident->refresh();

        $this->assertSame(WeaponIncident::STATUS_RESOLVED, $incident->status);
        $this->assertSame(WeaponIncident::OUTCOME_SEIZURE_DEFINITIVE, $incident->closure_outcome);
        $this->assertTrue($incident->blocksOperationalAvailability());
    }

    public function test_weapon_history_renders_incident_table_and_detail_stays_clean(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext($admin);
        $type = \App\Models\IncidentType::query()->where('code', 'perdida')->firstOrFail();

        $incident = WeaponIncident::query()->create([
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'status' => WeaponIncident::STATUS_OPEN,
            'observation' => 'Perdida en traslado',
            'note' => 'Caso inicial',
            'event_at' => now()->subDay(),
            'reported_at' => now()->subDay(),
            'reported_by' => $admin->id,
        ]);

        WeaponIncidentUpdate::query()->create([
            'weapon_incident_id' => $incident->id,
            'event_type' => WeaponIncidentUpdate::EVENT_REINTEGRATION,
            'note' => 'Reintegracion condicionada despues de validacion.',
            'happened_at' => now(),
            'status_from' => WeaponIncident::STATUS_OPEN,
            'status_to' => WeaponIncident::STATUS_IN_PROGRESS,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('weapons.show', $weapon))
            ->assertOk()
            ->assertDontSee('Registrar novedad')
            ->assertDontSee('Expediente vivo')
            ->assertDontSee('Reintegracion condicionada despues de validacion.');

        $this->actingAs($admin)
            ->get(route('reports.history', ['weapon_id' => $weapon->id]))
            ->assertOk()
            ->assertSee('Novedades')
            ->assertSee('Perdida en traslado')
            ->assertSee('Caso inicial')
            ->assertDontSee('Expediente vivo');
    }

    private function createWeaponContext(User $responsible): array
    {
        $client = Client::query()->create([
            'name' => 'Cliente Demo',
            'nit' => '900100200-1',
        ]);

        $responsible->clients()->attach($client->id);

        $weapon = Weapon::query()->create([
            'internal_code' => 'SJ-2001',
            'serial_number' => 'SER-2001',
            'weapon_type' => 'Pistola',
            'caliber' => '9MM',
            'brand' => 'Jericho',
            'capacity' => '15',
            'ownership_type' => 'company_owned',
            'permit_type' => 'porte',
        ]);

        WeaponClientAssignment::query()->create([
            'weapon_id' => $weapon->id,
            'client_id' => $client->id,
            'responsible_user_id' => $responsible->id,
            'start_at' => now()->toDateString(),
            'is_active' => true,
            'assigned_by' => $responsible->id,
        ]);

        return [$weapon, $client];
    }
}
