<?php

namespace Tests\Unit\Services;

use App\Services\HaulingRateCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HaulingRateCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['harvesthaul.hauling' => [
            'base_rate_per_km'        => 15.00,
            'base_rate_per_kg'        => 0.50,
            'base_trip_fee'           => 250.00,
            'fuel_liters_per_km'      => 0.135,
            'fuel_price_per_liter'    => 58.00,
            'maintenance_cost_per_km' => 3.50,
            'driver_cost_per_km'      => 4.00,
            'terrain_multipliers'     => ['flat' => 1.00, 'rolling' => 1.15, 'mountainous' => 1.35],
            'suggestion_sources'      => [],
            'suggestion_basis'        => 'Road-cost estimate — test sources',
            'sanity'                  => ['high_ratio' => 1.75, 'low_ratio' => 0.55, 'light_load_warning_ratio' => 0.4],
        ]]);
    }

    public function test_suggest_computes_expected_values_for_flat_terrain(): void
    {
        // costPerKm = 0.135×58 + 3.5 + 4.0 = 15.33; trip = 15.33×100×1.0 + 250 = 1783
        $result = (new HaulingRateCalculator())->suggest(100.0, 1000.0, 'flat');

        $this->assertEqualsWithDelta(1783.00, $result['trip_cost'], 0.01);
        $this->assertEqualsWithDelta(1.78, $result['rate_per_kg'], 0.01);
        $this->assertEqualsWithDelta(15.33, $result['cost_per_km'], 0.01);
        $this->assertEquals(100.0, $result['road_km']);
        $this->assertEquals('flat', $result['terrain']);
    }

    public function test_suggest_scales_trip_cost_with_terrain(): void
    {
        $flat = (new HaulingRateCalculator())->suggest(100.0, 1000.0, 'flat')['trip_cost'];
        $rolling = (new HaulingRateCalculator())->suggest(100.0, 1000.0, 'rolling')['trip_cost'];
        $mountainous = (new HaulingRateCalculator())->suggest(100.0, 1000.0, 'mountainous')['trip_cost'];

        $this->assertGreaterThan($flat, $rolling, 'Rolling terrain must cost more than flat');
        $this->assertGreaterThan($rolling, $mountainous, 'Mountainous terrain must cost more than rolling');
    }

    public function test_suggest_returns_null_rate_when_no_load(): void
    {
        $result = (new HaulingRateCalculator())->suggest(100.0, 0.0, 'flat');
        $this->assertNull($result['rate_per_kg']);
        $this->assertEqualsWithDelta(1783.00, $result['trip_cost'], 0.01);
    }

    public function test_sanity_warns_when_rate_is_way_below_suggestion(): void
    {
        $result = HaulingRateCalculator::sanity(0.70, 1.78);
        $this->assertNotNull($result, 'A 0.70 rate vs 1.78 suggestion must be flagged');
        $this->assertEquals('warning', $result['level']);
        $this->assertStringContainsString('loss', $result['message']);
    }

    public function test_sanity_warns_when_rate_is_way_above_suggestion(): void
    {
        $result = HaulingRateCalculator::sanity(3.60, 1.78);
        $this->assertNotNull($result);
        $this->assertEquals('warning', $result['level']);
    }

    public function test_sanity_warns_when_no_rate_is_set(): void
    {
        $result = HaulingRateCalculator::sanity(null, 1.78);
        $this->assertNotNull($result);
        $this->assertEquals('warning', $result['level']);
        $this->assertStringContainsString('No hauling rate', $result['message']);
    }

    public function test_sanity_returns_null_for_healthy_rate(): void
    {
        $result = HaulingRateCalculator::sanity(1.60, 1.78);
        $this->assertNull($result, 'A rate within a healthy band of the suggestion needs no banner');
    }

    public function test_sanity_returns_null_without_suggestion(): void
    {
        $this->assertNull(HaulingRateCalculator::sanity(1.50, null));
        $this->assertNull(HaulingRateCalculator::sanity(null, null));
    }

    public function test_sanity_honours_configured_high_threshold(): void
    {
        config(['harvesthaul.hauling.sanity.high_ratio' => 1.20]);
        // 2.67/1.78 = 1.50 × suggestion — healthy under the default 1.75, flagged under the strict 1.20.
        $result = HaulingRateCalculator::sanity(2.67, 1.78);
        $this->assertNotNull($result);
        $this->assertEquals('warning', $result['level']);
    }

    public function test_sanity_honours_configured_low_threshold(): void
    {
        config(['harvesthaul.hauling.sanity.low_ratio' => 0.90]);
        // 1.60/1.78 = 0.90 × suggestion — healthy under the default 0.55, flagged under the strict 0.90.
        $result = HaulingRateCalculator::sanity(1.60, 1.78);
        $this->assertNotNull($result);
        $this->assertEquals('warning', $result['level']);
    }

    public function test_sanity_appends_light_load_note_when_truck_is_light(): void
    {
        $result = HaulingRateCalculator::sanity(0.70, 1.78, 0.30);
        $this->assertNotNull($result);
        $this->assertStringContainsStringIgnoringCase('loaded light', $result['message']);
    }

    public function test_sanity_omits_light_load_note_when_truck_is_well_loaded(): void
    {
        $result = HaulingRateCalculator::sanity(0.70, 1.78, 0.90);
        $this->assertNotNull($result);
        $this->assertStringNotContainsString('loaded light', $result['message']);
    }

    public function test_high_premium_message_lists_real_services_not_cold_chain(): void
    {
        $result = HaulingRateCalculator::sanity(3.60, 1.78);
        $this->assertNotNull($result);
        $this->assertStringContainsString('waiting time', $result['message']);
        $this->assertStringNotContainsStringIgnoringCase('cold chain', $result['message']);
    }
}