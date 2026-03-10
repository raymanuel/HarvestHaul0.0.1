<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // farmer user
        User::factory()->create([
            'name' => 'Farmer A',
            'email' => 'farmera@mail.com',
            'password' => '123',
            'role' => 'farmer'
        ]);

        // admin User
        User::factory()->create([
        'name' => 'Admin',
        'email' => 'admin@mail.com',
        'password' => bcrypt('123'),
        'role' => 'admin'
        ]);

        // logistics partner user
        User::factory()->create([
        'name' => 'Logistics Partner',
        'email' => 'logistics@mail.com',
        'password' => bcrypt('123'),
        'role' => 'logistics_partner'
        ]);

        // driver user
        User::factory()->create([
        'name' => 'Driver A',
        'email' => 'driver@mail.com',
        'password' => bcrypt('123'),
        'role' => 'driver'
        ]);


    }
}
