<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PackagingTypeFactory extends Factory
{
    public function definition(): array
    {
        static $names = ['Sack', 'Crate', 'Bag', 'Box', 'Bulk'];
        static $i = 0;

        return [
            'name' => $names[$i++ % count($names)].' '.$this->faker->word(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
