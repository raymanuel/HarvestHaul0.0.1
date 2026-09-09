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

        /*
        | Road-cost suggestion inputs. These only ADVISE a fair per-kg rate
        | (fuel + maintenance + driver per km, scaled by terrain). They are
        | never billed directly — billing is rate × kg, with the per-farmer
        | negotiated hauling_rate_per_kg as the source of truth (see the
        | "project" knowledge block in AGENTS.md).
        */

        // Fuel burn of a loaded light truck (≈7.4 km/L laden)
        'fuel_liters_per_km' => (float) env('HAULING_FUEL_LITERS_PER_KM', 0.135),

        // Latest diesel pump price (₱/L) per DOE Oil Price Watch
        'fuel_price_per_liter' => (float) env('HAULING_FUEL_PRICE_PER_LITER', 58.00),

        // Tires + periodic service + incidental wear
        'maintenance_cost_per_km' => (float) env('HAULING_MAINTENANCE_COST_PER_KM', 3.50),

        // Driver wages amortized over route km
        'driver_cost_per_km' => (float) env('HAULING_DRIVER_COST_PER_KM', 4.00),

        // Extra fuel & wear factor for hilly country (road shape cost ratios)
        'terrain_multipliers' => [
            'flat'        => 1.00,
            'rolling'     => 1.15,
            'mountainous' => 1.35,
        ],

        // One-line phrase stored on each job describing where its price came from.
        'suggestion_basis' => 'Road-cost estimate — DOE fuel price watch, ICCT light-truck consumption, World Bank/FHWA terrain factors',

        // Sanity-check knobs (quoted rate vs road-cost suggestion). Tune after real trips.
        'sanity' => [
            // Quoted rate above this fraction of the suggestion → "premium too big" warning
            'high_ratio'              => (float) env('HAULING_SANITY_HIGH_RATIO', 1.75),
            // Quoted rate below this fraction of the suggestion → "trip may run at a loss" warning
            'low_ratio'               => (float) env('HAULING_SANITY_LOW_RATIO', 0.55),
            // Truck loaded below this fraction of capacity → append the light-load fairness note
            'light_load_warning_ratio' => (float) env('HAULING_LIGHT_LOAD_WARNING_RATIO', 0.4),
        ],

        // Real sources behind the fuel / economy / terrain factors above.
        'suggestion_sources' => [
            [
                'label'  => 'Diesel pump price',
                'source' => 'DOE Philippines Oil Price Watch (weekly diesel pump price)',
                'url'    => 'https://www.doe.gov.ph/oil-price-watch-1',
            ],
            [
                'label'  => 'Light-truck fuel economy (≈7.4 km/L laden)',
                'source' => 'ICCT — fuel consumption standards for light commercial trucks',
                'url'    => 'https://theicct.org/',
            ],
            [
                'label'  => 'Terrain cost factors (rolling ≙ 1.15×, mountainous ≙ 1.35× flat road cost)',
                'source' => 'World Bank ROCKS & FHWA road cost studies',
                'url'    => 'https://www.worldbank.org/en/topic/transport',
            ],
        ],
    ],

];
