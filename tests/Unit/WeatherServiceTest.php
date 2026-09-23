<?php

namespace Tests\Unit;

use App\Services\Weather\WeatherService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    private function fakeForecastResponse(array $overrides = []): array
    {
        return array_merge([
            'hourly' => [
                'time' => ['2026-11-05T00:00', '2026-11-05T06:00', '2026-11-05T12:00', '2026-11-05T18:00'],
                'precipitation_probability' => [5, 10, 20, 15],
                'weathercode' => [0, 1, 2, 1],
                'windspeed_10m' => [8.0, 9.5, 12.0, 10.0],
            ],
        ], $overrides);
    }

    public function test_forecast_at_returns_the_noon_hour_for_the_target_date(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->fakeForecastResponse(), 200),
        ]);

        $service = new WeatherService();
        $forecast = $service->forecastAt(6.1164, 125.1716, Carbon::parse('2026-11-05'));

        $this->assertNotNull($forecast);
        $this->assertEquals(20, $forecast['precipitation_probability']);
        $this->assertEquals(12.0, $forecast['wind_speed_kmh']);
    }

    public function test_forecast_at_returns_null_on_a_failed_response(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response([], 500),
        ]);

        $service = new WeatherService();
        $forecast = $service->forecastAt(6.1164, 125.1716, Carbon::parse('2026-11-05'));

        $this->assertNull($forecast);
    }

    public function test_forecast_at_returns_null_when_the_request_throws(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('timed out');
        });

        $service = new WeatherService();
        $forecast = $service->forecastAt(6.1164, 125.1716, Carbon::parse('2026-11-05'));

        $this->assertNull($forecast);
    }

    public function test_forecast_at_caches_so_the_same_coordinates_and_date_only_hit_the_service_once(): void
    {
        Cache::flush();
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->fakeForecastResponse(), 200),
        ]);

        $service = new WeatherService();
        $service->forecastAt(6.11640, 125.17160, Carbon::parse('2026-11-05'));
        $service->forecastAt(6.11641, 125.17161, Carbon::parse('2026-11-05'));

        Http::assertSentCount(1);
    }

    public function test_a_failed_lookup_is_never_cached(): void
    {
        Cache::flush();
        Http::fake([
            'api.open-meteo.com/*' => Http::response([], 500),
        ]);

        $service = new WeatherService();
        $service->forecastAt(6.1164, 125.1716, Carbon::parse('2026-11-05'));
        $service->forecastAt(6.1164, 125.1716, Carbon::parse('2026-11-05'));

        Http::assertSentCount(2);
    }

    public function test_severity_classifies_clear_moderate_and_severe(): void
    {
        $service = new WeatherService();

        $this->assertEquals('clear', $service->severity(['precipitation_probability' => 5, 'wind_speed_kmh' => 10]));
        $this->assertEquals('moderate', $service->severity(['precipitation_probability' => 50, 'wind_speed_kmh' => 10]));
        $this->assertEquals('severe', $service->severity(['precipitation_probability' => 80, 'wind_speed_kmh' => 10]));
        $this->assertEquals('severe', $service->severity(['precipitation_probability' => 5, 'wind_speed_kmh' => 45]));
    }

    public function test_eta_buffer_for_returns_the_configured_multiplier(): void
    {
        $service = new WeatherService();

        $this->assertEquals(1.00, $service->etaBufferFor('clear'));
        $this->assertEquals(1.15, $service->etaBufferFor('moderate'));
        $this->assertEquals(1.30, $service->etaBufferFor('severe'));
    }
}
