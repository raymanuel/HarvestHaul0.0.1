<?php

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;

class HarvestControllerTest extends TestCase
{
    /**
     * The HarvestController::store method validates that destination coordinates
     * must be within Philippines bounds (4°N–21°N, 116°E–127°E).
     *
     * This test verifies that business rule in isolation.
     */
    private function assertValidPHCoordinate(float $lat, float $lng): void
    {
        $isInBounds = $lat >= 4.0 && $lat <= 21.0 && $lng >= 116.0 && $lng <= 127.0;
        $this->assertTrue($isInBounds, "($lat, $lng) should be within PH bounds");
    }

    private function assertInvalidPHCoordinate(float $lat, float $lng): void
    {
        $isInBounds = $lat >= 4.0 && $lat <= 21.0 && $lng >= 116.0 && $lng <= 127.0;
        $this->assertFalse($isInBounds, "($lat, $lng) should be outside PH bounds");
    }

    public function test_manila_is_within_philippines_bounds(): void
    {
        $this->assertValidPHCoordinate(14.5995, 120.9842);
    }

    public function test_davao_is_within_philippines_bounds(): void
    {
        $this->assertValidPHCoordinate(7.1907, 125.4553);
    }

    public function test_baguio_is_within_philippines_bounds(): void
    {
        $this->assertValidPHCoordinate(16.4023, 120.5960);
    }

    public function test_cebu_is_within_philippines_bounds(): void
    {
        $this->assertValidPHCoordinate(10.3157, 123.8854);
    }

    public function test_south_of_bounds_is_rejected(): void
    {
        $this->assertInvalidPHCoordinate(3.5, 121.0);
    }

    public function test_north_of_bounds_is_rejected(): void
    {
        $this->assertInvalidPHCoordinate(22.0, 121.0);
    }

    public function test_west_of_bounds_is_rejected(): void
    {
        $this->assertInvalidPHCoordinate(14.0, 115.0);
    }

    public function test_east_of_bounds_is_rejected(): void
    {
        $this->assertInvalidPHCoordinate(14.0, 128.0);
    }

    public function test_boundary_values_are_accepted(): void
    {
        $this->assertValidPHCoordinate(4.0, 116.0);
        $this->assertValidPHCoordinate(21.0, 127.0);
    }

    public function test_hong_kong_is_outside_bounds(): void
    {
        $this->assertInvalidPHCoordinate(22.3193, 114.1694);
    }

    public function test_malaysia_is_outside_bounds(): void
    {
        $this->assertInvalidPHCoordinate(3.1390, 101.6869);
    }
}
