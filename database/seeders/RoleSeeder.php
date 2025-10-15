<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => Role::ADMIN, 'description' => 'Administrator with full access'],
            ['name' => Role::COORDINATOR, 'description' => 'Coordinate reservations and teams'],
            ['name' => Role::TECHNICIAN, 'description' => 'Execute installations and maintenance'],
            ['name' => Role::SELLER, 'description' => 'Create and manage sales reservations'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}