<?php

namespace Database\Factories;

use App\Models\Cooperative;
use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DriverProfile>
 */
class DriverProfileFactory extends Factory
{
    protected $model = DriverProfile::class;

    public function definition(): array
    {
        return [
            'user_id'            => User::factory()->driver(),
            'cooperative_id'     => Cooperative::factory(),
            'license_no'         => fake()->unique()->bothify('DL-########'),
            'employment_status'  => 'active',
            'is_verified'        => true,
            'agreement_effective_date' => fake()->date(),
            'agreement_expiry_date'    => fake()->date(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attr) => ['is_verified' => false]);
    }
}
