<?php

namespace Tests\Unit\Services;

use App\Services\WeatherService;
use Tests\TestCase;

class ETAServiceWeatherTest extends TestCase
{
    private WeatherService $weatherService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->weatherService = app(WeatherService::class);
    }

    public function test_weather_speed_multiplier_returns_1_for_clear_sky(): void
    {
        $result = $this->weatherService->getWeatherSpeedMultiplier('Clear', 5.0, 25.0);
        $this->assertEquals(1.0, $result);
    }

    public function test_weather_speed_multiplier_reduces_for_rain(): void
    {
        $result = $this->weatherService->getWeatherSpeedMultiplier('Moderate Rain', 10.0, 20.0);
        $this->assertLessThan(1.0, $result);
        $this->assertGreaterThanOrEqual(0.7, $result);
    }

    public function test_weather_speed_multiplier_reduces_for_thunderstorm(): void
    {
        $result = $this->weatherService->getWeatherSpeedMultiplier('Thunderstorm', 30.0, 20.0);
        $this->assertLessThanOrEqual(0.5, $result);
    }

    public function test_weather_speed_multiplier_reduces_for_high_wind(): void
    {
        $result = $this->weatherService->getWeatherSpeedMultiplier('Clear', 50.0, 25.0);
        $this->assertLessThan(1.0, $result);
        $this->assertGreaterThanOrEqual(0.5, $result);
    }

    public function test_weather_speed_multiplier_reduces_for_extreme_heat(): void
    {
        $result = $this->weatherService->getWeatherSpeedMultiplier('Clear', 5.0, 42.0);
        $this->assertLessThan(1.0, $result);
        $this->assertGreaterThanOrEqual(0.85, $result);
    }

    public function test_weather_speed_multiplier_reduces_for_fog(): void
    {
        $result = $this->weatherService->getWeatherSpeedMultiplier('Fog', 5.0, 20.0);
        $this->assertLessThan(1.0, $result);
        $this->assertGreaterThanOrEqual(0.7, $result);
    }

    public function test_weather_speed_multiplier_never_goes_below_minimum(): void
    {
        $result = $this->weatherService->getWeatherSpeedMultiplier('Tornado', 100.0, 50.0);
        $this->assertGreaterThanOrEqual(0.30, $result);
    }

    public function test_weather_speed_multiplier_handles_null_values(): void
    {
        $result = $this->weatherService->getWeatherSpeedMultiplier(null, null, null);
        $this->assertEquals(1.0, $result);
    }

    public function test_weather_speed_multiplier_is_case_insensitive(): void
    {
        $result1 = $this->weatherService->getWeatherSpeedMultiplier('clear', 5.0, 25.0);
        $result2 = $this->weatherService->getWeatherSpeedMultiplier('Clear', 5.0, 25.0);
        $result3 = $this->weatherService->getWeatherSpeedMultiplier('CLEAR', 5.0, 25.0);

        $this->assertEquals($result1, $result2);
        $this->assertEquals($result2, $result3);
    }

    public function test_combined_weather_effects_compound(): void
    {
        $onlyRain = $this->weatherService->getWeatherSpeedMultiplier('Heavy Rain', 5.0, 25.0);
        $rainAndWind = $this->weatherService->getWeatherSpeedMultiplier('Heavy Rain', 45.0, 25.0);
        $rainWindAndHeat = $this->weatherService->getWeatherSpeedMultiplier('Heavy Rain', 45.0, 42.0);

        $this->assertGreaterThanOrEqual($rainWindAndHeat, $onlyRain);
        $this->assertGreaterThanOrEqual($rainWindAndHeat, $rainAndWind);
    }
}
