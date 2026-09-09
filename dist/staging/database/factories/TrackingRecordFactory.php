<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PoolingJob;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrackingRecord>
 */
class TrackingRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pooling_job_id' => PoolingJob::factory(),
            'driver_id' => User::factory()->driver(),
            'latitude' => fake()->latitude(7.0, 8.5),
            'longitude' => fake()->longitude(124.5, 126.5),
            'speed_kmh' => fake()->randomFloat(2, 0, 100),
            'bearing' => fake()->randomFloat(2, 0, 360),
            'accuracy_meters' => fake()->randomFloat(2, 1, 50),
            'posted_at' => now(),
        ];
    }
}
