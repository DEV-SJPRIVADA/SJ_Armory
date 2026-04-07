<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\IncidentType;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponClientAssignment;
use App\Models\WeaponIncident;
use App\Models\WeaponIncidentFollowUp;
use Database\Seeders\IncidentModalitySeeder;
use Database\Seeders\IncidentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeaponIncidentStructureTest extends TestCase
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

    public function test_incident_type_seeder_populates_operational_rule_defaults(): void
    {
        $hurtada = IncidentType::query()->where('code', 'hurtada')->firstOrFail();
        $perdida = IncidentType::query()->where('code', 'perdida')->firstOrFail();
        $incautada = IncidentType::query()->where('code', 'incautada')->firstOrFail();
        $maintenance = IncidentType::query()->where('code', 'en_mantenimiento')->firstOrFail();

        $this->assertTrue($hurtada->requires_modality);
        $this->assertFalse($hurtada->requires_attachment);
        $this->assertTrue($hurtada->requires_resolution_note);
        $this->assertSame(WeaponIncident::STATUS_OPEN, $hurtada->default_status);
        $this->assertSame(4, $hurtada->sla_hours);

        $this->assertTrue($perdida->requires_modality);
        $this->assertFalse($perdida->requires_attachment);
        $this->assertTrue($perdida->requires_resolution_note);
        $this->assertTrue($perdida->blocks_operation);
        $this->assertFalse($perdida->persists_operational_block);
        $this->assertSame(WeaponIncident::STATUS_OPEN, $perdida->default_status);
        $this->assertSame(8, $perdida->sla_hours);
        $this->assertSame(10, $perdida->modalities()->count());

        $this->assertTrue($incautada->requires_modality);
        $this->assertFalse($incautada->requires_attachment);
        $this->assertTrue($incautada->requires_resolution_note);
        $this->assertTrue($incautada->blocks_operation);
        $this->assertFalse($incautada->persists_operational_block);
        $this->assertSame(WeaponIncident::STATUS_OPEN, $incautada->default_status);
        $this->assertSame(12, $incautada->sla_hours);
        $this->assertSame(10, $incautada->modalities()->count());

        $this->assertFalse($maintenance->requires_modality);
        $this->assertFalse($maintenance->requires_attachment);
        $this->assertFalse($maintenance->requires_resolution_note);
        $this->assertSame(WeaponIncident::STATUS_IN_PROGRESS, $maintenance->default_status);
        $this->assertSame(72, $maintenance->sla_hours);

        $this->assertSame([
            WeaponIncident::OUTCOME_REINTEGRATED => 'Recuperada y reintegrada',
            WeaponIncident::OUTCOME_RECOVERED_PENDING_VALIDATION => 'Recuperada pendiente de validación',
            WeaponIncident::OUTCOME_SEIZURE_DEFINITIVE => 'Incautación definitiva',
        ], WeaponIncident::closureOutcomeOptionsForType($incautada));
    }

    public function test_weapon_incident_follow_ups_are_related_and_cascade_on_delete(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext($admin);
        $type = IncidentType::query()->where('code', 'perdida')->firstOrFail();

        $incident = WeaponIncident::query()->create([
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'status' => WeaponIncident::STATUS_OPEN,
            'observation' => 'Perdida en turno nocturno',
            'event_at' => now()->subHour(),
            'reported_at' => now()->subHour(),
            'reported_by' => $admin->id,
        ]);

        $followUp = $incident->followUps()->create([
            'user_id' => $admin->id,
            'entry_type' => WeaponIncidentFollowUp::ENTRY_CONTACT,
            'message' => 'Se solicita ampliacion de informacion al responsable.',
            'follow_up_at' => now()->addHours(2),
            'meta' => ['channel' => 'phone'],
        ]);

        $this->assertDatabaseHas('weapon_incident_follow_ups', [
            'id' => $followUp->id,
            'weapon_incident_id' => $incident->id,
            'entry_type' => WeaponIncidentFollowUp::ENTRY_CONTACT,
        ]);

        $this->assertTrue($incident->followUps->contains($followUp));
        $this->assertSame($followUp->id, $incident->latestFollowUp?->id);

        $incident->delete();

        $this->assertDatabaseMissing('weapon_incident_follow_ups', [
            'id' => $followUp->id,
        ]);
    }

    private function createWeaponContext(User $responsible): array
    {
        $client = Client::query()->create([
            'name' => 'Cliente Estructura',
            'nit' => '900500600-1',
        ]);

        $responsible->clients()->attach($client->id);

        $weapon = Weapon::query()->create([
            'internal_code' => 'SJ-4001',
            'serial_number' => 'SER-4001',
            'weapon_type' => 'Pistola',
            'caliber' => '9MM',
            'brand' => 'Beretta',
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
