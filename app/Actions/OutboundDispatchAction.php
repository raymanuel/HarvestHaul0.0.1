<?php

namespace App\Actions;

use App\Models\OutboundOrder;
use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
use Illuminate\Support\Str;

class OutboundDispatchAction
{
    public function execute(
        OutboundOrder $order,
        int $truckId,
        int $driverId,
        float $startLat,
        float $startLng
    ): PoolingJob {
        $profile = $order->logisticsProfile;
        $customer = $order->customerCard;

        $job = PoolingJob::create([
            'logistics_profile_id' => $profile->id,
            'truck_id'             => $truckId,
            'driver_id'            => $driverId,
            'status'               => PoolingJobStatus::CONFIRMED,
            'leg_type'             => 'outbound',
            'customer_card_id'     => $customer->id,
            'outbound_order_id'    => $order->id,
            'total_kg'             => (float) $order->total_kg,
            'truck_capacity_kg'    => (float) ($order->logisticsProfile->trucks()->find($truckId)?->capacity_kg ?? 0),
            'start_latitude'       => $startLat,
            'start_longitude'      => $startLng,
            'end_latitude'         => (float) $customer->latitude,
            'end_longitude'        => (float) $customer->longitude,
            'radius_km'            => 5,
            'price_reference'      => (float) $order->total_amount,
            'confirmed_at'         => now(),
            'notes'                => $order->notes,
        ]);

        $order->update([
            'status'         => 'confirmed',
            'tracking_token' => Str::random(32),
            'dispatched_at'  => now(),
        ]);

        return $job;
    }
}