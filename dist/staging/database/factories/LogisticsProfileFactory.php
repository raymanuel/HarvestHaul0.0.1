<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LogisticsProfile>
 */
class LogisticsProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->logisticsPartner(),
            'company_name' => fake()->company(),
            'business_permit_no' => fake()->bothify('??-########'),
            'cda_registration_no' => null,
            'phone' => fake()->phoneNumber(),
            'is_verified' => false,
            'logistics_type' => 'company',
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    public function cooperative(): static
    {
        return $this->state(fn (array $attributes) => [
            'logistics_type' => 'cooperative',
            'cda_registration_no' => fake()->bothify('??-########'),
        ]);
    }
}
