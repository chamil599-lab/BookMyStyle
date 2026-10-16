<?php

namespace Database\Seeders;

use App\Models\Salon;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SalonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, create or get a user to be the salon owner
        $owner = User::firstOrCreate(
            ['email' => 'salonowner@example.com'],
            [
                'name' => 'Salon Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create sample salons
        $salons = [
            [
                'name' => 'Glamour Beauty Salon',
                'owner_name' => 'Sarah Johnson',
                'location' => '123 Main Street, Colombo 05',
                'phone' => '+94112345678',
                'email' => 'info@glamourbeauty.com',
                'open_time' => '09:00',
                'close_time' => '19:00',
                'description' => 'Experience luxury beauty treatments in the heart of Colombo. Our expert team provides premium hair, skin, and nail services using the finest products.',
                'cover_image' => 'salons/glamour-salon.jpg',
                'number_of_staff' => 8,
                'facebook_page' => 'https://facebook.com/glamourbeautysalon',
                'instagram_page' => 'https://instagram.com/glamourbeautysalon',
                'tiktok_page' => 'https://tiktok.com/@glamourbeauty',
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ],
            [
                'name' => 'Elite Hair Studio',
                'owner_name' => 'Michael Brown',
                'location' => '456 Galle Road, Colombo 03',
                'phone' => '+94119876543',
                'email' => 'hello@elitehairstudio.com',
                'open_time' => '08:00',
                'close_time' => '20:00',
                'description' => 'Premier hair salon specializing in cutting-edge hairstyles, coloring, and hair treatments. Transform your look with our skilled stylists.',
                'cover_image' => 'salons/elite-hair.jpg',
                'number_of_staff' => 6,
                'facebook_page' => 'https://facebook.com/elitehairstudio',
                'instagram_page' => 'https://instagram.com/elitehairstudio',
                'tiktok_page' => 'https://tiktok.com/@elitehair',
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ],
            [
                'name' => 'Royal Spa & Wellness',
                'owner_name' => 'Emma Wilson',
                'location' => '789 Union Place, Colombo 02',
                'phone' => '+94111234567',
                'email' => 'relax@royalspa.com',
                'open_time' => '10:00',
                'close_time' => '22:00',
                'description' => 'Luxurious spa offering rejuvenating treatments, massages, and wellness services. Escape the city hustle and find your inner peace.',
                'cover_image' => 'salons/royal-spa.jpg',
                'number_of_staff' => 12,
                'facebook_page' => 'https://facebook.com/royalspawellness',
                'instagram_page' => 'https://instagram.com/royalspawellness',
                'tiktok_page' => 'https://tiktok.com/@royalspa',
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ],
            [
                'name' => 'Modern Cuts Barbershop',
                'owner_name' => 'David Chen',
                'location' => '321 Baseline Road, Colombo 04',
                'phone' => '+94117654321',
                'email' => 'book@moderncuts.com',
                'open_time' => '08:30',
                'close_time' => '18:30',
                'description' => 'Contemporary barbershop offering precision cuts, beard grooming, and traditional shaving services for the modern gentleman.',
                'cover_image' => 'salons/modern-cuts.jpg',
                'number_of_staff' => 5,
                'facebook_page' => 'https://facebook.com/moderncutsbarbershop',
                'instagram_page' => 'https://instagram.com/moderncuts',
                'tiktok_page' => 'https://tiktok.com/@moderncuts',
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ],
            [
                'name' => 'Bliss Nail Lounge',
                'owner_name' => 'Lisa Garcia',
                'location' => '654 Havelock Road, Colombo 05',
                'phone' => '+94113456789',
                'email' => 'hello@blissnails.com',
                'open_time' => '09:30',
                'close_time' => '19:30',
                'description' => 'Specialized nail salon offering creative nail art, gel manicures, pedicures, and premium nail care services in a relaxing environment.',
                'cover_image' => 'salons/bliss-nails.jpg',
                'number_of_staff' => 4,
                'facebook_page' => 'https://facebook.com/blissnaillounge',
                'instagram_page' => 'https://instagram.com/blissnaillounge',
                'tiktok_page' => 'https://tiktok.com/@blissnails',
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ],
        ];

        foreach ($salons as $salonData) {
            Salon::create($salonData);
        }

        $this->command->info('Salons seeded successfully!');
    }
}
