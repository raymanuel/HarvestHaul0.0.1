<?php

namespace App\Services\Weather;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Weather-aware logistics (advisory only — never auto-cancels or
 * auto-reschedules a trip; the coop admin always decides). Backed by
 * Open-Meteo: free, no API key, no signup, same "public keyless service,
 * cached, fails gracefully" shape as OsrmRoutingService/NominatimGeocodingService.
 */
class WeatherService
{
    /**
     * Forecast for a coordinate on a given date — picks the hour closest to
     * local noon as a representative daytime reading (trip dates carry no
     * specific hour). Returns null on any failure; callers must treat that
     * as "no weather data available", never as an error to surface.
     */
    public function forecastAt(float $lat, float $lng, Carbon $when): ?array
    {
        $date = $when->toDateString();
        $cacheKey = sprintf('weather:forecast:%.3F,%.3F:%s', $lat, $lng, $date);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $lat,
                'longitude' => $lng,
                'hourly' => 'precipitation_probability,weathercode,windspeed_10m',
                'start_date' => $date,
                'end_date' => $date,
                'timezone' => 'auto',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $forecast = $this->parseNoonHour($response->json());

            if ($forecast) {
                Cache::put($cacheKey, $forecast, now()->addDay());
            }

            return $forecast;
        } catch (Throwable $e) {
            Log::warning('Open-Meteo forecast lookup failed', ['lat' => $lat, 'lng' => $lng, 'date' => $date, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function parseNoonHour(?array $body): ?array
    {
        $times = $body['hourly']['time'] ?? null;
        if (! $times) {
            return null;
        }

        $index = null;
        foreach ($times as $i => $time) {
            if (str_ends_with($time, 'T12:00')) {
                $index = $i;
                break;
            }
        }
        $index ??= 0;

        $precipitation = $body['hourly']['precipitation_probability'][$index] ?? null;
        $wind = $body['hourly']['windspeed_10m'][$index] ?? null;
        $code = $body['hourly']['weathercode'][$index] ?? null;

        if ($precipitation === null || $wind === null) {
            return null;
        }

        return [
            'precipitation_probability' => (float) $precipitation,
            'wind_speed_kmh' => (float) $wind,
            'weather_code' => $code,
        ];
    }

    /**
     * Classifies a forecast into 'clear'/'moderate'/'severe' using
     * config('harvesthaul.weather.*') thresholds — whichever signal
     * (rain or wind) is worse decides the classification.
     */
    public function severity(array $forecast): string
    {
        $precipitation = (float) ($forecast['precipitation_probability'] ?? 0);
        $wind = (float) ($forecast['wind_speed_kmh'] ?? 0);

        if ($precipitation >= config('harvesthaul.weather.severe_precipitation_probability', 70)
            || $wind >= config('harvesthaul.weather.severe_wind_speed_kmh', 40)) {
            return 'severe';
        }

        if ($precipitation >= config('harvesthaul.weather.moderate_precipitation_probability', 40)
            || $wind >= config('harvesthaul.weather.moderate_wind_speed_kmh', 25)) {
            return 'moderate';
        }

        return 'clear';
    }

    /**
     * The ETA travel-time multiplier for a severity level — 1.0 (no change)
     * for 'clear' or anything unrecognized.
     */
    public function etaBufferFor(string $severity): float
    {
        return (float) config("harvesthaul.weather.eta_buffer.{$severity}", 1.0);
    }
}
