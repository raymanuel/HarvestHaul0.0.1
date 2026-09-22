<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\LogisticsProfile;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LogisticsProfile>
 */
class LogisticsProfileFactory extends Factory
{
    protected $model = LogisticsProfile::class;

    public function definition(): array
    {
        return [
            'user_id'        => User::factory()->logisticsPartner(),
            'company_name'   => fake()->company(),
            'business_permit_no' => fake()->unique()->numerify('BL-#######'),
            'phone'          => fake()->numerify('09#########'),
            'office_address' => fake()->address(),
            'is_verified'    => false,
            'logistics_type' => 'cooperative',
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }
}
