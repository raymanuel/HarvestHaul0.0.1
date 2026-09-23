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

    /*
    |--------------------------------------------------------------------------
    | Pickup Scheduling Configuration
    |--------------------------------------------------------------------------
    |
    | Service duration at each pickup stop (spec 7.8) — configurable rather
    | than hardcoded so it can be tuned per cooperative's real loading times.
    |
    */

    'pickup' => [
        'base_service_minutes'       => (float) env('PICKUP_BASE_SERVICE_MINUTES', 15),
        'large_load_service_minutes' => (float) env('PICKUP_LARGE_LOAD_SERVICE_MINUTES', 30),
        'large_load_threshold_kg'    => (float) env('PICKUP_LARGE_LOAD_THRESHOLD_KG', 2000),
    ],

    'consolidation' => [
        // Requests too far from a truck's already-assigned stops don't join
        // that group even if the weight would fit — capacity alone must not
        // decide who shares a truck. Distance is from a request's pickup
        // point to the group's centroid (ConsolidationEngine::binPackByCapacity).
        'max_cluster_radius_km' => (float) env('CONSOLIDATION_MAX_CLUSTER_RADIUS_KM', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logistics Monitoring Configuration
    |--------------------------------------------------------------------------
    */

    'logistics' => [
        // A stop with no arrival recorded this long past its planned_arrival_at
        // is flagged as delayed (DetectDelayedStops command).
        'delay_threshold_minutes' => (float) env('HAUL_DELAY_THRESHOLD_MINUTES', 20),

        // A driver's most recent GPS ping older than this is treated as
        // unreliable for "nearest available driver" sorting, not used.
        'driver_position_max_age_days' => (int) env('DRIVER_POSITION_MAX_AGE_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Weather-Aware Logistics Configuration
    |--------------------------------------------------------------------------
    |
    | Open-Meteo (free, keyless) forecast severity thresholds and the ETA
    | buffer applied when planning a trip under moderate/severe conditions.
    | Advisory only — never auto-cancels or auto-reschedules a trip.
    |
    */

    'weather' => [
        // Precipitation probability (%) / wind speed (km/h) at or above these
        // values classify the forecast as 'severe' rather than 'moderate'.
        'severe_precipitation_probability' => (float) env('WEATHER_SEVERE_PRECIPITATION_PROBABILITY', 70),
        'severe_wind_speed_kmh'            => (float) env('WEATHER_SEVERE_WIND_SPEED_KMH', 40),

        // Below these, conditions are classified 'moderate' rather than 'clear'.
        'moderate_precipitation_probability' => (float) env('WEATHER_MODERATE_PRECIPITATION_PROBABILITY', 40),
        'moderate_wind_speed_kmh'            => (float) env('WEATHER_MODERATE_WIND_SPEED_KMH', 25),

        // ETA travel-time multipliers applied in ConsolidationEngine when
        // planning a trip under these conditions. 1.0 = no change.
        'eta_buffer' => [
            'clear'    => 1.00,
            'moderate' => (float) env('WEATHER_ETA_BUFFER_MODERATE', 1.15),
            'severe'   => (float) env('WEATHER_ETA_BUFFER_SEVERE', 1.30),
        ],
    ],

];
