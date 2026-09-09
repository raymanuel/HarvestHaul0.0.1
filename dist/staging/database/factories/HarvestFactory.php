<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Crop;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Harvest>
 */
class HarvestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->farmer(),
            'crop_id' => Crop::factory(),
            'quantity_kg' => fake()->randomFloat(2, 10, 5000),
            'unit' => 'kg',
            'status' => 'active',
            'notes' => fake()->sentence(),
            'harvest_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'quality_grade' => fake()->randomElement(['A', 'B', 'C']),
            'packaging_type' => fake()->randomElement(['sack', 'box', 'crate']),
            'latitude' => fake()->latitude(7.0, 8.5),
            'longitude' => fake()->longitude(124.5, 126.5),
            'destination_address' => fake()->address(),
            'destination_latitude' => fake()->latitude(7.0, 8.5),
            'destination_longitude' => fake()->longitude(124.5, 126.5),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'assigned',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
