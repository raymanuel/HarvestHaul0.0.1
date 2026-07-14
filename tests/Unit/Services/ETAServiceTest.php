<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ETAService;

class ETAServiceTest extends TestCase
{
    private function callPrivateMethod(object $object, string $methodName, array $params = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $params);
    }

    public function test_haversine_returns_zero_for_identical_coordinates(): void
    {
        $service = new ETAService();
        $distance = $this->callPrivateMethod($service, 'haversine', [16.5, 121.5, 16.5, 121.5]);

        $this->assertEquals(0.0, $distance);
    }

    public function test_haversine_manila_to_quezon_city(): void
    {
        $service = new ETAService();
        $distance = $this->callPrivateMethod($service, 'haversine', [14.5995, 120.9842, 14.6760, 121.0437]);

        $this->assertEqualsWithDelta(10.3, $distance, 0.5);
    }

    public function test_haversine_manila_to_davao(): void
    {
        $service = new ETAService();
        $distance = $this->callPrivateMethod($service, 'haversine', [14.5995, 120.9842, 7.1907, 125.4553]);

        $this->assertEqualsWithDelta(1000, $distance, 50);
    }

    public function test_haversine_manila_to_baguio(): void
    {
        $service = new ETAService();
        $distance = $this->callPrivateMethod($service, 'haversine', [14.5995, 120.9842, 16.4023, 120.5960]);

        $this->assertEqualsWithDelta(205, $distance, 5);
    }

    public function test_haversine_commutative(): void
    {
        $service = new ETAService();
        $lat1 = 10.0; $lng1 = 120.0;
        $lat2 = 12.0; $lng2 = 122.0;

        $d1 = $this->callPrivateMethod($service, 'haversine', [$lat1, $lng1, $lat2, $lng2]);
        $d2 = $this->callPrivateMethod($service, 'haversine', [$lat2, $lng2, $lat1, $lng1]);

        $this->assertEqualsWithDelta($d1, $d2, 0.0001);
    }

    public function test_service_constants_are_defined(): void
    {
        $reflection = new \ReflectionClass(ETAService::class);

        $this->assertArrayHasKey('EARTH_RADIUS_KM', $reflection->getConstants());
        $this->assertArrayHasKey('DEFAULT_SPEED_KMH', $reflection->getConstants());
        $this->assertArrayHasKey('TERRAIN_SPEED_MULTIPLIER', $reflection->getConstants());
    }

    // ─────────────────────────────────────────────────────────
    // Confidence scoring
    // ─────────────────────────────────────────────────────────

    public function test_calculate_speed_stability_identical_speeds(): void
    {
        $service = new ETAService();
        $records = collect([
            (object) ['speed_kmh' => 40],
            (object) ['speed_kmh' => 40],
            (object) ['speed_kmh' => 40],
        ]);
        $stability = $this->callPrivateMethod($service, 'calculateSpeedStability', [$records]);

        $this->assertEquals(1.0, $stability);
    }

    public function test_calculate_speed_stability_variable_speeds(): void
    {
        $service = new ETAService();
        $records = collect([
            (object) ['speed_kmh' => 30],
            (object) ['speed_kmh' => 50],
            (object) ['speed_kmh' => 40],
        ]);
        $stability = $this->callPrivateMethod($service, 'calculateSpeedStability', [$records]);

        $this->assertGreaterThan(0.5, $stability);
        $this->assertLessThan(1.0, $stability);
    }

    public function test_calculate_speed_stability_high_variance(): void
    {
        $service = new ETAService();
        $records = collect([
            (object) ['speed_kmh' => 5],
            (object) ['speed_kmh' => 80],
            (object) ['speed_kmh' => 10],
        ]);
        $stability = $this->callPrivateMethod($service, 'calculateSpeedStability', [$records]);

        $this->assertLessThan(0.5, $stability);
    }

    public function test_calculate_speed_stability_fewer_than_two_records(): void
    {
        $service = new ETAService();
        $records = collect([
            (object) ['speed_kmh' => 40],
        ]);
        $stability = $this->callPrivateMethod($service, 'calculateSpeedStability', [$records]);

        $this->assertEquals(0.5, $stability);
    }

    public function test_calculate_speed_stability_all_zero_speeds(): void
    {
        $service = new ETAService();
        $records = collect([
            (object) ['speed_kmh' => 0],
            (object) ['speed_kmh' => 0],
            (object) ['speed_kmh' => 0],
        ]);
        $stability = $this->callPrivateMethod($service, 'calculateSpeedStability', [$records]);

        $this->assertEquals(0.5, $stability);
    }

    public function test_calculate_speed_stability_empty(): void
    {
        $service = new ETAService();
        $stability = $this->callPrivateMethod($service, 'calculateSpeedStability', [collect()]);

        $this->assertEquals(0.5, $stability);
    }
}
