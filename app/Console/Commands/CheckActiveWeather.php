<?php

namespace App\Console\Commands;

use App\Models\PoolingJob;
use App\Models\TrackingRecord;
use App\Models\User;
use App\Notifications\WeatherChangeAlert;
use App\Services\WeatherService;
use Illuminate\Console\Command;

class CheckActiveWeather extends Command
{
    protected $signature = 'weather:check-active {--job= : Specific job ID to check}';
    protected $description = 'Check weather at driver\'s current GPS position for in-progress jobs and alert on changes.';

    public function handle(WeatherService $weatherService): int
    {
        $jobId = $this->option('job');

        $query = PoolingJob::where('status', 'in_progress')
            ->whereNotNull('driver_id');

        if ($jobId) {
            $query->where('id', $jobId);
        }

        $jobs = $query->get();

        if ($jobs->isEmpty()) {
            $this->info('No in-progress jobs to check weather for.');
            return self::SUCCESS;
        }

        $checkedCount = 0;
        $alertCount = 0;

        foreach ($jobs as $job) {
            $latestTracking = TrackingRecord::where('pooling_job_id', $job->id)
                ->orderByDesc('recorded_at')
                ->first();

            if (!$latestTracking) {
                $this->line("Route #{$job->id}: No GPS data available, skipping.");
                continue;
            }

            $lat = (float) $latestTracking->latitude;
            $lng = (float) $latestTracking->longitude;

            $weather = $weatherService->getWeather($lat, $lng, $job->id);

            if (!$weather) {
                $this->warn("Route #{$job->id}: Failed to fetch weather.");
                continue;
            }

            $oldWeather = [
                'condition' => $job->weather_condition,
                'temperature' => $job->weather_temperature,
                'wind_speed' => $job->weather_wind_speed,
            ];

            $newWeather = [
                'condition' => $weather['condition'],
                'temperature' => $weather['temperature'],
                'wind_speed' => $weather['wind_speed'],
            ];

            $job->update([
                'weather_condition' => $weather['condition'],
                'weather_temperature' => $weather['temperature'],
                'weather_wind_speed' => $weather['wind_speed'],
                'weather_icon' => $weather['icon'],
                'weather_checked_at' => now(),
                'weather_advisory' => $weather['advisory'],
            ]);

            $checkedCount++;

            if ($oldWeather['condition'] && $weatherService->hasWeatherChanged($oldWeather, $newWeather)) {
                $changeType = $weatherService->getWeatherChangeType($oldWeather, $newWeather);
                $summary = $weatherService->getChangeSummary($changeType, $oldWeather, $newWeather);

                $this->warn("Route #{$job->id}: Weather change detected — {$changeType}");
                $this->line("  Previous: {$oldWeather['condition']}, {$oldWeather['temperature']}°C");
                $this->line("  Current:  {$weather['condition']}, {$weather['temperature']}°C");

                $driver = User::find($job->driver_id);
                if ($driver) {
                    $driver->notify(new WeatherChangeAlert(
                        job: $job,
                        changeType: $changeType,
                        message: $summary,
                        previousWeather: $oldWeather['condition'],
                        currentWeather: $weather['condition'],
                        previousTemp: $oldWeather['temperature'],
                        currentTemp: $weather['temperature'],
                        previousWind: $oldWeather['wind_speed'],
                        currentWind: $weather['wind_speed'],
                    ));
                }

                $logisticsUser = $job->logisticsProfile?->user;
                if ($logisticsUser && $logisticsUser->id !== $job->driver_id) {
                    $logisticsUser->notify(new WeatherChangeAlert(
                        job: $job,
                        changeType: $changeType,
                        message: $summary,
                        previousWeather: $oldWeather['condition'],
                        currentWeather: $weather['condition'],
                        previousTemp: $oldWeather['temperature'],
                        currentTemp: $weather['temperature'],
                        previousWind: $oldWeather['wind_speed'],
                        currentWind: $weather['wind_speed'],
                    ));
                }

                \App\Models\Notification::create([
                    'user_id' => $job->logisticsProfile?->user_id ?? $job->driver_id,
                    'title' => '🌦️ Weather Change Alert',
                    'message' => "Route #{$job->id}: {$summary}",
                    'link' => route('pooling.index'),
                    'type' => 'weather_change',
                ]);

                $alertCount++;
            } else {
                $this->info("Route #{$job->id}: {$weather['condition']}, " . round($weather['temperature']) . "°C — no significant change.");
            }
        }

        $this->info("Checked {$checkedCount} job(s), sent {$alertCount} weather change alert(s).");

        return self::SUCCESS;
    }
}
