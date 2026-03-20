<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\ResponsibilityLevel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $positions = Position::pluck('id', 'name');
        $levels = ResponsibilityLevel::pluck('id', 'level');
        $password = env('SEED_ADMIN_PASSWORD');

        if (!is_string($password) || trim($password) === '') {
            throw new RuntimeException('Define SEED_ADMIN_PASSWORD antes de ejecutar AdminUserSeeder.');
        }

        $admins = [
            [
                'name' => 'Wilder Rivera',
                'email' => 'wilder.rivera@example.com',
                'role' => 'ADMIN',
                'position' => 'Gerencia General',
                'responsibility_level' => null,
                'is_active' => true,
                'cost_center' => 'CC-1001',
            ],
            [
                'name' => 'Andres San Miguel',
                'email' => 'andres.sanmiguel@example.com',
                'role' => 'ADMIN',
                'position' => 'Gerencia General',
                'responsibility_level' => null,
                'is_active' => true,
                'cost_center' => 'CC-1002',
            ],
        ];

        foreach ($admins as $admin) {
            User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => Hash::make($password),
                    'role' => $admin['role'],
                    'position_id' => $positions[$admin['position']] ?? null,
                    'responsibility_level_id' => $levels[$admin['responsibility_level']] ?? null,
                    'is_active' => $admin['is_active'],
                    'cost_center' => $admin['cost_center'],
                ]
            );
        }
    }
}
