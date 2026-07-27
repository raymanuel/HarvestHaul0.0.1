<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CropSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('crop_varieties')->truncate();
        DB::table('crops')->truncate();
        DB::table('crop_categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $now = Carbon::now();

        // Categories and crops aligned with DA RFO12 commodity names
        // so that market price LIKE matching works on the harvest create form
        $categoryMap = [
            'Rice' => [
                'Regular Milled Rice'   => ['Regular Milled'],
                'Well Milled Rice'      => ['Well Milled'],
                'Premium Rice'          => ['Premium'],
                'Glutinous Rice'        => ['Glutinous'],
                'Jasponica Rice'        => ['Jasponica'],
                'Sinandomeng Rice'      => ['Sinandomeng'],
                'Other Special Rice'    => ['Special'],
            ],
            'Corn' => [
                'Corn'                  => ['Yellow', 'White'],
                'Corn, Yellow'          => ['Feed Grade', 'Food Grade'],
                'Corn, White'           => ['Feed Grade', 'Food Grade'],
                'Corn Grits'            => ['Yellow', 'White', 'Feed Grade'],
                'Corn Cracked'          => ['Yellow, Feed Grade'],
            ],
            'Lowland Vegetables' => [
                'Tomato'                => ['Local', 'Imported'],
                'Eggplant'              => ['Long', 'Round'],
                'String Beans'          => ['Sitaw', 'Local'],
                'Ampalaya'              => ['Local', 'Imported'],
                'Squash'                => ['Local'],
                'Okra'                  => ['Local'],
                'Bell Pepper'           => ['Green', 'Red'],
                'Chili'                 => ['Green', 'Red', 'Siling Labuyo'],
                'Bottle Gourd'          => ['Upo'],
                'Sponge Gourd'          => ['Patola'],
                'Winged Bean'           => ['Local'],
            ],
            'Highland Vegetables' => [
                'Cabbage'               => ['Repolyo', 'Local'],
                'Carrot'                => ['Local', 'Imported'],
                'Potato'                => ['Local', 'Imported'],
                'Broccoli'              => ['Local', 'Imported'],
                'Cauliflower'           => ['Local', 'Imported'],
                'Lettuce'               => ['Local', 'Imported'],
                'Celery'                => ['Local'],
                'Spring Onion'          => ['Green Onion', 'Leeks'],
                'Pechay'                => ['Local', 'Baguio'],
                'Mustard Leaves'        => ['Local'],
                'Radish'                => ['Labanos', 'Local'],
                'Chayote'               => ['Local'],
            ],
            'Root Crops' => [
                'Sweet Potato'          => ['Camote', 'Local'],
                'Cassava'               => ['Kamoteng Kahoy'],
                'Taro'                  => ['Gabi'],
            ],
            'Fruits' => [
                'Mango'                 => ['Carabao', 'Pico', 'Local'],
                'Banana'                => ['Cavendish', 'Saba', 'Bongolan'],
                'Pineapple'             => ['Smooth Cayenne', 'MD2 Gold'],
                'Papaya'                => ['Solo', 'Red Lady'],
                'Calamansi'             => ['Local'],
                'Watermelon'            => ['Local', 'Imported'],
                'Guava'                 => ['Local'],
                'Lanzones'              => ['Local'],
                'Atis'                  => ['Local'],
                'Santol'                => ['Local'],
                'Star Apple'            => ['Caimito'],
                'Durian'                => ['Local'],
                'Rambutan'              => ['Local'],
                'Avocado'               => ['Local'],
                'Dragon Fruit'          => ['Local'],
            ],
            'Spices' => [
                'Garlic'                => ['Local', 'Imported'],
                'Red Onion'             => ['Local', 'Imported'],
                'Ginger'                => ['Luya', 'Local'],
                'Turmeric'              => ['Luyang Dilaw'],
                'Lemongrass'            => ['Tanglad'],
            ],
            'Legumes' => [
                'Mung Bean'             => ['Mongo', 'Local'],
                'Peanut'                => ['Mani', 'Local'],
            ],
            'Coconut Products' => [
                'Coconut'               => ['Niyog', 'Mature'],
            ],
            'Other Crops' => [
                'Coffee'                => ['Robusta', 'Arabica'],
                'Cacao'                 => ['Local'],
                'Tobacco'               => ['Local'],
                'Rubber'                => ['Local'],
                'Sugar'                 => ['Sugarcane'],
            ],
        ];

        foreach ($categoryMap as $categoryName => $crops) {
            $categoryId = DB::table('crop_categories')->insertGetId([
                'name'       => $categoryName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($crops as $cropName => $varieties) {
                $cropId = DB::table('crops')->insertGetId([
                    'crop_category_id' => $categoryId,
                    'name'             => $cropName,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);

                foreach ($varieties as $varietyName) {
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
