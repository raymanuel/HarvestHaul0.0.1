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

Schedule::command('haul:detect-delays')->everyFifteenMinutes();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
