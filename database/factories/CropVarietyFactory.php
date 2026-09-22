<?php

namespace Database\Factories;

use App\Models\Crop;
use Illuminate\Database\Eloquent\Factories\Factory;

class CropVarietyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'crop_id' => Crop::factory(),
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'status' => 'active',
            'price_per_kg' => $this->faker->randomFloat(2, 10, 80),
        ];
    }
}
