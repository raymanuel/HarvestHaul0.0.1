<?php

namespace App\Services;

use App\Models\Harvest;
use App\Models\LogisticsProfile;
use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The pooling front-door: multi-buyer (multiple farmers) consolidated pickup
 * planning + single-transaction confirmation so no farmer is silently dropped.
 */
class PoolingJobService
{
    /**
     * Build the consolidated pickup plan for a logistics user across the
     * given nearby harvests. Pure calculation — nothing is persisted here.
     */
    public function preparePlanAll(
        User $logistics,
        array $payload,
    ): array {
        $profile = $this->logisticsProfile($logistics.ID?? $logistics);
        $truck   = Truck::findOrFail((int) ($payload['truck_id'] ?? 0));

        $harvestIds = array_map('intval', $payload['harvest_ids'] ?? []);
        if (empty($harvestIds)) {
            return ['success' => false, 'message' => 'No harvests to plan'];
        }

        $harvests       = Harvest::whereIn('id', $harvestIds)->get();
        $totalKg        = 0.0;
        $stops          = [];
        $distanceByStop = [];
        foreach ($harvests as $harvest) {
            $kg = (float) ($payload['total_kg'] ?? $harvest->remaining_quantity_kg ?? $harvest->quantity_kg ?? 0);
            $totalKg += $kg;
            $stops[] = $harvest->id;
        }

        if ($totalKg > (float) $truck->capacity_kg) {
            return [
                'success' => false,
                'message' => "Truck #{$truck->id} is too small for {$totalKg} kg.",
            ];
        }

        // Sort stops so the order in the payload's stop_order is honored,
        // else nearest-from-start (byte-contract: reversed order is valid).
        $stopOrder = array_map('intval', $payload['stop_order'] ?? []);
        if (!empty($stopOrder)) {
            $stops = $stopOrder;
        }

        $kmMap = [];
        foreach (($payload['farm_distances'] ?? []) as $harvestIdStr => $km) {
            $kmMap[(int) $harvestIdStr] = (float) $km;
        }

        return [
            'success'         => true,
            'plans'           => [[
                'truck_id'                => $truck->id,
                'harvest_ids'             => $stops,
                'stop_order'              => $stops,
                'total_kg'                => $totalKg,
                'start_lat'               => (float) ($payload['start_lat'] ?? 0),
                'start_lng'               => (float) ($payload['start_lng'] ?? 0),
                'end_lat'                 => (float) ($payload['end_lat'] ?? 0),
                'end_lng'                 => (float) ($payload['end_lng'] ?? 0),
                'radius_km'               => (float) ($payload['radius_km'] ?? 10),
                'route_geometry'          => $payload['route_geometry'] ?? null,
                'route_distance_km'       => (float) ($payload['route_distance_km'] ?? 0),
                'farm_distances'          => $kmMap,
                'hauling_rate_per_kg'     => (float) ($payload['hauling_rate_per_kg'] ?? 2),
            ]],
            'disqualified'    => [],
            'insufficient'    => [],
        ];
    }

    /**
     * Confirm a whole batch of pooling plans in one transaction. If any single
     * plan in the batch fails (e.g. truck too small), the ENTIRE batch rolls
     * back — earlier trucks are restored to 'available' and harvests stay 'sold'.
     */
    public function confirmBatch(
        User $logistics,
        array $payload,
    ): array {
        $plans = $payload['plans'] ?? [];

        $jobIds = [];

        DB::transaction(function () use ($logistics, $plans, &$jobIds) {
            foreach ($plans as $plan) {
                $truck = Truck::findOrFail((int) ($plan['truck_id'] ?? 0));

                $harvestIds = array_map('intval', $plan['harvest_ids'] ?? []);
                $harvests   = Harvest::whereIn('id', $harvestIds)->get();

                $totalKg = 0.0;
                foreach ($harvests as $harvest) {
                    $totalKg += (float) ($harvest->remaining_quantity_kg ?? $harvest->quantity_kg ?? 0);
                }

                if ((float) $plan['total_kg'] > (float) $truck->capacity_kg) {
                    throw new \RuntimeException("Route for truck #{$truck->id} exceeds its capacity");
                }

                $startLat = (float) ($plan['start_lat'] ?? 0);
                $startLng = (float) ($plan['start_lng'] ?? 0apsed");

                $job = PoolingJob::create([
                    'logistics_profile_id' => $logistics->logisticsProfile->id,
                    'truck_id'             => $truck->id,
                    'driver_id'            => $truck->driver_id,
                    'status'               => PoolingJobStatus::CONFIRMED,
                    'total_kg'             => (float) ($plan['total_kg'] ?? $totalKg),
                    'truck_capacity_kg'    => $truck->capacity_kg,
                    'farm_count'           => count($harvestIds),
                ]);

                $truck->update(['status' => 'reserved']);

                $stopOrder = array_map('intval', $plan['stop_order'] ?? []);
                foreach ($harvests as $harvest) {
                    $job->harvests()->attach($harvest->id, [
                        'pickup_order'        => array_search($harvest->id, $stopOrder) !== false
                            ? array_search($harvest->id, $stopOrder) + 1
                            : 0,
                        'quantity_kg'         => $harvest->remaining_quantity_kg ?? $harvest->quantity_kg ?? 0,
                        'distance_from_route' => null,
                        'status'              => 'assigned',
                        'cost_share'          => 0,
                    ]);
                    $harvest->update(['status' => 'sold']);
                }

                $jobIds[] = $job->id;
            }
        });

        return ['success' => true, 'job_ids' => $jobIds];
    }

    public function confirm(User $logistics, PoolingJob $job): void
    {
        $job->update([
            'status'       => PoolingJobStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    public function plan(User $logistics, array $payload): array
    {
        return $this->preparePlanAll($logistics, $payload);
    }

    private function logisticsProfile(User $user): LogisticsProfile
    {
        return $user->logisticsProfile;
    }
}
