<?php

namespace App\Providers;

use App\Channels\DatabaseChannel;
use App\Models\HaulRequest;
use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
use App\Models\Negotiation;
use App\Models\Harvest;
use App\Observers\PoolingJobObserver;
use App\Observers\NegotiationObserver;
use App\Observers\HarvestObserver;
use Illuminate\Support\Facades\Notification;
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

        // When a pooling job completes, resolve any open/booked haul requests
        // covering the same harvests so they don't dead-end in "booked".
        PoolingJob::updated(function (PoolingJob $job) {
            if ($job->wasChanged('status') && $job->status === PoolingJobStatus::COMPLETED) {
                $harvestIds = $job->harvests()->pluck('harvests.id');
                if ($harvestIds->isNotEmpty()) {
                    HaulRequest::whereIn('harvest_id', $harvestIds)
                        ->whereIn('status', ['open', 'booked'])
                        ->update(['status' => 'fulfilled']);
                }
            }
        });

        // Eager-load user relationships for the layout component to prevent
        // lazy-loading queries on every page render (sidebar role checks)
        View::composer('components.layout', function ($view) {
            $user = $view->user ?? auth()->user();
            if ($user && !$user->relationLoaded('logisticsProfile')) {
                $user->load('logisticsProfile');
            }
            $view->with('authUser', $user);
        });

    }
}
