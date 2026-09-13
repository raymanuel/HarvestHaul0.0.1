<?php

namespace Database\Factories;

use App\Models\CustomerCard;
use Illuminate\Database\Eloquent\Factories\Factory;

class OutboundOrderFactory extends Factory
{
    protected $model = \App\Models\OutboundOrder::class;

    public function definition(): array
    {
        $card = CustomerCard::factory()->create();

        return [
            'customer_card_id'     => $card->id,
            'logistics_profile_id' => $card->logistics_profile_id,
            'status'               => 'drafted',
            'total_kg'             => 0,
            'total_amount'         => 0,
        ];
    }
}