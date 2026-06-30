<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponClientAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormatControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createWeaponWithAssignment(User $responsible, string $serial = 'FMT-001'): Weapon
    {
        $client = Client::create([
            'name' => 'Cliente Formato',
            'nit' => '900999888',
        ]);

        $weapon = Weapon::create([
            'internal_code' => 'SJ-FMT-'.$serial,
            'serial_number' => $serial,
            'weapon_type' => 'Pistola',
            'caliber' => '9MM',
            'brand' => 'Glock',
            'capacity' => '15',
            'ownership_type' => 'company_owned',
            'permit_type' => 'porte',
            'permit_number' => 'P-FMT',
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

        return $weapon;
    }

    public function test_admin_can_open_formats_index(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)
            ->get(route('formatos.index'))
            ->assertOk()
            ->assertSee(__('Revista mensual de armamento'));
    }

    public function test_monthly_review_weapons_returns_paginated_table_rows(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $responsible = User::factory()->create(['role' => 'RESPONSABLE', 'responsibility_level_id' => 1]);
        $weapon = $this->createWeaponWithAssignment($responsible, 'FMT-TABLE-001');

        $response = $this->actingAs($admin)
            ->getJson(route('formatos.revista-mensual.armas', ['q' => 'FMT-TABLE-001']));

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('rows.0.id', $weapon->id)
            ->assertJsonPath('rows.0.serie', 'FMT-TABLE-001')
            ->assertJsonPath('rows.0.cliente', 'Cliente Formato');
    }

    public function test_preview_monthly_review_requires_selected_weapon_ids(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $responsible = User::factory()->create(['role' => 'RESPONSABLE', 'responsibility_level_id' => 1]);
        $weapon = $this->createWeaponWithAssignment($responsible, 'FMT-PREV-001');

        $this->actingAs($admin)
            ->postJson(route('formatos.revista-mensual.vista-previa'), [
                'weapon_ids' => [$weapon->id],
            ])
            ->assertOk()
            ->assertJson([
                'count' => 1,
                'pages' => 1,
                'rows_per_page' => 20,
            ]);
    }

    public function test_download_monthly_review_exports_only_selected_weapons(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $responsible = User::factory()->create(['role' => 'RESPONSABLE', 'responsibility_level_id' => 1]);
        $selected = $this->createWeaponWithAssignment($responsible, 'FMT-DL-001');
        $this->createWeaponWithAssignment($responsible, 'FMT-DL-002');

        $response = $this->actingAs($admin)
            ->post(route('formatos.revista-mensual.descargar'), [
                'weapon_ids' => [$selected->id],
            ]);

        $response->assertOk();
        $this->assertStringContainsString(
            'FO-OP-03 Revista mensual de armamento',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_responsible_only_sees_weapons_in_their_scope(): void
    {
        $responsibleA = User::factory()->create(['role' => 'RESPONSABLE', 'responsibility_level_id' => 1]);
        $responsibleB = User::factory()->create(['role' => 'RESPONSABLE', 'responsibility_level_id' => 1]);
        $weaponA = $this->createWeaponWithAssignment($responsibleA, 'FMT-SCOPE-A');
        $this->createWeaponWithAssignment($responsibleB, 'FMT-SCOPE-B');

        $this->actingAs($responsibleA)
            ->getJson(route('formatos.revista-mensual.armas'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('rows.0.id', $weaponA->id);
    }

    public function test_column_options_respect_active_filters(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $responsible = User::factory()->create(['role' => 'RESPONSABLE', 'responsibility_level_id' => 1]);
        $this->createWeaponWithAssignment($responsible, 'FMT-COL-001');

        $this->actingAs($admin)
            ->getJson(route('formatos.revista-mensual.column-options', [
                'target' => 'cliente',
                'col' => ['cliente' => ['Cliente Formato']],
            ]))
            ->assertOk()
            ->assertJsonPath('values.0', 'Cliente Formato');
    }
}
