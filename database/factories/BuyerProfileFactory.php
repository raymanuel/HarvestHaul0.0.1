<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BuyerProfile>
 */
class BuyerProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->buyer(),
            'phone' => fake()->phoneNumber(),
            'is_verified' => false,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }
}
