<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Harvest;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Negotiation>
 */
class NegotiationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'buyer_id' => User::factory()->buyer(),
            'farmer_id' => User::factory()->farmer(),
            'harvest_id' => Harvest::factory(),
            'negotiated_price' => null,
            'negotiated_volume' => null,
            'status' => 'OPEN',
            'last_activity_at' => now(),
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'OPEN',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'COMPLETED',
            'negotiated_price' => fake()->randomFloat(2, 1000, 50000),
            'negotiated_volume' => fake()->randomFloat(2, 100, 5000),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'CLOSED',
        ]);
    }
}
