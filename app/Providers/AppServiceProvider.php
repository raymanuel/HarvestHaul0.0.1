<?php

namespace App\Providers;

use App\Channels\DatabaseChannel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Notification::extend('database', function ($app) {
            return $app->make(DatabaseChannel::class);
        });
    }
}
