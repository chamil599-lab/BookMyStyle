<?php

namespace Database\Seeders;

use App\Models\Salon;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,    // ← First create roles
            UserSeeder::class,
            SalonSeeder::class,
            ServicePagesSeeder::class,
            SubscriptionPackageSeeder::class,
                       
        ]);
    }
}
