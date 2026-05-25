<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\IncidentType;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponClientAssignment;
use App\Models\WeaponIncident;
use App\Services\WeaponIncidentReportService;
use App\Support\PostCustodyRole;
use Database\Seeders\IncidentModalitySeeder;
use Database\Seeders\IncidentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeaponCustodyTest extends TestCase
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

    public function test_move_to_armerillo_assigns_custody_post_without_worker(): void
    {
        [$weapon, $responsible, $client] = $this->createWeaponContext();

        $this->actingAs($responsible)
            ->post(route('weapons.custody.armerillo', $weapon))
            ->assertRedirect(route('weapons.show', $weapon));

        $weapon->refresh()->load('activePostAssignment.post');
        $this->assertSame(PostCustodyRole::ARMERILLO, $weapon->activePostAssignment?->post?->custody_role);
        $this->assertTrue($weapon->isOperationalForInventory());
    }

    public function test_move_to_para_mantenimiento_is_non_operational(): void
    {
        [$weapon, $responsible] = $this->createWeaponContext();

        $this->actingAs($responsible)
            ->post(route('weapons.custody.para_mantenimiento', $weapon))
            ->assertRedirect();

        $weapon->refresh()->load('activePostAssignment.post');
        $this->assertSame(
            PostCustodyRole::ARMERILLO_PARA_MANTENIMIENTO,
            $weapon->activePostAssignment?->post?->custody_role,
        );
        $this->assertFalse($weapon->isOperationalForInventory());
    }

    public function test_move_to_para_mantenimiento_after_prior_closed_assignment(): void
    {
        [$weapon, $responsible, $client] = $this->createWeaponContext('SER-CUST-2');
        $armerillo = app(\App\Services\ResponsibleCustodyPostService::class)->armerilloPost($responsible, $client);

        \App\Models\WeaponPostAssignment::query()->create([
            'weapon_id' => $weapon->id,
            'post_id' => $armerillo->id,
            'assigned_by' => $responsible->id,
            'start_at' => now()->subDays(10)->toDateString(),
            'end_at' => now()->subDays(5)->toDateString(),
            'is_active' => null,
        ]);

        $this->actingAs($responsible)
            ->post(route('weapons.custody.armerillo', $weapon))
            ->assertRedirect();

        $this->actingAs($responsible)
            ->post(route('weapons.custody.para_mantenimiento', $weapon))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $weapon->refresh()->load('activePostAssignment.post');
        $this->assertSame(
            PostCustodyRole::ARMERILLO_PARA_MANTENIMIENTO,
            $weapon->activePostAssignment?->post?->custody_role,
        );
    }

    public function test_maps_endpoint_excludes_non_operational_custody(): void
    {
        [$weapon, $responsible, $client] = $this->createWeaponContext('SER-CUST-1');
        $client->update(['latitude' => 4.65, 'longitude' => -74.08]);

        $this->actingAs($responsible)
            ->post(route('weapons.custody.para_mantenimiento', $weapon));

        $serials = collect($this->actingAs($responsible)
            ->getJson(route('maps.weapons'))
            ->json())
            ->pluck('serial_number');

        $this->assertFalse($serials->contains('SER-CUST-1'));
    }

    public function test_incident_report_excludes_legacy_maintenance_type(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon, $responsible, $client] = $this->createWeaponContext('SER-REP-1');

        $maintenanceType = IncidentType::query()->where('code', 'en_mantenimiento')->firstOrFail();
        WeaponIncident::query()->create([
            'weapon_id' => $weapon->id,
            'incident_type_id' => $maintenanceType->id,
            'status' => WeaponIncident::STATUS_IN_PROGRESS,
            'observation' => 'Legado',
            'event_at' => now()->subDay(),
            'reported_at' => now()->subDay(),
            'reported_by' => $admin->id,
        ]);

        $hurtadaType = IncidentType::query()->where('code', 'hurtada')->firstOrFail();
        WeaponIncident::query()->create([
            'weapon_id' => $weapon->id,
            'incident_type_id' => $hurtadaType->id,
            'status' => WeaponIncident::STATUS_OPEN,
            'observation' => 'Hurto',
            'event_at' => now()->subDay(),
            'reported_at' => now()->subDay(),
            'reported_by' => $admin->id,
        ]);

        $dashboard = app(WeaponIncidentReportService::class)->dashboard($admin, ['year' => null], null);

        $this->assertSame(1, $dashboard['kpis'][3]['value']);
    }

    public function test_cannot_create_non_reportable_incident_via_http(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        [$weapon] = $this->createWeaponContext();
        $type = IncidentType::query()->where('code', 'en_armerillo')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('weapon-incidents.store'), [
                'weapon_id' => $weapon->id,
                'incident_type_id' => $type->id,
                'observation' => 'No debe permitirse',
                'event_at' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('incident_type_id');
    }

    /**
     * @return array{0: Weapon, 1: User, 2: Client}
     */
    private function createWeaponContext(string $serial = 'SER-CUST-TEST'): array
    {
        $responsible = User::factory()->create(['role' => 'RESPONSABLE']);
        $client = Client::query()->create([
            'name' => 'Cliente Custodia',
            'nit' => '900300400-1',
            'latitude' => 4.65,
            'longitude' => -74.08,
        ]);
        $responsible->clients()->attach($client->id);

        $weapon = Weapon::query()->create([
            'internal_code' => 'IC-'.$serial,
            'serial_number' => $serial,
            'weapon_type' => 'Pistola',
            'caliber' => '9MM',
            'brand' => 'Test',
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

        return [$weapon, $responsible, $client];
    }
}
