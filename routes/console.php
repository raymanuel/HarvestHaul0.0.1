<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Models\User;
use App\Notifications\PriceDataStale;

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

// Scrape DA RFO12 prices from the Bantay Presyo endpoint (hourly lightweight check;
// the heavy re-fetch only runs when the source date advances)
Schedule::command('crops:scrape:darfo12')->hourly()->withoutOverlapping()
    ->onFailure(function () {
        // Instant signal; prices:check-stale remains the twice-daily deep check.
        if (Cache::get('scraper:onfailure:date') === now()->toDateString()) {
            return;
        }
        Cache::put('scraper:onfailure:date', now()->toDateString(), now()->diffInSeconds(now()->endOfDay()));

        Log::error('Scheduled scrape of DA RFO12 prices failed.');

        User::where('role', 'admin')->get()
            ->each->notify(new PriceDataStale(
                'DA RFO12 price scraper failed',
                'The hourly market price scrape exited with an error. Check Scraper Status on the admin dashboard for details.',
                route('prices.full')
            ));
    });

// Alert admins when DA RFO12 has newer prices we have not stored, or scrapes keep failing
Schedule::command('prices:check-stale')->twiceDaily(8, 20);
