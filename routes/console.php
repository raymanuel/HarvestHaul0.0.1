<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
<<<<<<< HEAD
use Illuminate\Support\Facades\Schedule;
=======
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
<<<<<<< HEAD

// Auto-complete deliveries awaiting buyer confirmation for over 48 hours
Schedule::command('deliveries:auto-complete')->hourly();
=======
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
