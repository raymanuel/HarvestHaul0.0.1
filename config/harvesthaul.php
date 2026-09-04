<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hauling Rate Configuration
    |--------------------------------------------------------------------------
    |
    | Default hauling rates used when no per-farmer negotiated rate or
    | flat hauling rate is set. These values produce the fallback
    | price reference for route cost allocation.
    |
    */

    'hauling' => [
        'base_rate_per_km' => (float) env('HAULING_BASE_RATE_PER_KM', 15.00),
        'base_rate_per_kg' => (float) env('HAULING_BASE_RATE_PER_KG', 0.50),
        'base_trip_fee'    => (float) env('HAULING_BASE_TRIP_FEE', 250.00),
    ],

];
