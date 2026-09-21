<?php

return [
    'provider' => env('ROUTING_PROVIDER', 'osrm'),

    'osrm' => [
        'base_url' => env('OSRM_BASE_URL', 'https://router.project-osrm.org'),
        'profile' => env('OSRM_PROFILE', 'driving'),
        'timeout' => (int) env('OSRM_TIMEOUT', 10),
        'retry_times' => (int) env('OSRM_RETRY_TIMES', 2),
        'retry_sleep' => (int) env('OSRM_RETRY_SLEEP', 500),
        // Minimum gap between live requests to the shared public OSRM
        // instance. A cache hit never waits on this. 0 disables throttling
        // (set in phpunit.xml so the test suite never sleeps).
        'min_interval_ms' => (int) env('OSRM_MIN_INTERVAL_MS', 300),
    ],

    // How long a cached table()/route() result stays valid. Farmer pickup
    // coordinates rarely change, so this is generous by design (spec:
    // "Route Calculation Cache" — reuse the matrix for the same candidate
    // set instead of re-hitting the public OSRM instance every page load).
    'cache_ttl_minutes' => (int) env('OSRM_CACHE_TTL_MINUTES', 360),
];
