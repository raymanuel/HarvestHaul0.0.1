<?php

namespace Database\Factories;

use App\Models\LogisticsProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerCardFactory extends Factory
{
    protected $model = \App\Models\CustomerCard::class;

    public function definition(): array
    {
        return [
            'logistics_profile_id' => LogisticsProfile::factory(),
            'name'                 => fake()->company(),
            'business_type'        => 'grocery',
            'contact'              => '09171234567',
            'address'              => fake()->address(),
            'latitude'             => 6.06,
            'longitude'            => 125.17,
        ];
    }
}