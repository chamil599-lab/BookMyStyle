<?php

namespace Database\Seeders;

use App\Models\User;
use App\RolesEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
        ])->assignRole(RolesEnum::ADMIN->value);

        User::factory()->create([
            'name' => 'Owner',
            'email' => 'owner@gmail.com',
        ])->assignRole(RolesEnum::OWNER->value);

        // Add the second owner
        User::factory()->create([
            'name' => 'Owner2',
            'email' => 'owner2@gmail.com',
        ])->assignRole(RolesEnum::OWNER->value);

        User::factory()->create([
            'name' => 'User',
            'email' => 'user@gmail.com',
        ])->assignRole(RolesEnum::USER->value);
    }
}
