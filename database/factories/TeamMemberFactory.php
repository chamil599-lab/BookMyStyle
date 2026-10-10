<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeamMember>
 */
class TeamMemberFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->name();
        $role = $this->faker->jobTitle();

        return [
            'name' => $name,
            'role' => $role,
            'bio' => $this->faker->paragraphs(2, true),
            // use a placeholder external image by default
            'src_url' => $this->faker->optional(0.6)->imageUrl(400, 400, 'people'),
            'upload_path' => null,
            'order' => $this->faker->numberBetween(0, 20),
            'status' => 1,
        ];
    }
}
