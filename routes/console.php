<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-complete deliveries awaiting buyer confirmation for over 48 hours
Schedule::command('deliveries:auto-complete')->hourly();

// Auto-complete stale in_progress deliveries older than 48 hours
Schedule::command('deliveries:auto-complete-stale')->hourly();

// Detect stalls and stop delays on active jobs every 15 minutes
Schedule::command('delays:check')->everyFifteenMinutes();

// Auto-generate invoices for completed jobs hourly
Schedule::command('invoices:generate')->hourly();

// Check weather conditions for active jobs every 30 minutes
Schedule::command('weather:check')->everyThirtyMinutes();

// Check weather at driver's current GPS position for in-progress jobs every 10 minutes
Schedule::command('weather:check-active')->everyTenMinutes();

// Auto-reject expired pooling proposals (48h no response)
Schedule::command('proposals:auto-reject-expired')->hourly();

// Auto-close stale OPEN negotiations (7 days inactivity)
Schedule::command('negotiations:auto-close-stale')->daily();

// Clean up stale tracking records, old notifications, and weather logs (daily)
Schedule::command('data:cleanup')->daily();

// Scrape DA RFO12 prices (every 2 hours, blog-first for freshest data)
Schedule::command('crops:scrape:darfo12')->cron('0 */2 * * *')->withoutOverlapping();
