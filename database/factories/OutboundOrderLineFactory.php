<?php

namespace Database\Factories;

use App\Models\OutboundOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class OutboundOrderLineFactory extends Factory
{
    protected $model = \App\Models\OutboundOrderLine::class;

    public function definition(): array
    {
        $qty  = fake()->randomFloat(2, 10, 500);
        $rate = fake()->randomFloat(2, 10, 100);

        return [
            'outbound_order_id' => OutboundOrder::factory(),
            'crop_type'         => fake()->randomElement(['Banana', 'Coconut', 'Pineapple']),
            'quantity_kg'       => $qty,
            'rate_per_kg'       => $rate,
            'subtotal'          => function (array $attributes) {
                return round((float) $attributes['quantity_kg'] * (float) $attributes['rate_per_kg'], 2);
            },
        ];
    }
}