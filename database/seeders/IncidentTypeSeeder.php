<?php

namespace Database\Seeders;

use App\Models\IncidentType;
use App\Models\WeaponIncident;
use Illuminate\Database\Seeder;

class IncidentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => 'hurtada',
                'name' => 'Hurtada',
                'color' => '#be123c',
                'sort_order' => 10,
                'requires_modality' => true,
                'requires_attachment' => false,
                'requires_resolution_note' => true,
                'default_status' => WeaponIncident::STATUS_OPEN,
                'sla_hours' => 4,
                'blocks_operation' => true,
                'persists_operational_block' => false,
                'is_reportable' => true,
            ],
            [
                'code' => 'perdida',
                'name' => 'Perdida',
                'color' => '#b91c1c',
                'sort_order' => 20,
                'requires_modality' => true,
                'requires_attachment' => false,
                'requires_resolution_note' => true,
                'default_status' => WeaponIncident::STATUS_OPEN,
                'sla_hours' => 8,
                'blocks_operation' => true,
                'persists_operational_block' => false,
                'is_reportable' => true,
            ],
            [
                'code' => 'incautada',
                'name' => 'Incautada',
                'color' => '#7c2d12',
                'sort_order' => 25,
                'requires_modality' => true,
                'requires_attachment' => false,
                'requires_resolution_note' => true,
                'default_status' => WeaponIncident::STATUS_OPEN,
                'sla_hours' => 12,
                'blocks_operation' => true,
                'persists_operational_block' => false,
                'is_reportable' => true,
            ],
            [
                'code' => 'en_mantenimiento',
                'name' => 'En Mantenimiento',
                'color' => '#0f766e',
                'sort_order' => 30,
                'requires_modality' => false,
                'requires_attachment' => false,
                'requires_resolution_note' => false,
                'default_status' => WeaponIncident::STATUS_IN_PROGRESS,
                'sla_hours' => 72,
                'blocks_operation' => false,
                'persists_operational_block' => false,
                'is_reportable' => false,
            ],
            [
                'code' => 'para_mantenimiento',
                'name' => 'Para Mantenimiento',
                'color' => '#0ea5e9',
                'sort_order' => 40,
                'requires_modality' => false,
                'requires_attachment' => false,
                'requires_resolution_note' => false,
                'default_status' => WeaponIncident::STATUS_OPEN,
                'sla_hours' => 48,
                'blocks_operation' => false,
                'persists_operational_block' => false,
                'is_reportable' => false,
            ],
            [
                'code' => 'en_armerillo',
                'name' => 'En Armerillo',
                'color' => '#7c3aed',
                'sort_order' => 50,
                'requires_modality' => false,
                'requires_attachment' => false,
                'requires_resolution_note' => false,
                'default_status' => WeaponIncident::STATUS_IN_PROGRESS,
                'sla_hours' => 24,
                'blocks_operation' => false,
                'persists_operational_block' => false,
                'is_reportable' => false,
            ],
            [
                'code' => 'dar_de_baja',
                'name' => 'Dar de Baja',
                'color' => '#4b5563',
                'sort_order' => 60,
                'requires_modality' => false,
                'requires_attachment' => false,
                'requires_resolution_note' => true,
                'default_status' => WeaponIncident::STATUS_IN_PROGRESS,
                'sla_hours' => 120,
                'blocks_operation' => true,
                'persists_operational_block' => true,
                'is_reportable' => true,
            ],
        ];

        foreach ($types as $type) {
            IncidentType::query()->updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
