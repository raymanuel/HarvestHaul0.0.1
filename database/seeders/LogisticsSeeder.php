<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\LogisticsProfile;
use Illuminate\Support\Facades\Hash;

class LogisticsSeeder extends Seeder
{
    public function run(): void
    {
        $partners = [
            [
                'name'        => 'Juan Dela Cruz',
                'email'       => 'logistics1@test.com',
                'company'     => 'GenSan Farmers Cooperative',
                'phone'       => '09171234567',
                'permit'      => 'BP-2026-001A',
                'type'        => 'cooperative',
                'is_verified' => true,
            ],
            [
                'name'        => 'Maria Santos',
                'email'       => 'logistics2@test.com',
                'company'     => 'AgriLogistics Gensan',
                'phone'       => '09189876543',
                'permit'      => 'BP-2026-002B',
                'type'        => 'company',
                'is_verified' => true,
            ],
        ];

        foreach ($partners as $partner) {
            $user = User::create([
<<<<<<< HEAD
                'name'              => $partner['name'],
                'email'             => $partner['email'],
                'password'          => Hash::make('password'),
                'role'              => 'logistics_partner',
                'email_verified_at' => now(),
=======
                'name'     => $partner['name'],
                'email'    => $partner['email'],
                'password' => Hash::make('password'),
                'role'     => 'logistics_partner',
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            ]);

            LogisticsProfile::create([
                'user_id'            => $user->id,
                'company_name'       => $partner['company'],
                'phone'              => $partner['phone'],
                'business_permit_no' => $partner['permit'],
                'logistics_type'     => $partner['type'],
                'is_verified'        => $partner['is_verified'],
            ]);
        }
    }
}
