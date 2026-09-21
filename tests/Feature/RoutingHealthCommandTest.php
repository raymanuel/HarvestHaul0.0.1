<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoutingHealthCommandTest extends TestCase
{
    public function test_reports_success_when_osrm_reachable(): void
    {
        Http::fake([
            'router.project-osrm.org/table/*' => Http::response(['code' => 'Ok', 'durations' => [[0]], 'distances' => [[0]]]),
            'router.project-osrm.org/route/*' => Http::response(['code' => 'Ok', 'routes' => [['distance' => 1, 'duration' => 1]]]),
        ]);

        $this->artisan('routing:health')->assertExitCode(0);
    }

    public function test_reports_failure_when_osrm_unreachable(): void
    {
        Http::fake(['router.project-osrm.org/*' => Http::response([], 500)]);

        $this->artisan('routing:health')->assertExitCode(1);
    }
}
