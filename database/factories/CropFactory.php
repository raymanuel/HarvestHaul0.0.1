<?php

namespace Database\Factories;

use App\Models\CropCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Crop>
 */
class CropFactory extends Factory
{
    public function definition(): array
    {
        return [
            'crop_category_id' => CropCategory::factory(),
            'name' => fake()->unique()->randomElement(['Rice', 'Corn', 'Coconut', 'Banana', 'Pineapple', 'Mango', 'Avocado', 'Cacao']),
            'description' => fake()->sentence(),
            'status' => 'active',
        ];
    }
}
