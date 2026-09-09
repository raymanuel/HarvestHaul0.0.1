<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Destination;

class DestinationSeeder extends Seeder
{
    public function run(): void
    {
        $destinations = [
            // Markets
            [
                'name'      => 'General Santos Public Market',
                'address'   => 'General Santos City, South Cotabato',
                'latitude'  => 6.1108,
                'longitude' => 125.1716,
                'type'      => 'market',
            ],
            [
                'name'      => 'Polomolok Public Market',
                'address'   => 'Polomolok, South Cotabato',
                'latitude'  => 6.2167,
                'longitude' => 125.0833,
                'type'      => 'market',
            ],
            [
                'name'      => 'Tupi Public Market',
                'address'   => 'Tupi, South Cotabato',
                'latitude'  => 6.3333,
                'longitude' => 124.9833,
                'type'      => 'market',
            ],
            [
                'name'      => "T'Boli Market",
                'address'   => "T'Boli, South Cotabato",
                'latitude'  => 6.2908,
                'longitude' => 124.7394,
                'type'      => 'market',
            ],
            // Trading Posts
            [
                'name'      => 'GenSan Fish Port Complex',
                'address'   => 'General Santos City, South Cotabato',
                'latitude'  => 6.0943,
                'longitude' => 125.1779,
                'type'      => 'port',
            ],
            [
                'name'      => 'South Cotabato Trading Center',
                'address'   => 'Koronadal City, South Cotabato',
                'latitude'  => 6.5036,
                'longitude' => 124.8469,
                'type'      => 'trading_post',
            ],
            [
                'name'      => 'Dole Philippines - Polomolok',
                'address'   => 'Polomolok, South Cotabato',
                'latitude'  => 6.2089,
                'longitude' => 125.0731,
                'type'      => 'warehouse',
            ],
        ];

        foreach ($destinations as $destination) {
            Destination::firstOrCreate(
                ['name' => $destination['name']],
                $destination
            );
        }
    }
}
