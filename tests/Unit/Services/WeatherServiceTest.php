<?php

namespace Tests\Unit\Services;

use App\Services\WeatherService;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    private WeatherService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WeatherService::class);
    }

    public function test_has_weather_changed_returns_false_for_identical_conditions(): void
    {
        $old = ['condition' => 'Clear', 'temperature' => 25, 'wind_speed' => 5];
        $new = ['condition' => 'Clear', 'temperature' => 25, 'wind_speed' => 5];

        $this->assertFalse($this->service->hasWeatherChanged($old, $new));
    }

    public function test_has_weather_changed_detects_severe_weather(): void
    {
        $old = ['condition' => 'Clear', 'temperature' => 25, 'wind_speed' => 5];
        $new = ['condition' => 'Thunderstorm', 'temperature' => 20, 'wind_speed' => 45];

        $this->assertTrue($this->service->hasWeatherChanged($old, $new));
    }

    public function test_has_weather_changed_detects_weather_cleared(): void
    {
        $old = ['condition' => 'Thunderstorm', 'temperature' => 20, 'wind_speed' => 45];
        $new = ['condition' => 'Clear', 'temperature' => 25, 'wind_speed' => 5];

        $this->assertTrue($this->service->hasWeatherChanged($old, $new));
    }

    public function test_has_weather_changed_detects_rain_started(): void
    {
        $old = ['condition' => 'Clear', 'temperature' => 25, 'wind_speed' => 5];
        $new = ['condition' => 'Moderate Rain', 'temperature' => 20, 'wind_speed' => 10];

        $this->assertTrue($this->service->hasWeatherChanged($old, $new));
    }

    public function test_has_weather_changed_detects_rain_intensified(): void
    {
        $old = ['condition' => 'Light Rain', 'temperature' => 20, 'wind_speed' => 10];
        $new = ['condition' => 'Heavy Rain', 'temperature' => 18, 'wind_speed' => 15];

        $this->assertTrue($this->service->hasWeatherChanged($old, $new));
    }

    public function test_has_weather_changed_detects_wind_increase(): void
    {
        $old = ['condition' => 'Clear', 'temperature' => 25, 'wind_speed' => 20];
        $new = ['condition' => 'Clear', 'temperature' => 25, 'wind_speed' => 45];

        $this->assertTrue($this->service->hasWeatherChanged($old, $new));
    }

    public function test_has_weather_changed_detects_temperature_drop(): void
    {
        $old = ['condition' => 'Clear', 'temperature' => 30, 'wind_speed' => 5];
        $new = ['condition' => 'Clear', 'temperature' => 20, 'wind_speed' => 5];

        $this->assertTrue($this->service->hasWeatherChanged($old, $new));
    }

    public function test_has_weather_changed_detects_temperature_extreme(): void
    {
        $old = ['condition' => 'Clear', 'temperature' => 25, 'wind_speed' => 5];
        $new = ['condition' => 'Clear', 'temperature' => 45, 'wind_speed' => 5];

        $this->assertTrue($this->service->hasWeatherChanged($old, $new));
    }

    public function test_has_weather_changed_ignores_minor_changes(): void
    {
        $old = ['condition' => 'Clear', 'temperature' => 25, 'wind_speed' => 5];
        $new = ['condition' => 'Clear', 'temperature' => 27, 'wind_speed' => 8];

        $this->assertFalse($this->service->hasWeatherChanged($old, $new));
    }

    public function test_get_weather_change_type_returns_null_for_no_change(): void
    {
        $old = ['condition' => 'Clear', 'temperature' => 25, 'wind_speed' => 5];
        $new = ['condition' => 'Clear', 'temperature' => 25, 'wind_speed' => 5];

        $this->assertNull($this->service->getWeatherChangeType($old, $new));
    }

    public function test_get_weather_change_type_returns_severe_weather(): void
    {
        $old = ['condition' => 'Clear', 'temperature' => 25, 'wind_speed' => 5];
        $new = ['condition' => 'Thunderstorm', 'temperature' => 20, 'wind_speed' => 45];

        $this->assertEquals('severe_weather', $this->service->getWeatherChangeType($old, $new));
    }

    public function test_get_weather_speed_multiplier_clear_weather(): void
    {
        $multiplier = $this->service->getWeatherSpeedMultiplier('Clear', 5.0, 25.0);
        $this->assertEquals(1.0, $multiplier);
    }

    public function test_get_weather_speed_multiplier_light_rain(): void
    {
        $multiplier = $this->service->getWeatherSpeedMultiplier('Light Rain', 10.0, 20.0);
        $this->assertEquals(0.85, $multiplier);
    }

    public function test_get_weather_speed_multiplier_heavy_rain(): void
    {
        $multiplier = $this->service->getWeatherSpeedMultiplier('Heavy Rain', 15.0, 18.0);
        $this->assertEquals(0.70, $multiplier);
    }

    public function test_get_weather_speed_multiplier_thunderstorm(): void
    {
        $multiplier = $this->service->getWeatherSpeedMultiplier('Thunderstorm', 30.0, 20.0);
        $this->assertEquals(0.50, $multiplier);
    }

    public function test_get_weather_speed_multiplier_high_wind(): void
    {
        $multiplier = $this->service->getWeatherSpeedMultiplier('Clear', 45.0, 25.0);
        $this->assertEquals(0.80, $multiplier);
    }

    public function test_get_weather_speed_multiplier_extreme_wind(): void
    {
        $multiplier = $this->service->getWeatherSpeedMultiplier('Clear', 65.0, 25.0);
        $this->assertEquals(0.50, $multiplier);
    }

    public function test_get_weather_speed_multiplier_extreme_heat(): void
    {
        $multiplier = $this->service->getWeatherSpeedMultiplier('Clear', 5.0, 42.0);
        $this->assertEquals(0.90, $multiplier);
    }

    public function test_get_weather_speed_multiplier_extreme_cold(): void
    {
        $multiplier = $this->service->getWeatherSpeedMultiplier('Clear', 5.0, 5.0);
        $this->assertEquals(0.85, $multiplier);
    }

    public function test_get_weather_speed_multiplier_fog(): void
    {
        $multiplier = $this->service->getWeatherSpeedMultiplier('Fog', 5.0, 20.0);
        $this->assertEquals(0.75, $multiplier);
    }

    public function test_get_weather_speed_multiplier_minimum_floor(): void
    {
        $multiplier = $this->service->getWeatherSpeedMultiplier('Tornado', 80.0, 45.0);
        $this->assertGreaterThanOrEqual(0.30, $multiplier);
    }

    public function test_get_weather_speed_multiplier_null_condition(): void
    {
        $multiplier = $this->service->getWeatherSpeedMultiplier(null, null, null);
        $this->assertEquals(1.0, $multiplier);
    }

    public function test_get_change_summary_returns_readable_string(): void
    {
        $old = ['condition' => 'Clear', 'temperature' => 25, 'wind_speed' => 5];
        $new = ['condition' => 'Thunderstorm', 'temperature' => 20, 'wind_speed' => 45];

        $summary = $this->service->getChangeSummary('severe_weather', $old, $new);

        $this->assertIsString($summary);
        $this->assertNotEmpty($summary);
        $this->assertStringContainsString('Thunderstorm', $summary);
    }
}
