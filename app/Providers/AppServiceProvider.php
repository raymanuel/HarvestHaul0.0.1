<?php

namespace App\Providers;

use App\Channels\DatabaseChannel;
use App\Models\PoolingJob;
use App\Models\Negotiation;
use App\Models\Harvest;
use App\Policies\DriverPolicy;
use App\Observers\PoolingJobObserver;
use App\Observers\NegotiationObserver;
use App\Observers\HarvestObserver;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Notification::extend('database', function ($app) {
            return $app->make(DatabaseChannel::class);
        });

        // Model Observers
        PoolingJob::observe(PoolingJobObserver::class);
        Negotiation::observe(NegotiationObserver::class);
        Harvest::observe(HarvestObserver::class);

        // Eager-load user relationships for the layout component to prevent
        // lazy-loading queries on every page render (sidebar role checks)
        View::composer('components.layout', function ($view) {
            $user = $view->user ?? auth()->user();
            if ($user && !$user->relationLoaded('logisticsProfile')) {
                $user->load('logisticsProfile');
            }
            $view->with('authUser', $user);
        });

        // Driver-specific authorization gates
        Gate::define('view-job-as-driver', function (\App\Models\User $user, PoolingJob $job) {
            return $job->driver_id === $user->id;
        });

        Gate::define('update-job-as-driver', function (\App\Models\User $user, PoolingJob $job) {
            return $job->driver_id === $user->id;
        });

        Gate::define('log-fuel-for-job', function (\App\Models\User $user, PoolingJob $job) {
            return $job->driver_id === $user->id;
        });
    }
}
