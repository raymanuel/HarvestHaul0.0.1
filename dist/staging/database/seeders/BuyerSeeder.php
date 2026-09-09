<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class BuyerSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'              => 'Mindanao Fresh Trading',
            'email'             => 'buyer@test.com',
            'password'          => Hash::make('password'),
            'role'              => 'buyer',
            'email_verified_at' => now(),
        ]);
    }
}
