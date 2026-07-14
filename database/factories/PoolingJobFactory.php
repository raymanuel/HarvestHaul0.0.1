<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Truck;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PoolingJob>
 */
class PoolingJobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'logistics_profile_id' => User::factory()->logisticsPartner(),
            'truck_id' => Truck::factory(),
            'driver_id' => User::factory()->driver(),
            'buyer_id' => null,
            'status' => 'pending',
            'total_kg' => fake()->randomFloat(2, 100, 5000),
            'truck_capacity_kg' => fake()->randomFloat(2, 5000, 15000),
            'farm_count' => fake()->numberBetween(1, 5),
            'start_latitude' => fake()->latitude(7.0, 8.5),
            'start_longitude' => fake()->longitude(124.5, 126.5),
            'end_latitude' => fake()->latitude(7.0, 8.5),
            'end_longitude' => fake()->longitude(124.5, 126.5),
            'radius_km' => fake()->randomFloat(2, 5, 50),
            'notes' => fake()->sentence(),
            'price_reference' => fake()->randomFloat(2, 1000, 50000),
            'negotiated_price' => null,
            'route_geometry' => [['lat' => fake()->latitude(), 'lng' => fake()->longitude()]],
            'proposal_expires_at' => now()->addHours(48),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'confirmed_at' => now()->subHours(2),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'confirmed_at' => now()->subDays(1),
            'completed_at' => now(),
        ]);
    }
}
