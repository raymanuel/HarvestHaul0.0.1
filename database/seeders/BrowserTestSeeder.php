<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FarmerProfile;
use App\Models\BuyerProfile;
use App\Models\DriverHeartbeat;
use App\Models\LogisticsProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BrowserTestSeeder extends Seeder
{
    public function run(): void
    {
        $testPassword = Hash::make('Password123!');

        User::updateOrCreate(
            ['email' => 'testfarmer@example.com'],
            [
                'name' => 'Test Farmer',
                'password' => $testPassword,
                'role' => 'farmer',
                'status' => 'active',
                'email_verified_at' => now(),
                'affiliation_type' => 'independent',
            ]
        );

        User::updateOrCreate(
            ['email' => 'testbuyer@example.com'],
            [
                'name' => 'Test Buyer',
                'password' => $testPassword,
                'role' => 'buyer',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'testlogistics@example.com'],
            [
                'name' => 'Test Logistics',
                'password' => $testPassword,
                'role' => 'logistics_partner',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Ensure testfarmer has a farmer profile with GPS coordinates (Polomolok, South Cotabato)
        // and is UNVERIFIED so the admin-verification step of the E2E walkthrough is actionable.
        $farmer = User::where('email', 'testfarmer@example.com')->first();
        if ($farmer) {
            FarmerProfile::updateOrCreate(
                ['user_id' => $farmer->id],
                [
                    'latitude'         => 6.11640000,
                    'longitude'        => 125.17160000,
                    'farm_location'    => 'Purok 3, Polomolok, South Cotabato',
                    'is_verified'      => false,
                    'affiliation_type' => 'independent',
                ]
            );
        }

        // Ensure testbuyer has a buyer profile (UNVERIFIED) so the admin can approve it
        // before the buyer can start negotiations.
        $buyer = User::where('email', 'testbuyer@example.com')->first();
        if ($buyer) {
            BuyerProfile::updateOrCreate(
                ['user_id' => $buyer->id],
                [
                    'phone'       => '09170000001',
                    'is_verified' => false,
                ]
            );
        }

        $this->command->info('Browser test users created.');

        // Seed DriverHeartbeat records so auto-assign can find company drivers.
        $company = LogisticsProfile::where('logistics_type', 'company')->first();
        if ($company) {
            $companyDrivers = User::where('role', 'driver')
                ->whereHas('driverProfile', fn($q) => $q->where('partner_id', $company->id))
                ->get();
            foreach ($companyDrivers as $driver) {
                DriverHeartbeat::updateOrCreate(
                    ['driver_id' => $driver->id],
                    [
                        'logistics_profile_id' => $company->id,
                        'latitude'              => 6.1164 + ($driver->id * 0.001),
                        'longitude'             => 125.1716 + ($driver->id * 0.001),
                        'reported_at'           => now(),
                    ]
                );
            }
        }
    }
}
