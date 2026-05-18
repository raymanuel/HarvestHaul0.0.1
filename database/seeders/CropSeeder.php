<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CropSeeder extends Seeder
{
    /**
     * Run the database seeds for High-Value Crops and Categories specific to GenSan, Polomolok, and Tupi.
     */
    public function run(): void
    {
        // Disable foreign key constraints temporarily to allow safe truncation updates
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('crop_varieties')->truncate();
        DB::table('crops')->truncate();
        DB::table('crop_categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $now = Carbon::now();

        // Structural crop data nested by Category to fulfill database foreign key constraints
        $categoryMap = [
            'Fruits' => [
                'Mango' => ['Carabao (Export Grade)', 'Pico'],
                'Pineapple' => ['Smooth Cayenne', 'MD2 Gold'],
                'Papaya' => ['Solo Papaya', 'Red Lady'],
                'Banana' => ['Cavendish', 'Saba', 'Bongolan'],
            ],
            'Vegetables' => [
                'Asparagus' => ['UC 157', 'Jersey Deluxe'],
                'Pumpkin' => ['Suprema F1', 'Rizal Smooth'],
            ],
            'Specialty Crops' => [
                'Coffee' => ['Robusta', 'Arabica']
            ]
        ];

        // Process execution loops maintaining relational hierarchical integrity
        foreach ($categoryMap as $categoryName => $crops) {
            // 1. Seed Category and capture the generated primary key index ID
            $categoryId = DB::table('crop_categories')->insertGetId([
                'name'       => $categoryName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($crops as $cropName => $varieties) {
                // 2. Seed Crop using the mapped category foreign key dependency
                $cropId = DB::table('crops')->insertGetId([
                    'crop_category_id' => $categoryId,
                    'name'             => $cropName,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);

                foreach ($varieties as $varietyName) {
                    // 3. Seed Variety matching the parent crop tracking record
                    DB::table('crop_varieties')->insert([
                        'crop_id'    => $cropId,
                        'name'       => $varietyName,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}
