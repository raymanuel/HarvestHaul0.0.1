<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Truck>
 */
class TruckFactory extends Factory
{
    public function definition(): array
    {
        return [
            'logistics_profile_id' => User::factory()->logisticsPartner(),
            'plate_number' => fake()->unique()->bothify('??-####'),
            'capacity_kg' => fake()->randomFloat(2, 3000, 15000),
            'status' => 'available',
            'vehicle_type' => fake()->randomElement(['truck', 'van', 'pickup']),
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'available',
        ]);
    }

    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'assigned',
        ]);
    }
}
