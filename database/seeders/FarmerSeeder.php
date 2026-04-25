<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\FarmerProfile;
use Illuminate\Support\Facades\Hash;

class FarmerSeeder extends Seeder
{
    public function run(): void
    {
        // Mock farms scattered across General Santos City and nearby South Cotabato
        $mockFarms = [
            ['name' => 'Polomolok Pineapple Coop', 'lat' => 6.2215, 'lng' => 125.0718],
            ['name' => 'Tupi Harvests', 'lat' => 6.3333, 'lng' => 124.9416],
            ['name' => 'Lagao Fruit Farm', 'lat' => 6.1351, 'lng' => 125.1912],
            ['name' => 'Silway Veggie Patch', 'lat' => 6.1420, 'lng' => 125.1550],
            ['name' => 'Katangawan Corn Fields', 'lat' => 6.1511, 'lng' => 125.2215],
        ];

        foreach ($mockFarms as $index => $farm) {
            $user = User::create([
                'name' => $farm['name'] . ' Owner',
                'email' => "farmer{$index}@test.com",
                'password' => ('123'), // Kept your updated test password
                'role' => 'farmer',
            ]);

            FarmerProfile::create([
                'user_id' => $user->id,
                'phone' => '0912345678' . $index,
                'farm_location' => $farm['name'],
                'is_verified' => true,
                'latitude' => $farm['lat'],
                'longitude' => $farm['lng'],
            ]);
        }
    }
}
