<?php

namespace App\Console\Commands;

use App\Models\PoolingJob;
use App\Services\WeatherService;
use Illuminate\Console\Command;

class CheckWeather extends Command
{
    protected $signature = 'weather:check {--job= : Specific job ID to check weather for}';
    protected $description = 'Check weather conditions for active and pending pooling jobs.';

    public function handle(WeatherService $weatherService): int
    {
        $jobId = $this->option('job');

        $query = PoolingJob::whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->whereNotNull('start_latitude')
            ->whereNotNull('start_longitude');

        if ($jobId) {
            $query->where('id', $jobId);
        }

        $jobs = $query->get();

        if ($jobs->isEmpty()) {
            $this->info('No active jobs to check weather for.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($jobs as $job) {
            // Check weather at depot/start location
            $weather = $weatherService->getWeather(
                (float) $job->start_latitude,
                (float) $job->start_longitude,
                $job->id  // pass job ID for weather log persistence
            );

            if ($weather) {
                $job->update([
                    'weather_condition' => $weather['condition'],
                    'weather_temperature' => $weather['temperature'],
                    'weather_wind_speed' => $weather['wind_speed'],
                    'weather_icon' => $weather['icon'],
                    'weather_checked_at' => now(),
                    'weather_advisory' => $weather['advisory'],
                ]);

                // Check weather per-waypoint (each farm in the route)
                $job->loadMissing('harvests');
                $waypointWeather = [];
                foreach ($job->harvests as $harvest) {
                    if ($harvest->latitude && $harvest->longitude) {
                        $wpWeather = $weatherService->getWeather(
                            (float) $harvest->latitude,
                            (float) $harvest->longitude,
                            $job->id
                        );
                        if ($wpWeather && $wpWeather['is_severe']) {
                            $waypointWeather[] = "Farm #{$harvest->id}: {$wpWeather['advisory']}";
                        }
                    }
                }

                // Check weather at destination
                if ($job->end_latitude && $job->end_longitude) {
                    $destWeather = $weatherService->getWeather(
                        (float) $job->end_latitude,
                        (float) $job->end_longitude,
                        $job->id
                    );
                    if ($destWeather && $destWeather['is_severe']) {
                        $waypointWeather[] = "Destination: {$destWeather['advisory']}";
                    }
                }

                $allSevere = $weather['is_severe'] || !empty($waypointWeather);

                if ($allSevere) {
                    $advisoryText = $weather['advisory'];
                    if (!empty($waypointWeather)) {
                        $advisoryText .= ' ' . implode(' ', $waypointWeather);
                    }

                    \App\Models\Notification::create([
                        'user_id' => $job->logisticsProfile?->user_id ?? 1,
                        'title' => '⚠️ Severe Weather Alert',
                        'message' => "Route #{$job->id}: {$advisoryText}",
                        'link' => route('pooling.index'),
                        'type' => 'weather_alert',
                    ]);

                    $this->warn("Severe weather detected for Route #{$job->id}: {$weather['condition']}");
                }

                $this->info("Route #{$job->id}: {$weather['condition']}, " . round($weather['temperature']) . "°C");
                $count++;
            }
        }

        $this->info("Weather checked for {$count} job(s).");

        return self::SUCCESS;
    }
}
