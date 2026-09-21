<?php

namespace Database\Factories;

use App\Models\BuyerProfile;
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
            'business_name' => fake()->company(),
            'contact_person' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'business_address' => fake()->address(),
            'is_verified' => false,
            'status' => BuyerProfile::STATUS_PENDING,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'status' => BuyerProfile::STATUS_APPROVED,
        ]);
    }

    public function approved(): static
    {
        return $this->verified();
    }
}
