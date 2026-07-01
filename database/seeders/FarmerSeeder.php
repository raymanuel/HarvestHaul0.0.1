<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\FarmerProfile;
use App\Models\LogisticsProfile;
use Illuminate\Support\Facades\Hash;

class FarmerSeeder extends Seeder
{
    public function run(): void
    {
        $coop = LogisticsProfile::where('business_permit_no', 'BP-2026-001A')->first();

        $mockFarms = [
            [
                'name'             => 'Polomolok Pineapple Farm',
                'lat'              => 6.2215,
                'lng'              => 125.0718,
                'affiliation_type' => 'cooperative',
                'cooperative_id'   => $coop?->id,
            ],
            [
                'name'             => 'Tupi Harvests',
                'lat'              => 6.3333,
                'lng'              => 124.9416,
                'affiliation_type' => 'cooperative',
                'cooperative_id'   => $coop?->id,
            ],
            [
                'name'             => 'Lagao Fruit Farm',
                'lat'              => 6.1351,
                'lng'              => 125.1912,
                'affiliation_type' => 'cooperative',
                'cooperative_id'   => $coop?->id,
            ],
            [
                'name'             => 'Silway Veggie Patch',
                'lat'              => 6.1420,
                'lng'              => 125.1550,
                'affiliation_type' => 'independent',
                'cooperative_id'   => null,
            ],
            [
                'name'             => 'Katangawan Corn Fields',
                'lat'              => 6.1511,
                'lng'              => 125.2215,
                'affiliation_type' => 'independent',
                'cooperative_id'   => null,
            ],
        ];

        foreach ($mockFarms as $index => $farm) {
            $user = User::create([
<<<<<<< HEAD
                'name'              => $farm['name'] . ' Owner',
                'email'             => "farmer{$index}@test.com",
                'password'          => Hash::make('password'),
                'role'              => 'farmer',
                'email_verified_at' => now(),
=======
                'name'     => $farm['name'] . ' Owner',
                'email'    => "farmer{$index}@test.com",
                'password' => Hash::make('password'),
                'role'     => 'farmer',
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            ]);

            FarmerProfile::create([
                'user_id'          => $user->id,
                'phone'            => '091234567' . $index,
                'farm_location'    => $farm['name'],
                'is_verified'      => true,
                'latitude'         => $farm['lat'],
                'longitude'        => $farm['lng'],
                'affiliation_type' => $farm['affiliation_type'],
                'cooperative_id'   => $farm['cooperative_id'],
            ]);
        }
    }
}
