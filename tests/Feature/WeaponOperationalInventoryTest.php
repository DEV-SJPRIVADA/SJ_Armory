<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\IncidentType;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponClientAssignment;
use App\Models\WeaponIncident;
use Database\Seeders\IncidentModalitySeeder;
use Database\Seeders\IncidentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeaponOperationalInventoryTest extends TestCase
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

    public function test_weapons_index_defaults_to_operational_inventory(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $responsible = User::factory()->create(['role' => 'RESPONSABLE']);
        $client = Client::query()->create(['name' => 'Cliente Operativo', 'nit' => '900200300-1']);
        $responsible->clients()->attach($client->id);

        $operational = $this->createWeapon('SER-OP-1', $client, $responsible);
        $maintenance = $this->createWeapon('SER-MT-1', $client, $responsible);
        $stolen = $this->createWeapon('SER-HU-1', $client, $responsible);
        $seized = $this->createWeapon('SER-IN-1', $client, $responsible);
        $retired = $this->createWeapon('SER-DB-1', $client, $responsible);

        $this->createIncident($maintenance, 'en_mantenimiento', WeaponIncident::STATUS_IN_PROGRESS, $admin);
        $this->createIncident($stolen, 'hurtada', WeaponIncident::STATUS_OPEN, $admin);
        $this->createIncident($seized, 'incautada', WeaponIncident::STATUS_IN_PROGRESS, $admin);
        $this->createIncident($retired, 'dar_de_baja', WeaponIncident::STATUS_RESOLVED, $admin, WeaponIncident::OUTCOME_RETIRED_DEFINITIVE);

        $this->actingAs($admin)
            ->get(route('weapons.index'))
            ->assertOk()
            ->assertSee('SER-OP-1')
            ->assertSee('SER-MT-1')
            ->assertDontSee('SER-HU-1')
            ->assertDontSee('SER-IN-1')
            ->assertDontSee('SER-DB-1');

        $this->actingAs($admin)
            ->get(route('weapons.index', ['inventory_scope' => 'all']))
            ->assertOk()
            ->assertSee('SER-HU-1')
            ->assertSee('SER-IN-1')
            ->assertSee('SER-DB-1');

        $this->actingAs($admin)
            ->get(route('weapons.index', ['inventory_scope' => 'non_operational']))
            ->assertOk()
            ->assertSee('SER-HU-1')
            ->assertSee('SER-IN-1')
            ->assertSee('SER-DB-1')
            ->assertDontSee('SER-OP-1');
    }

    public function test_weapon_delete_is_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $client = Client::query()->create(['name' => 'Cliente Demo', 'nit' => '900200300-9']);
        $responsible = User::factory()->create(['role' => 'RESPONSABLE']);
        $responsible->clients()->attach($client->id);
        $weapon = $this->createWeapon('SER-LOCK-1', $client, $responsible);

        $this->actingAs($admin)
            ->delete(route('weapons.destroy', $weapon))
            ->assertForbidden();

        $this->assertDatabaseHas('weapons', ['id' => $weapon->id]);
    }

    public function test_weapons_index_orders_by_expiration_date_and_then_client(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $responsible = User::factory()->create(['role' => 'RESPONSABLE']);

        $alphaClient = Client::query()->create(['name' => 'Alpha Seguridad', 'nit' => '900200300-2']);
        $zuluClient = Client::query()->create(['name' => 'Zulu Seguridad', 'nit' => '900200300-3']);
        $responsible->clients()->attach([$alphaClient->id, $zuluClient->id]);

        $alphaSoon = $this->createWeapon('SER-ALPHA-1', $alphaClient, $responsible, '2026-04-01');
        $zuluSoon = $this->createWeapon('SER-ZULU-1', $zuluClient, $responsible, '2026-04-01');
        $alphaLater = $this->createWeapon('SER-ALPHA-2', $alphaClient, $responsible, '2026-06-01');
        $alphaNoDate = $this->createWeapon('SER-ALPHA-3', $alphaClient, $responsible);

        $this->actingAs($admin)
            ->get(route('weapons.index', ['inventory_scope' => 'all']))
            ->assertOk()
            ->assertSeeInOrder([
                $alphaSoon->serial_number,
                $zuluSoon->serial_number,
                $alphaLater->serial_number,
                $alphaNoDate->serial_number,
            ]);
    }

    private function createWeapon(string $serial, Client $client, User $responsible, ?string $permitExpiresAt = null): Weapon
    {
        $weapon = Weapon::query()->create([
            'internal_code' => 'IC-' . $serial,
            'serial_number' => $serial,
            'weapon_type' => 'Pistola',
            'caliber' => '9MM',
            'brand' => 'Jericho',
            'capacity' => '15',
            'ownership_type' => 'company_owned',
            'permit_type' => 'porte',
            'permit_expires_at' => $permitExpiresAt,
        ]);

        WeaponClientAssignment::query()->create([
            'weapon_id' => $weapon->id,
            'client_id' => $client->id,
            'responsible_user_id' => $responsible->id,
            'start_at' => now()->toDateString(),
            'is_active' => true,
            'assigned_by' => $responsible->id,
        ]);

        return $weapon;
    }

    private function createIncident(Weapon $weapon, string $typeCode, string $status, User $admin, ?string $closureOutcome = null): WeaponIncident
    {
        $type = IncidentType::query()->where('code', $typeCode)->firstOrFail();

        return WeaponIncident::query()->create([
            'weapon_id' => $weapon->id,
            'incident_type_id' => $type->id,
            'status' => $status,
            'observation' => 'Caso de prueba',
            'event_at' => now()->subDay(),
            'reported_at' => now()->subDay(),
            'reported_by' => $admin->id,
            'resolved_at' => $status === WeaponIncident::STATUS_RESOLVED ? now() : null,
            'resolved_by' => $status === WeaponIncident::STATUS_RESOLVED ? $admin->id : null,
            'resolution_note' => $status === WeaponIncident::STATUS_RESOLVED ? 'Cierre de prueba' : null,
            'closure_outcome' => $status === WeaponIncident::STATUS_RESOLVED ? $closureOutcome : null,
        ]);
    }
}
