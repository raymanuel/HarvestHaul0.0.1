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

        User::factory()->create([
            'name' => 'Diego Lastiko Inat-inat Jr',
            'email' => 'test@example.com',
            'password' => 'pass'
        ]);

        // Second User (Ray)
        User::factory()->create([
        'name' => 'Ray Manuel Pineda',
        'email' => 'ray@mail.com',
        'password' => bcrypt('ray')
        ]);

        // 3rd User (Iver)
        User::factory()->create([
        'name' => 'Iver Jude Relox',
        'email' => 'iver@mail.com',
        'password' => bcrypt('iver')
        ]);

        // Second User (Ray)
        User::factory()->create([
        'name' => 'Elnes Jake Gabales',
        'email' => 'jake@mail.com',
        'password' => bcrypt('jake')
        ]);

        // Second User (Ray)
        User::factory()->create([
        'name' => 'Gabriel Andrei Lopez',
        'email' => 'gab@mail.com',
        'password' => bcrypt('gab')
        ]);
    }
}
