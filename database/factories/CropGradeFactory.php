<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CropGradeFactory extends Factory
{
    public function definition(): array
    {
        static $grades = ['Grade A', 'Grade B', 'Grade C'];
        static $i = 0;

        return [
            'name' => $grades[$i++ % count($grades)],
            'code' => strtoupper($grades[0][0]).$i,
            'sort_order' => $i,
            'is_active' => true,
        ];
    }
}
