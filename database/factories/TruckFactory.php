<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Cooperative;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Truck>
 */
class TruckFactory extends Factory
{
    protected $model = \App\Models\Truck::class;

    public function definition(): array
    {
        return [
            // trucks.cooperative_id is NOT NULL and was renamed from logistics_profile_id
            'cooperative_id'          => Cooperative::factory(),
            'truck_name'              => fake()->words(2, true),
            'plate_number'            => fake()->unique()->bothify('??-####'),
            'vehicle_type'            => fake()->randomElement(['truck', 'van', 'pickup']),
            'capacity_kg'             => fake()->randomFloat(2, 3000, 15000),
            'status'                  => 'available',
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
