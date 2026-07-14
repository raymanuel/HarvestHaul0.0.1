<?php

namespace App\Console\Commands;

use App\Models\TrackingRecord;
use App\Models\Notification;
use App\Models\WeatherLog;
use Illuminate\Console\Command;

class CleanupStaleData extends Command
{
    protected $signature = 'data:cleanup';
    protected $description = 'Clean up stale tracking records, old notifications, and weather logs.';

    public function handle(): int
    {
        // Archive tracking records older than 30 days
        $trackingCutoff = now()->subDays(30);
        $deletedTracking = TrackingRecord::where('posted_at', '<', $trackingCutoff)->delete();
        $this->info("Deleted {$deletedTracking} old tracking records (before {$trackingCutoff->format('Y-m-d')}).");

        // Clean up read notifications older than 90 days
        $notifCutoff = now()->subDays(90);
        $deletedNotifs = Notification::whereNotNull('read_at')
            ->where('read_at', '<', $notifCutoff)
            ->delete();
        $this->info("Deleted {$deletedNotifs} old read notifications.");

        // Clean up old weather logs (keep last 7 days)
        $weatherCutoff = now()->subDays(7);
        $deletedWeather = WeatherLog::where('checked_at', '<', $weatherCutoff)->delete();
        $this->info("Deleted {$deletedWeather} old weather logs.");

        return self::SUCCESS;
    }
}
