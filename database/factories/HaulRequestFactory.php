<?php

namespace Database\Factories;

use App\Models\Crop;
use App\Models\PackagingType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HaulRequestFactory extends Factory
{
    public function definition(): array
    {
        $sacks = $this->faker->numberBetween(1, 80);
        $weight = $sacks * 50;

        return [
            'farmer_id' => User::factory()->farmer(),
            'crop_id' => Crop::factory(),
            'crop_variety_id' => null,
            'packaging_type_id' => PackagingType::factory(),
            'estimated_sacks' => $sacks,
            'estimated_weight_kg' => $weight,
            'harvest_date' => today(),
            'preferred_pickup_date' => today()->addDays(2),
            'pickup_window_start' => '08:00:00',
            'pickup_window_end' => '10:00:00',
            'pickup_location' => $this->faker->streetAddress(),
            'pickup_location_lat' => $this->faker->latitude(6.5, 7.5),
            'pickup_location_lng' => $this->faker->longitude(124.5, 126.0),
            'status' => 'pending',
        ];
    }
}
