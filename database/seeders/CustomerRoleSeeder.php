<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class CustomerRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create customer role if it doesn't exist
        if (! Role::where('name', 'customer')->exists()) {
            Role::create(['name' => 'customer']);
            $this->command->info('Customer role created successfully!');
        } else {
            $this->command->info('Customer role already exists.');
        }
    }
}
