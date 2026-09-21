<?php

namespace Tests\Unit\Services;

use App\Services\Routing\HaversineService;
use PHPUnit\Framework\TestCase;

class HaversineServiceTest extends TestCase
{
    private HaversineService $haversine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->haversine = new HaversineService();
    }

    public function test_distance_between_known_coordinates(): void
    {
        // Davao City to General Santos City — great-circle (straight-line) distance.
        $km = $this->haversine->distanceKm(7.0722, 125.6131, 6.1164, 125.1716);

        $this->assertEqualsWithDelta(117.0, $km, 2.0);
    }

    public function test_distance_to_self_is_zero(): void
    {
        $this->assertEqualsWithDelta(0.0, $this->haversine->distanceKm(7.0, 125.0, 7.0, 125.0), 0.0001);
    }

    public function test_closed_loop_km_sums_legs_back_to_start(): void
    {
        $points = [[7.0, 125.0], [7.01, 125.01], [7.02, 125.0]];

        $expected = $this->haversine->distanceKm(7.0, 125.0, 7.01, 125.01)
            + $this->haversine->distanceKm(7.01, 125.01, 7.02, 125.0)
            + $this->haversine->distanceKm(7.02, 125.0, 7.0, 125.0);

        $this->assertEqualsWithDelta($expected, $this->haversine->closedLoopKm($points), 0.0001);
    }

    public function test_estimated_minutes_scales_with_25kmh_average(): void
    {
        $this->assertEquals(24.0, $this->haversine->estimatedMinutesForKm(10.0));
    }
}
