<?php

namespace Database\Factories;

use App\Models\HaulJob;
use App\Models\HaulRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class HaulJobStopFactory extends Factory
{
    public function definition(): array
    {
        return [
            'haul_job_id' => HaulJob::factory(),
            'haul_request_id' => HaulRequest::factory(),
            'sequence_no' => 1,
            'status' => 'pending',
            'planned_arrival_at' => null,
            'actual_arrival_at' => null,
            'picked_up_at' => null,
        ];
    }
}