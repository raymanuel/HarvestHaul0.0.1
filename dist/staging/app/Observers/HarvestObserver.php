<?php

namespace App\Observers;

use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\Notification;
use App\Traits\Notifiable;

class HarvestObserver
{
    use Notifiable;

    public function updated(Harvest $harvest): void
    {
        if (!$harvest->wasChanged('status')) {
            return;
        }

        match ($harvest->status) {
            HarvestStatus::ASSIGNED => $this->onAssigned($harvest),
            HarvestStatus::IN_PROGRESS => $this->onInTransit($harvest),
            HarvestStatus::COMPLETED => $this->onCompleted($harvest),
            default => null,
        };
    }

    private function onAssigned(Harvest $harvest): void
    {
        self::sendNotification(
            $harvest->user_id,
            'Harvest Assigned',
            "Your harvest '{$harvest->crop_type}' has been assigned to a logistics route.",
            route('tracking.index'),
            'harvest_assigned',
            'harvest'
        );
    }

    private function onInTransit(Harvest $harvest): void
    {
        self::sendNotification(
            $harvest->user_id,
            'Harvest In Transit',
            "Your harvest '{$harvest->crop_type}' is now in transit.",
            route('tracking.index'),
            'harvest_in_transit',
            'harvest'
        );
    }

    private function onCompleted(Harvest $harvest): void
    {
        self::sendNotification(
            $harvest->user_id,
            'Harvest Delivered',
            "Your harvest '{$harvest->crop_type}' has been delivered.",
            route('tracking.index'),
            'harvest_delivered',
            'harvest'
        );
    }
}
