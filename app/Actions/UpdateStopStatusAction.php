<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\PoolingJob;
use App\Models\User;
use App\Traits\GeometryHelper;

class UpdateStopStatusAction
{
    use GeometryHelper;

    /**
     * Update a harvest stop's status on a pooling route (assigned → arrived → loaded → delivered).
     *
     * @throws \RuntimeException on validation failure
     */
    public function execute(PoolingJob $poolingJob, Harvest $harvest, array $validated, User $driver): void
    {
        $targetStatus = $validated['status'];
        $currentStopStatus = $harvest->pivot->status;

        $this->validateSequencing($targetStatus, $currentStopStatus);
        $this->validateGeofenceIfArrived($poolingJob, $harvest, $targetStatus);
        $this->validateLoadedQuantity($harvest, $validated, $targetStatus);
        $this->validateCropConfirmation($validated, $targetStatus);
        $this->validateDeliveryReceipt($targetStatus);

        $pivotUpdates = ['status' => $targetStatus];

        if ($targetStatus === 'arrived') {
            $pivotUpdates['arrived_at'] = now();
        }

        if ($targetStatus === 'loaded') {
            $pivotUpdates['loaded_quantity_kg'] = $validated['loaded_quantity_kg'];
            $pivotUpdates['loaded_volume_cubic_meters'] = $validated['loaded_volume_cubic_meters'];
            $pivotUpdates['crop_confirmed'] = !empty($validated['crop_confirmed']);
            $pivotUpdates['loaded_at'] = now();
            $harvest->update(['status' => HarvestStatus::IN_PROGRESS]);
        }

        if ($targetStatus === 'delivered') {
            $pivotUpdates['delivered_at'] = now();
            $harvest->update(['status' => HarvestStatus::COMPLETED]);
        }

        $poolingJob->harvests()->updateExistingPivot($harvest->id, $pivotUpdates);

        AuditLog::create([
            'admin_id'    => $driver->id,
            'action'      => 'driver_stop_status_update',
            'target_type' => 'pooling_job_harvests',
            'target_id'   => $poolingJob->id,
            'notes'       => "Driver {$driver->name} updated stop (Harvest #{$harvest->id}) status to {$targetStatus} in Route #{$poolingJob->id}.",
        ]);

        $this->sendNotifications($poolingJob, $harvest, $targetStatus, $validated, $driver);
    }

    protected function validateSequencing(string $targetStatus, string $current): void
    {
        $valid = match ($targetStatus) {
            'arrived'   => $current === 'assigned',
            'loaded'    => $current === 'arrived',
            'delivered' => $current === 'loaded',
            default     => false,
        };

        if (!$valid) {
            throw new \RuntimeException("This stop must be in " . strtoupper($current) . " status to mark it as " . strtoupper($targetStatus) . ".");
        }
    }

    protected function validateGeofenceIfArrived(PoolingJob $poolingJob, Harvest $harvest, string $targetStatus): void
    {
        if ($targetStatus !== 'arrived') {
            return;
        }

        $latestTracking = \App\Models\TrackingRecord::where('pooling_job_id', $poolingJob->id)
            ->latest('id')
            ->first();

        if (!$latestTracking) {
            throw new \RuntimeException('No GPS tracking data available. Enable location tracking and try again.');
        }

        $driverLat = (float) $latestTracking->latitude;
        $driverLng = (float) $latestTracking->longitude;
        $farmLat = (float) $harvest->latitude;
        $farmLng = (float) $harvest->longitude;

        if ($farmLat && $farmLng) {
            $distFromFarm = $this->haversine($driverLat, $driverLng, $farmLat, $farmLng);
            if ($distFromFarm > 0.5) {
                throw new \RuntimeException('You must be at the farm location (within 500m) to mark as arrived. Current distance: ' . round($distFromFarm, 2) . ' km.');
            }
        }
    }

    protected function validateLoadedQuantity(Harvest $harvest, array $validated, string $targetStatus): void
    {
        if ($targetStatus !== 'loaded') {
            return;
        }

        if ((float) $validated['loaded_quantity_kg'] > (float) $harvest->pivot->quantity_kg) {
            throw new \RuntimeException('Loaded quantity (' . $validated['loaded_quantity_kg'] . ' kg) cannot exceed job allocation (' . $harvest->pivot->quantity_kg . ' kg).');
        }
    }

    protected function validateCropConfirmation(array $validated, string $targetStatus): void
    {
        if ($targetStatus !== 'loaded') {
            return;
        }

        if (!isset($validated['crop_confirmed']) || !$validated['crop_confirmed']) {
            throw new \RuntimeException('You must confirm the crop matches the listing before marking as loaded.');
        }
    }

    protected function validateDeliveryReceipt(string $targetStatus): void
    {
        if ($targetStatus === 'delivered' && !request()->hasFile('delivery_receipt')) {
            throw new \RuntimeException('A delivery receipt photo is required to mark as delivered.');
        }
    }

    protected function sendNotifications(PoolingJob $poolingJob, Harvest $harvest, string $targetStatus, array $validated, User $driver): void
    {
        if ($targetStatus === 'arrived') {
            \App\Models\Notification::create([
                'user_id' => $harvest->user_id,
                'title'   => 'Driver Arrived at Pickup',
                'message' => "Driver {$driver->name} has arrived at your farm to pick up '{$harvest->crop->name}'.",
                'link'    => route('tracking.index'),
            ]);
        }

        if ($targetStatus === 'loaded') {
            \App\Models\Notification::create([
                'user_id' => $harvest->user_id,
                'title'   => 'Harvest Loaded',
                'message' => "Driver {$driver->name} loaded {$validated['loaded_quantity_kg']} kg of '{$harvest->crop->name}' from your farm.",
                'link'    => route('tracking.index'),
            ]);
        }

        if ($targetStatus === 'delivered') {
            \App\Models\Notification::create([
                'user_id' => $harvest->user_id,
                'title'   => 'Harvest Delivered to Buyer',
                'message' => "Your harvest '{$harvest->crop->name}' has been delivered to the drop-off location.",
                'link'    => route('harvests.index'),
            ]);

            $negotiation = \App\Models\Negotiation::where('harvest_id', $harvest->id)
                ->where('status', 'COMPLETED')
                ->first();

            if ($negotiation) {
                \App\Models\Notification::create([
                    'user_id' => $negotiation->buyer_id,
                    'title'   => 'Purchase Delivered',
                    'message' => "Your purchased product of '{$harvest->crop->name}' ({$negotiation->negotiated_volume} kg) has been delivered.",
                    'link'    => route('buyer.negotiations'),
                ]);
            }
        }
    }
}
