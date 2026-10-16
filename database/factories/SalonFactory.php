<?php

namespace Database\Factories;

use App\Models\Salon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Salon>
 */
class SalonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company.' Salon',
            'owner_name' => $this->faker->name,
            'location' => $this->faker->city,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'description' => $this->faker->sentence(15),
            'cover_image' => 'https://picsum.photos/400/250?random='.$this->faker->unique()->numberBetween(1, 100),
        ];
    }
}
