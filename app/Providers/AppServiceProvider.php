<?php

namespace App\Providers;

use App\Channels\DatabaseChannel;
use App\Services\Routing\OsrmRoutingService;
use App\Services\Routing\RoutingServiceContract;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RoutingServiceContract::class, match (config('routing.provider', 'osrm')) {
            default => OsrmRoutingService::class,
        });
    }

    public function boot(): void
    {
        Notification::extend('database', function ($app) {
            return $app->make(DatabaseChannel::class);
        });

        // Eager-load the cooperative relationship for the layout component so
        // sidebar role checks never trigger lazy queries on each page render.
        View::composer('components.layout', function ($view) {
            $user = $view->user ?? auth()->user();
            if ($user && ! $user->relationLoaded('cooperative')) {
                $user->load('cooperative');
            }
            $view->with('authUser', $user);
        });
    }
}