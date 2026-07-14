<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Crop>
 */
class CropFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Rice', 'Corn', 'Coconut', 'Banana', 'Pineapple', 'Mango', 'Avocado', 'Cacao']),
            'description' => fake()->sentence(),
            'status' => 'active',
        ];
    }
}
