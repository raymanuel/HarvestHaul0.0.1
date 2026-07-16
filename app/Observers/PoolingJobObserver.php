<?php

namespace App\Observers;

use App\Models\PoolingJob;
use App\Models\Notification;
use App\Traits\Notifiable;

class PoolingJobObserver
{
    use Notifiable;

    public function updated(PoolingJob $job): void
    {
        if (!$job->wasChanged('status')) {
            return;
        }

        match ($job->status) {
            'confirmed' => $this->onConfirmed($job),
            'cancelled' => $this->onCancelled($job),
            default => null,
        };
    }

    private function onConfirmed(PoolingJob $job): void
    {
        $job->load('harvests.crop', 'logisticsProfile.user', 'driver');

        // Notify driver if assigned
        if ($job->driver_id) {
            self::sendNotification(
                $job->driver_id,
                'New Route Confirmed',
                "Route #{$job->id} has been confirmed and dispatched to you.",
                route('driver.dashboard')
            );
        }

        // Notify logistics partner
        if ($job->logisticsProfile?->user) {
            self::sendNotification(
                $job->logisticsProfile->user_id,
                'Route Confirmed',
                "Route #{$job->id} is now confirmed. Total: {$job->total_kg} kg.",
                route('pooling.index')
            );
        }
    }

    private function onCancelled(PoolingJob $job): void
    {
        // Notify logistics partner
        if ($job->logisticsProfile?->user_id) {
            self::sendNotification(
                $job->logisticsProfile->user_id,
                'Route Cancelled',
                "Route #{$job->id} has been cancelled.",
                route('pooling.index')
            );
        }

        // Notify driver if assigned
        if ($job->driver_id) {
            self::sendNotification(
                $job->driver_id,
                'Route Cancelled',
                "Route #{$job->id} has been cancelled.",
                route('driver.dashboard')
            );
        }
    }
}
