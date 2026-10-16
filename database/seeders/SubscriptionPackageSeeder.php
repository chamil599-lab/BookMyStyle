<?php

namespace Database\Seeders;

use App\Models\SubscriptionPackage;
use Illuminate\Database\Seeder;

class SubscriptionPackageSeeder extends Seeder
{
    public function run()
    {
        $packages = [
            [
                'name' => 'Salon Starter',
                'slug' => 'salon-starter',
                'price' => 19.99,
                'duration_days' => 30,
                'max_salons' => 1,
                'max_staff' => 3,
                'max_services' => 20,
                'analytics' => false,
                'priority_support' => false,
                'features' => ['Basic Booking', 'Email Notifications', 'Client Management'],
                'is_active' => true,
                'is_popular' => false,
                'order_column' => 1,
            ],
            [
                'name' => 'Salon Pro',
                'slug' => 'pro-salon',
                'price' => 49.99,
                'duration_days' => 30,
                'max_salons' => 3,
                'max_staff' => 10,
                'max_services' => 50,
                'analytics' => true,
                'priority_support' => true,
                'features' => ['Advanced Analytics', 'SMS Notifications', 'Staff Management', 'Marketing Tools'],
                'is_active' => true,
                'is_popular' => true, // Most popular
                'order_column' => 2,
            ],
            [
                'name' => 'Salon Elite',
                'slug' => 'salon-elite',
                'price' => 99.99,
                'duration_days' => 30,
                'max_salons' => 10,
                'max_staff' => 30,
                'max_services' => 200,
                'analytics' => true,
                'priority_support' => true,
                'features' => ['Unlimited Features', 'API Access', 'Custom Branding', 'Dedicated Support'],
                'is_active' => true,
                'is_popular' => false,
                'order_column' => 3,
            ],
        ];

        foreach ($packages as $package) {
            SubscriptionPackage::create($package);
        }
    }
}
