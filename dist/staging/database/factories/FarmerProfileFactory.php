<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FarmerProfile>
 */
class FarmerProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->farmer(),
            'phone' => fake()->phoneNumber(),
            'farm_location' => fake()->address(),
            'is_verified' => false,
            'latitude' => fake()->latitude(7.0, 8.5),
            'longitude' => fake()->longitude(124.5, 126.5),
            'affiliation_type' => 'independent',
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    public function cooperative(): static
    {
        return $this->state(fn (array $attributes) => [
            'affiliation_type' => 'cooperative',
        ]);
    }
}
