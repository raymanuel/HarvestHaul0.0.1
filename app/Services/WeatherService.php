<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openweathermap.org/data/2.5';

    public function __construct()
    {
        $this->apiKey = config('services.openweather.key', '');
    }

    public function getWeather(float $lat, float $lng, ?int $poolingJobId = null): ?array
    {
        if (empty($this->apiKey)) {
            return $this->getFallbackWeather();
        }

        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/weather", [
                'lat' => $lat,
                'lon' => $lng,
                'appid' => $this->apiKey,
                'units' => 'metric',
            ]);

            if (!$response->successful()) return $this->getFallbackWeather();

            $data = $response->json();
            $forecast = $this->getForecast($lat, $lng);

            $result = [
                'condition' => $data['weather'][0]['main'] ?? 'Unknown',
                'description' => $data['weather'][0]['description'] ?? '',
                'icon' => $data['weather'][0]['icon'] ?? '01d',
                'temperature' => $data['main']['temp'] ?? 0,
                'feels_like' => $data['main']['feels_like'] ?? 0,
                'humidity' => $data['main']['humidity'] ?? 0,
                'wind_speed' => $data['wind']['speed'] ?? 0,
                'wind_gust' => $data['wind']['gust'] ?? 0,
                'visibility' => $data['visibility'] ?? 0,
                'advisory' => $this->generateAdvisory($data, $forecast),
                'is_severe' => $this->isSevereWeather($data, $forecast),
                'forecast_available' => $forecast !== null,
            ];

            // Persist to weather_logs for history if poolingJobId provided
            if ($poolingJobId) {
                try {
                    \App\Models\WeatherLog::create([
                        'pooling_job_id' => $poolingJobId,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'condition' => $result['condition'],
                        'description' => $result['description'],
                        'icon' => $result['icon'],
                        'temperature' => $result['temperature'],
                        'feels_like' => $result['feels_like'],
                        'humidity' => $result['humidity'],
                        'wind_speed' => $result['wind_speed'],
                        'wind_gust' => $result['wind_gust'],
                        'visibility' => $result['visibility'],
                        'advisory' => $result['advisory'],
                        'is_severe' => $result['is_severe'],
                        'forecast_json' => $forecast ? json_encode($forecast) : null,
                        'checked_at' => now(),
                    ]);
                } catch (\Exception $logErr) {
                    // Don't fail if weather log storage fails
                    Log::warning('WeatherService: failed to persist weather log: ' . $logErr->getMessage());
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('WeatherService: ' . $e->getMessage());
            return $this->getFallbackWeather();
        }
    }

    /**
     * Check weather across multiple waypoints for a given route.
     */
    public function getWeatherForRoute(int $poolingJobId, array $waypoints): array
    {
        $results = [];
        foreach ($waypoints as $i => $wp) {
            $results[] = $this->getWeather(
                (float) $wp['lat'],
                (float) $wp['lng'],
                $poolingJobId
            );
        }
        return $results;
    }

    public function getForecast(float $lat, float $lng): ?array
    {
        if (empty($this->apiKey)) return null;

        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/forecast", [
                'lat' => $lat,
                'lon' => $lng,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'cnt' => 8,
            ]);

            if (!$response->successful()) return null;

            return $response->json();
        } catch (\Exception $e) {
            Log::error('WeatherService forecast: ' . $e->getMessage());
            return null;
        }
    }

    private function generateAdvisory(array $data, ?array $forecast = null): string
    {
        $main = $data['weather'][0]['main'] ?? '';
        $desc = $data['weather'][0]['description'] ?? '';
        $wind = $data['wind']['speed'] ?? 0;
        $vis = $data['visibility'] ?? 10000;
        $temp = $data['main']['temp'] ?? 25;

        $warnings = [];

        if (in_array($main, ['Thunderstorm', 'Tornado'])) {
            $warnings[] = 'Severe storm warning. Delay travel if possible.';
        }
        if ($main === 'Rain' && $desc === 'heavy intensity rain') {
            $warnings[] = 'Heavy rain — reduced visibility and road traction.';
        }
        if ($wind > 40) {
            $warnings[] = 'Very high winds (' . round($wind) . ' km/h). Risk of toppling for high-sided vehicles.';
        } elseif ($wind > 25) {
            $warnings[] = 'Strong winds (' . round($wind) . ' km/h). Drive with caution.';
        }
        if ($vis < 1000) {
            $warnings[] = 'Very low visibility (' . $vis . 'm).';
        } elseif ($vis < 5000) {
            $warnings[] = 'Reduced visibility (' . $vis . 'm).';
        }
        if ($temp > 40) {
            $warnings[] = 'Extreme heat (' . round($temp) . '°C). Ensure crop refrigeration.';
        }

        // Check forecast for upcoming severe weather within route duration
        if ($forecast && isset($forecast['list'])) {
            foreach ($forecast['list'] as $period) {
                $fcMain = $period['weather'][0]['main'] ?? '';
                $fcWind = $period['wind']['speed'] ?? 0;
                $fcTime = \Carbon\Carbon::parse($period['dt_txt'] ?? $period['dt'] ?? null);

                if ($fcTime && $fcTime->isFuture() && $fcTime->diffInHours(now()) <= 6) {
                    if (in_array($fcMain, ['Thunderstorm', 'Tornado', 'Hurricane'])) {
                        $warnings[] = "Forecast predicts {$fcMain} around " . $fcTime->format('gA') . '. Consider rescheduling.';
                        break;
                    }
                    if ($fcWind > 50) {
                        $warnings[] = "Forecast predicts very high winds (" . round($fcWind) . " km/h) around " . $fcTime->format('gA') . '.';
                        break;
                    }
                }
            }
        }

        return empty($warnings) ? 'Weather conditions favorable for transport.' : implode(' ', $warnings);
    }

    private function isSevereWeather(array $data, ?array $forecast = null): bool
    {
        $main = $data['weather'][0]['main'] ?? '';
        $wind = $data['wind']['speed'] ?? 0;

        $severe = in_array($main, ['Thunderstorm', 'Tornado', 'Hurricane'])
            || $wind > 50;

        // Also check forecast
        if (!$severe && $forecast && isset($forecast['list'])) {
            foreach ($forecast['list'] as $period) {
                $fcMain = $period['weather'][0]['main'] ?? '';
                $fcWind = $period['wind']['speed'] ?? 0;
                $fcTime = \Carbon\Carbon::parse($period['dt_txt'] ?? $period['dt'] ?? null);
                if ($fcTime && $fcTime->isFuture() && $fcTime->diffInHours(now()) <= 6) {
                    if (in_array($fcMain, ['Thunderstorm', 'Tornado', 'Hurricane']) || $fcWind > 50) {
                        return true;
                    }
                }
            }
        }

        return $severe;
    }

    private function getFallbackWeather(): array
    {
        return [
            'condition' => 'Unknown',
            'description' => 'Weather data unavailable',
            'icon' => '01d',
            'temperature' => 0,
            'feels_like' => 0,
            'humidity' => 0,
            'wind_speed' => 0,
            'wind_gust' => 0,
            'visibility' => 0,
            'advisory' => 'Weather check skipped (no API key configured).',
            'is_severe' => false,
        ];
    }

    /**
     * Check if weather conditions have changed significantly between two readings.
     *
     * @param array $old Previous weather data
     * @param array $new Current weather data
     * @return bool True if significant change detected
     */
    public function hasWeatherChanged(array $old, array $new): bool
    {
        return $this->getWeatherChangeType($old, $new) !== null;
    }

    /**
     * Determine the type of weather change between two readings.
     *
     * @param array $old Previous weather data
     * @param array $new Current weather data
     * @return string|null Change type or null if no significant change
     */
    public function getWeatherChangeType(array $old, array $new): ?string
    {
        $oldCondition = strtolower($old['condition'] ?? '');
        $newCondition = strtolower($new['condition'] ?? '');

        $oldTemp = $old['temperature'] ?? 0;
        $newTemp = $new['temperature'] ?? 0;

        $oldWind = $old['wind_speed'] ?? 0;
        $newWind = $new['wind_speed'] ?? 0;

        $severeConditions = ['thunderstorm', 'tornado', 'hurricane', 'severe', 'extreme'];
        $rainConditions = ['light rain', 'moderate rain', 'heavy rain', 'drizzle'];

        $oldIsSevere = in_array($oldCondition, $severeConditions);
        $newIsSevere = in_array($newCondition, $severeConditions);
        $oldIsRainy = in_array($oldCondition, $rainConditions);
        $newIsRainy = in_array($newCondition, $rainConditions);
        $oldIsWindy = $oldWind > 40;
        $newIsWindy = $newWind > 40;

        if (!$oldIsSevere && $newIsSevere) {
            return 'severe_weather';
        }

        if ($oldIsSevere && !$newIsSevere) {
            return 'weather_cleared';
        }

        if (!$oldIsRainy && $newIsRainy) {
            return 'rain_started';
        }

        if ($oldIsRainy && $newIsRainy) {
            $rainIntensity = ['drizzle' => 1, 'light rain' => 2, 'moderate rain' => 3, 'heavy rain' => 4];
            $oldIntensity = $rainIntensity[$oldCondition] ?? 2;
            $newIntensity = $rainIntensity[$newCondition] ?? 2;
            if ($newIntensity > $oldIntensity) {
                return 'rain_intensified';
            }
        }

        if (!$oldIsWindy && $newIsWindy) {
            return 'wind_increase';
        }

        if ($oldIsWindy && $newIsWindy && $newWind > $oldWind + 10) {
            return 'wind_increase';
        }

        if (abs($newTemp - $oldTemp) > 5) {
            return $newTemp > $oldTemp ? 'temperature_increased' : 'temperature_decreased';
        }

        if ($newTemp > 40 || $newTemp < 10) {
            return 'temperature_extreme';
        }

        return null;
    }

    /**
     * Get weather speed multiplier for ETA calculation.
     *
     * @param string|null $condition Weather condition
     * @param float|null $windSpeed Wind speed in km/h
     * @param float|null $temperature Temperature in Celsius
     * @return float Speed multiplier (0.0 - 1.0)
     */
    public function getWeatherSpeedMultiplier(?string $condition, ?float $windSpeed, ?float $temperature): float
    {
        $condition = strtolower($condition ?? 'clear');
        $multiplier = 1.0;

        $multiplier = match ($condition) {
            'clear', 'clouds', 'partly cloudy', 'few clouds', 'scattered clouds', 'broken clouds', 'overcast' => 1.0,
            'light rain', 'drizzle' => 0.85,
            'moderate rain' => 0.75,
            'heavy rain' => 0.70,
            'thunderstorm' => 0.50,
            'tornado', 'hurricane' => 0.30,
            'fog', 'mist', 'haze' => 0.75,
            'snow', 'sleet' => 0.50,
            default => 0.90,
        };

        if ($windSpeed !== null) {
            if ($windSpeed > 60) {
                $multiplier = min($multiplier, 0.50);
            } elseif ($windSpeed > 40) {
                $multiplier = min($multiplier, 0.80);
            } elseif ($windSpeed > 25) {
                $multiplier = min($multiplier, 0.90);
            }
        }

        if ($temperature !== null) {
            if ($temperature > 40) {
                $multiplier = min($multiplier, 0.90);
            } elseif ($temperature < 10) {
                $multiplier = min($multiplier, 0.85);
            }
        }

        return max($multiplier, 0.30);
    }

    /**
     * Get a human-readable change summary.
     *
     * @param string $changeType The change type from getWeatherChangeType()
     * @param array $old Previous weather data
     * @param array $new Current weather data
     * @return string Human-readable description
     */
    public function getChangeSummary(string $changeType, array $old, array $new): string
    {
        return match ($changeType) {
            'severe_weather' => "Severe weather approaching. {$new['condition']} detected with {$new['wind_speed']} km/h winds.",
            'weather_cleared' => "Weather has cleared. Previous: {$old['condition']}. Now: {$new['condition']}.",
            'rain_started' => "Rain has started. Conditions: {$new['condition']}.",
            'rain_intensified' => "Rain intensifying. Changed from {$old['condition']} to {$new['condition']}.",
            'wind_increase' => "Wind increasing. Now {$new['wind_speed']} km/h (was {$old['wind_speed']} km/h).",
            'temperature_extreme' => "Extreme temperature detected. Current: {$new['temperature']}°C.",
            'temperature_decreased' => "Temperature dropped significantly. Now {$new['temperature']}°C (was {$old['temperature']}°C).",
            'temperature_increased' => "Temperature rose significantly. Now {$new['temperature']}°C (was {$old['temperature']}°C).",
            default => "Weather conditions changed.",
        };
    }
}
