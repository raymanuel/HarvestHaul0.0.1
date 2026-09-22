<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HaulJobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'haul_request_id' => null,
            'cooperative_id' => null,
            'delivery_personnel_id' => User::factory()->state([
                'role' => \App\Models\UserRole::DELIVERY_PERSONNEL->value,
            ]),
            'truck_id' => null,
            'pickup_date' => today()->addDays(2),
            'scheduled_at' => now()->addDays(2),
            'status' => 'scheduled',
        ];
    }
}
