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
        // Mock Logistics Companies
        $partners = [
            [
                'name' => 'Juan Dela Cruz',
                'email' => 'partner1@test.com',
                'company' => 'FastHaul Mindanao',
                'phone' => '09171234567',
                'permit' => 'BP-2026-001A'
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'partner2@test.com',
                'company' => 'AgriLogistics Gensan',
                'phone' => '09189876543',
                'permit' => 'BP-2026-002B'
            ],
        ];

        foreach ($partners as $partner) {
            // 1. Create the base User account
            $user = User::create([
                'name' => $partner['name'],
                'email' => $partner['email'],
                'password' => Hash::make('password123'), // Standard testing password
                'role' => 'logistics_partner',
            ]);

            // 2. Create the associated Logistics Profile
            LogisticsProfile::create([
                'user_id' => $user->id,
                'company_name' => $partner['company'],
                'phone' => $partner['phone'],
                'business_permit_no' => $partner['permit'],
            ]);
        }
    }
}
