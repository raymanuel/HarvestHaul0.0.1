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

        // admin User
        User::factory()->create([
<<<<<<< HEAD
            'name' => 'Admin',
            'email' => 'admin@mail.com',
            'password' => 'password123',
            'role' => 'admin'
=======
        'name' => 'Admin',
        'email' => 'admin@mail.com',
        'password' => 'password123',
        'role' => 'admin'
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
        ]);

        $this->call([
            LogisticsSeeder::class,  // must run first — farmers reference coop ID
            FarmerSeeder::class,
            DestinationSeeder::class,
            CropSeeder::class,
            DriverSeeder::class,
            TruckSeeder::class,

        ]);



    }
}
