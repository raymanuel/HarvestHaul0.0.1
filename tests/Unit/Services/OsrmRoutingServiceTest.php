<?php

namespace Tests\Unit\Services;

use App\Models\RouteCalculation;
use App\Services\Routing\HaversineService;
use App\Services\Routing\OsrmRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OsrmRoutingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): OsrmRoutingService
    {
        return new OsrmRoutingService(new HaversineService());
    }

    public function test_table_uses_configured_base_url_not_hardcoded(): void
    {
        config(['routing.osrm.base_url' => 'https://custom-osrm.example.test']);

        Http::fake([
            'custom-osrm.example.test/table/*' => Http::response([
                'code' => 'Ok', 'durations' => [[0, 100], [100, 0]], 'distances' => [[0, 1000], [1000, 0]],
            ]),
        ]);

        $result = $this->service()->table([[7.0, 125.0], [7.01, 125.01]]);

        $this->assertEquals('osrm', $result['source']);
        Http::assertSent(fn ($req) => str_starts_with($req->url(), 'https://custom-osrm.example.test/table/'));
    }

    public function test_table_falls_back_to_advisory_on_failure(): void
    {
        Http::fake(['router.project-osrm.org/*' => Http::response([], 500)]);

        $result = $this->service()->table([[7.0, 125.0], [7.01, 125.01]]);

        $this->assertEquals('advisory', $result['source']);
        $this->assertNull($result['durations']);
        $this->assertGreaterThan(0, $result['total_km']);
    }

    public function test_table_result_is_cached_and_second_call_skips_http(): void
    {
        Http::fake([
            'router.project-osrm.org/table/*' => Http::response([
                'code' => 'Ok', 'durations' => [[0, 100], [100, 0]], 'distances' => [[0, 1000], [1000, 0]],
            ]),
        ]);

        $points = [[7.0, 125.0], [7.01, 125.01]];
        $first = $this->service()->table($points);
        $this->assertDatabaseCount('route_calculations', 1);

        Http::fake(['router.project-osrm.org/*' => Http::response([], 500)]);
        $second = $this->service()->table($points);

        $this->assertEquals($first['total_km'], $second['total_km']);
        $this->assertEquals('osrm', $second['source']);
    }

    public function test_advisory_fallback_is_not_cached(): void
    {
        Http::fake(['router.project-osrm.org/*' => Http::response([], 500)]);

        $this->service()->table([[7.0, 125.0], [7.01, 125.01]]);

        $this->assertDatabaseCount('route_calculations', 0);
    }

    public function test_route_returns_null_on_failure_without_throwing(): void
    {
        Http::fake(['router.project-osrm.org/*' => Http::response([], 500)]);

        $result = $this->service()->route([[7.0, 125.0], [7.01, 125.01]]);

        $this->assertNull($result);
    }

    public function test_route_success_is_cached(): void
    {
        Http::fake([
            'router.project-osrm.org/route/*' => Http::response([
                'code' => 'Ok',
                'routes' => [['distance' => 5000, 'duration' => 600, 'geometry' => ['coordinates' => [[125.0, 7.0], [125.01, 7.01]]]]],
            ]),
        ]);

        $points = [[7.0, 125.0], [7.01, 125.01]];
        $this->service()->route($points);

        $this->assertDatabaseHas('route_calculations', ['kind' => 'route']);
    }

    public function test_expired_cache_entry_is_not_reused(): void
    {
        RouteCalculation::create([
            'provider' => 'osrm', 'profile' => 'driving', 'kind' => 'table',
            'coordinates_hash' => hash('sha256', '125,7;125.01,7.01'),
            'result' => ['source' => 'osrm', 'durations' => [[0, 1]], 'distances' => [[0, 1]], 'total_min' => 1, 'total_km' => 1],
            'calculated_at' => now()->subDay(),
            'expires_at' => now()->subHour(),
        ]);

        Http::fake([
            'router.project-osrm.org/table/*' => Http::response([
                'code' => 'Ok', 'durations' => [[0, 200], [200, 0]], 'distances' => [[0, 2000], [2000, 0]],
            ]),
        ]);

        $result = $this->service()->table([[7.0, 125.0], [7.01, 125.01]]);

        $this->assertEquals(2.0, $result['total_km']);
    }
}
