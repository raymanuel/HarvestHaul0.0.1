<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FarmerProfile;
use App\Models\LogisticsProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CoopFlowTestSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('harvest2026');

        $coop = User::updateOrCreate(
            ['email' => 'rvymnl@gmail.com'],
            [
                'name'              => 'rvymnl',
                'password'          => $password,
                'role'              => 'logistics_partner',
                'status'            => 'active',
                'email_verified_at' => now(),
                'affiliation_type'  => 'cooperative',
                'cooperative_id'    => null,
            ]
        );

        LogisticsProfile::updateOrCreate(
            ['user_id' => $coop->id],
            [
                'company_name'           => 'RVY Cooperative',
                'business_permit_no'     => 'BP-0000-RVY',
                'cda_registration_no'    => 'CDA-0000-RVY',
                'phone'                  => '09170000000',
                'is_verified'            => true,
                'business_permit_verified' => true,
                'logistics_type'         => 'cooperative',
            ]
        );

        $farmers = [
            'reloxiver25@gmail.com',
            'rayvenous24@gmail.com',
            'pineda.raymanuel@gmail.com',
        ];

        foreach ($farmers as $email) {
            $farmer = User::updateOrCreate(
                ['email' => $email],
                [
                    'name'              => explode('@', $email)[0],
                    'password'          => $password,
                    'role'              => 'farmer',
                    'status'            => 'active',
                    'email_verified_at' => now(),
                    'affiliation_type'  => 'independent',
                    'cooperative_id'    => null,
                ]
            );

            FarmerProfile::updateOrCreate(
                ['user_id' => $farmer->id],
                [
                    'phone'              => '09170000000',
                    'is_verified'        => true,
                    'affiliation_type'   => 'independent',
                    'cooperative_id'     => null,
                    'membership_status'  => null,
                ]
            );
        }

        $this->command->info('Coop flow test accounts created (rvymnl coop + 3 independent farmers).');
    }
}