<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // admin
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@reservations.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::ADMIN)->first()->id,
        ]);

        // coordinator
        User::create([
            'name' => 'Project Coordinator',
            'email' => 'coordinator@reservations.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::COORDINATOR)->first()->id,
        ]);

        // technician
        User::create([
            'name' => 'Field Technician',
            'email' => 'technician@reservations.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::TECHNICIAN)->first()->id,
        ]);

        // seller
        User::create([
            'name' => 'Sales Agent',
            'email' => 'seller@reservations.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::SELLER)->first()->id,
        ]);

        // usuario adicional para testing
        User::create([
            'name' => 'Regular User',
            'email' => 'user@reservations.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::SELLER)->first()->id,
        ]);
    }
}