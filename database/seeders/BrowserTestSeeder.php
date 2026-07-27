<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BrowserTestSeeder extends Seeder
{
    public function run(): void
    {
        $testPassword = 'Password123!';

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

        $this->command->info('Browser test users created.');
    }
}
