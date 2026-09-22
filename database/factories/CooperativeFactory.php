<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Cooperative;
use App\Models\User;
use App\Models\UserRole;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cooperative>
 */
class CooperativeFactory extends Factory
{
    protected $model = Cooperative::class;

    public function definition(): array
    {
        return [
            'name'              => fake()->company(),
            'type'              => 'primary',
            'province'          => fake()->state(),
            'city'              => fake()->city(),
            'municipality'      => fake()->city(),
            'barangay'          => fake()->streetName(),
            'contact_number'    => fake()->numerify('09#########'),
            'official_email'    => fake()->unique()->safeEmail(),
            'year_established'  => fake()->numberBetween(1990, 2023),
            'status'            => Cooperative::STATUS_APPROVED,
            'coop_admin_user_id'=> User::factory()->create([
                'role' => UserRole::COOP_ADMIN->value,
            ])->id,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Cooperative::STATUS_APPROVED,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Cooperative::STATUS_PENDING,
        ]);
    }
}
