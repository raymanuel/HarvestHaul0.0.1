<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;


// Scheduler liveness signal: refreshed by every successful schedule:run.
// EnsureSchedulerAlive middleware treats a stale value as "OS cron is dead"
// and runs the due schedule itself after the response is sent.
Schedule::call(function () {
    Cache::put('scheduler:heartbeat', now()->timestamp, 300);
})->name('scheduler-heartbeat')->everyMinute();

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

// Mark sent invoices past their due date as overdue
Schedule::command('invoices:mark-overdue')->hourly();

// Check weather conditions for active jobs every 30 minutes
Schedule::command('weather:check')->everyThirtyMinutes();

// Check weather at driver's current GPS position for in-progress jobs every 10 minutes
Schedule::command('weather:check-active')->everyTenMinutes();

// Auto-reject expired pooling proposals (48h no response)
Schedule::command('proposals:auto-reject-expired')->hourly();

// Reopen booked haul requests whose partner never started a route (48h)
Schedule::command('haul-requests:auto-expire-booked')->hourly();

// Auto-close stale OPEN negotiations (48h inactivity)
Schedule::command('negotiations:auto-close-stale')->daily();

// Clean up stale tracking records, old notifications, and weather logs (daily)
Schedule::command('data:cleanup')->daily();

// DA RFO12 market prices: demand-driven only (scrape on dashboard visit if data >24h old).
// No scheduled cron — avoids shared-hosting process limits.
