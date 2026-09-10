<?php

namespace App\Observers;

use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
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
            PoolingJobStatus::CONFIRMED => $this->onConfirmed($job),
            PoolingJobStatus::CANCELLED => $this->onCancelled($job),
            default => null,
        };
    }

    private function onConfirmed(PoolingJob $job): void
    {
        // Notifications are handled exclusively by PoolingJobService::notifyRouteConfirmed
        // (called from confirmSettledRoute / confirmProposal). The observer intentionally
        // does NOT send notifications here to avoid duplicate driver + logistics messages.
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
