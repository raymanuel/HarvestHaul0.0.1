<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CropCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Root Crops', 'Fruits', 'Vegetables', 'Grains', 'Legumes', 'Spices']),
            'description' => fake()->sentence(),
            'status' => 'active',
        ];
    }
}
