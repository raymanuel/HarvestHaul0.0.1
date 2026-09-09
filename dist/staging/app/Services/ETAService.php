<?php

namespace App\Services;

use App\Models\PoolingJob;
use App\Models\TrackingRecord;
use App\Traits\GeometryHelper;

class ETAService
{
    use GeometryHelper;

    private const EARTH_RADIUS_KM = 6371;
    private const DEFAULT_SPEED_KMH = 30;

    // Speed multipliers for terrain/road type estimation based on PH road network
    private const TERRAIN_SPEED_MULTIPLIER = 0.85; // rural PH roads avg 85% of nominal speed
    private const STOP_SPEED_THRESHOLD_KMH = 1;

    public function __construct(
        private ?WeatherService $weatherService = null,
    ) {}

    public function calculateETA(PoolingJob $job, bool $adjustWeather = true): array
    {
        $latestRecords = TrackingRecord::where('pooling_job_id', $job->id)
            ->latest('id')
            ->take(5) // increased from 2 for better smoothing
            ->get();

        $currentSpeed = self::DEFAULT_SPEED_KMH;
        $currentLat = $job->start_latitude;
        $currentLng = $job->start_longitude;

        if ($latestRecords->count() >= 2) {
            // Use speed smoothing: average of last 3 records to avoid GPS spike jitter
            $speeds = [];
            $validRecords = $latestRecords->take(3);
            foreach ($validRecords as $record) {
                if ($record->speed_kmh && $record->speed_kmh > 0) {
                    $speeds[] = (float) $record->speed_kmh;
                }
            }

            if (count($speeds) > 0) {
                // Median filter to reject spikes (>2x median)
                sort($speeds);
                $median = $speeds[(int) floor(count($speeds) / 2)];
                $filtered = array_filter($speeds, fn($s) => $s <= $median * 2);
                $currentSpeed = array_sum($filtered) / count($filtered);
            } else {
                // Compute speed from last two positions
                $newest = $latestRecords->first();
                $prev = $latestRecords->last();
                $timeDiff = $newest->posted_at->diffInSeconds($prev->posted_at);
                if ($timeDiff > 0) {
                    $dist = $this->haversine(
                        (float) $prev->latitude, (float) $prev->longitude,
                        (float) $newest->latitude, (float) $newest->longitude
                    );
                    $currentSpeed = max(($dist / $timeDiff) * 3600, self::STOP_SPEED_THRESHOLD_KMH);
                }
            }

            // Apply terrain multiplier for rural PH roads
            $currentSpeed *= self::TERRAIN_SPEED_MULTIPLIER;

            // Apply weather multiplier if enabled and weather service available
            $weatherMultiplier = 1.0;
            if ($adjustWeather && $this->weatherService) {
                $weatherCondition = $job->weather_condition;
                $weatherWind = $job->weather_wind_speed;
                $weatherTemp = $job->weather_temperature;

                if (!$weatherCondition && $latestRecords->isNotEmpty()) {
                    $newest = $latestRecords->first();
                    $lastWeatherCheck = $job->weather_checked_at;
                    if ($lastWeatherCheck && $lastWeatherCheck->diffInMinutes(now()) < 30) {
                        $weatherCondition = $job->weather_condition;
                        $weatherWind = $job->weather_wind_speed;
                        $weatherTemp = $job->weather_temperature;
                    }
                }

                if ($weatherCondition) {
                    $weatherMultiplier = $this->weatherService->getWeatherSpeedMultiplier(
                        $weatherCondition,
                        $weatherWind ? (float) $weatherWind : null,
                        $weatherTemp ? (float) $weatherTemp : null,
                    );
                    $currentSpeed *= $weatherMultiplier;
                }
            }

            $newest = $latestRecords->first();
            $currentLat = (float) $newest->latitude;
            $currentLng = (float) $newest->longitude;
        } elseif ($latestRecords->isNotEmpty()) {
            $record = $latestRecords->first();
            $currentLat = (float) $record->latitude;
            $currentLng = (float) $record->longitude;
        }

        $remainingDistance = $this->calculateRemainingDistance($job, $currentLat, $currentLng);

        $etaSeconds = $currentSpeed > 0 ? ($remainingDistance / $currentSpeed) * 3600 : 0;

        $eta = now()->addSeconds((int) $etaSeconds);

        // Compute confidence score
        $totalRecords = $latestRecords->count();
        $recencySeconds = $totalRecords > 0 ? $latestRecords->first()->posted_at->diffInSeconds(now()) : 999;
        $speedStability = $totalRecords >= 3 ? $this->calculateSpeedStability($latestRecords->take(3)) : 0.5;

        $confidenceScore = 0.0;
        $dataQuality = 'stale';

        if ($totalRecords >= 3 && $recencySeconds < 30 && $speedStability > 0.7) {
            $confidenceScore = min(1.0, 0.5 + ($speedStability * 0.3) + ((30 - $recencySeconds) / 30 * 0.2));
            $dataQuality = 'high';
        } elseif ($totalRecords >= 1 && $recencySeconds < 120) {
            $confidenceScore = max(0.3, 0.5 - ($recencySeconds / 120 * 0.3));
            $dataQuality = 'medium';
        } elseif ($recencySeconds < 600) {
            $confidenceScore = max(0.1, 0.3 - ($recencySeconds / 600 * 0.2));
            $dataQuality = 'low';
        }

        // Adjust confidence for stopped vehicles (ETA becomes guesswork)
        if ($currentSpeed < 1 && $dataQuality === 'high') {
            $confidenceScore *= 0.7;
            $dataQuality = 'medium'; // degraded by stop
        }

        return [
            'current_lat' => $currentLat,
            'current_lng' => $currentLng,
            'current_speed_kmh' => round($currentSpeed, 1),
            'remaining_distance_km' => round($remainingDistance, 2),
            'eta' => $eta,
            'eta_human' => $eta->diffForHumans(),
            'eta_formatted' => $eta->format('g:i A'),
            'is_stopped' => $currentSpeed < 1,
            'confidence_score' => round($confidenceScore, 2),
            'data_quality' => $dataQuality,
            'total_gps_pings' => $totalRecords,
            'last_ping_seconds_ago' => $recencySeconds,
            'weather_adjusted' => $adjustWeather && isset($weatherMultiplier) && $weatherMultiplier < 1.0,
            'weather_multiplier' => round($weatherMultiplier ?? 1.0, 2),
            'weather_condition' => $job->weather_condition ?? null,
        ];
    }

    public function getETAForJob(PoolingJob $job): array
    {
        return $this->calculateETA($job);
    }

    private function calculateRemainingDistance(PoolingJob $job, float $currentLat, float $currentLng): float
    {
        $job->loadMissing(['harvests' => function ($q) {
            $q->orderByPivot('pickup_order');
        }]);

        $waypoints = [];
        $skipRemaining = false;

        foreach ($job->harvests as $harvest) {
            $lat = (float) ($harvest->latitude ?? 0);
            $lng = (float) ($harvest->longitude ?? 0);

            // Skip completed stops
            if ($harvest->pivot->status === 'delivered') continue;

            // If this stop is loaded or in progress, we haven't reached it yet but driver is at/near it
            if ($lat && $lng) {
                $waypoints[] = ['lat' => $lat, 'lng' => $lng];
            }
        }

        $destLat = (float) ($job->end_latitude ?? 0);
        $destLng = (float) ($job->end_longitude ?? 0);

        $totalDistance = 0.0;
        $prevLat = $currentLat;
        $prevLng = $currentLng;

        foreach ($waypoints as $wp) {
            $totalDistance += $this->haversine($prevLat, $prevLng, $wp['lat'], $wp['lng']);
            $prevLat = $wp['lat'];
            $prevLng = $wp['lng'];
        }

        if ($destLat && $destLng) {
            $totalDistance += $this->haversine($prevLat, $prevLng, $destLat, $destLng);
        }

        return $totalDistance;
    }

    /**
     * Calculate speed stability as a ratio (0-1) based on variance.
     * Higher values = more stable speed = more confident ETA.
     */
    private function calculateSpeedStability($records): float
    {
        $speeds = [];
        foreach ($records as $r) {
            if ($r->speed_kmh && $r->speed_kmh > 0) {
                $speeds[] = (float) $r->speed_kmh;
            }
        }
        if (count($speeds) < 2) return 0.5;

        $mean = array_sum($speeds) / count($speeds);
        if ($mean < 1) return 0.5;

        $variance = 0.0;
        foreach ($speeds as $s) {
            $variance += ($s - $mean) ** 2;
        }
        $variance /= count($speeds);
        $cv = sqrt($variance) / $mean; // coefficient of variation

        return max(0.1, min(1.0, 1.0 - $cv));
    }
}
