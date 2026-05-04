<?php

namespace App\Services;

use App\Models\Harvest;
use App\Models\PoolingJob;
use App\Models\Truck;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResourcePoolingService
{
    // -------------------------------------------------------
    // MAIN ENTRY POINT
    // Pool harvests for a given truck, route, and radius.
    // Returns a plan array for display — nothing saved yet.
    // -------------------------------------------------------
    public function plan(
        Truck $truck,
        array $nearbyHarvestIds,  // harvest IDs already filtered by route radius on the frontend
        float $startLat,
        float $startLng,
        float $endLat,
        float $endLng,
        float $radiusKm
    ): array {
        // 1. Load the harvests
        $harvests = Harvest::whereIn('id', $nearbyHarvestIds)
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['crop', 'cropVariety', 'farmer', 'destination'])
            ->get();

        if ($harvests->isEmpty()) {
            return $this->emptyPlan('No active harvests found for the selected farms.');
        }

        // 2. Knapsack — select best combination within truck capacity
        $selected = $this->knapsack($harvests, (float) $truck->capacity_kg);

        if ($selected->isEmpty()) {
            return $this->emptyPlan('No harvests fit within the truck capacity.');
        }

        // 3. Greedy Nearest Neighbor — order the selected farms by proximity
        $ordered = $this->greedyNearestNeighbor($selected, $startLat, $startLng);

        // 4. Build the plan
        $totalKg      = $selected->sum('quantity_kg');
        $loadPercent  = round(($totalKg / $truck->capacity_kg) * 100, 1);

        $stops = $ordered->values()->map(function ($harvest, $index) {
            return [
                'pickup_order'       => $index + 1,
                'harvest_id'         => $harvest->id,
                'farmer_name'        => $harvest->farmer->name,
                'farm_location'      => $harvest->farmer?->farmerProfile?->farm_location ?? '—',
                'crop'               => $harvest->crop->name ?? $harvest->crop_type ?? '—',
                'variety'            => $harvest->cropVariety->name ?? $harvest->variety ?? '—',
                'quantity_kg'        => (float) $harvest->quantity_kg,
                'latitude'           => (float) $harvest->latitude,
                'longitude'          => (float) $harvest->longitude,
                'destination_label'  => $harvest->destination_label,
                'destination_lat'    => (float) $harvest->destination_latitude,
                'destination_lng'    => (float) $harvest->destination_longitude,
            ];
        });

        return [
            'success'           => true,
            'message'           => null,
            'truck_id'          => $truck->id,
            'truck_name'        => $truck->truck_name ?? $truck->plate_number,
            'truck_capacity_kg' => (float) $truck->capacity_kg,
            'total_kg'          => round((float) $totalKg, 2),
            'load_percentage'   => $loadPercent,
            'farm_count'        => $stops->count(),
            'start_lat'         => $startLat,
            'start_lng'         => $startLng,
            'end_lat'           => $endLat,
            'end_lng'           => $endLng,
            'radius_km'         => $radiusKm,
            'stops'             => $stops,             // used by confirm()
            'selected_harvests' => $stops->map(fn($s) => [
                'harvest_id'    => $s['harvest_id'],
                'farm_name'     => $s['farmer_name'],
                'farm_location' => $s['farm_location'],
                'crop'          => $s['crop'] . ($s['variety'] !== '—' ? ' (' . $s['variety'] . ')' : ''),
                'quantity_kg'   => $s['quantity_kg'],
                'pickup_order'  => $s['pickup_order'],
            ])->values(),
        ];
    }

    // -------------------------------------------------------
    // CONFIRM — Save the plan to DB
    // -------------------------------------------------------
    public function confirm(array $plan, int $logisticsProfileId): PoolingJob
    {
        return DB::transaction(function () use ($plan, $logisticsProfileId) {
            $truck = Truck::findOrFail($plan['truck_id']);

            // Create the job
            $job = PoolingJob::create([
                'logistics_profile_id' => $logisticsProfileId,
                'truck_id'             => $truck->id,
                'driver_id'            => $truck->driver_id,
                'status'               => 'confirmed',
                'total_kg'             => $plan['total_kg'],
                'truck_capacity_kg'    => $plan['truck_capacity_kg'],
                'farm_count'           => $plan['farm_count'],
                'start_latitude'       => $plan['start_lat'],
                'start_longitude'      => $plan['start_lng'],
                'end_latitude'         => $plan['end_lat'],
                'end_longitude'        => $plan['end_lng'],
                'radius_km'            => $plan['radius_km'],
                'confirmed_at'         => now(),
            ]);

            // Attach harvests with pivot data
            foreach ($plan['stops'] as $stop) {
                $job->harvests()->attach($stop['harvest_id'], [
                    'pickup_order' => $stop['pickup_order'],
                    'quantity_kg'  => $stop['quantity_kg'],
                ]);

                // Mark harvest as assigned
                Harvest::where('id', $stop['harvest_id'])->update(['status' => 'assigned']);
            }

            // Mark truck as assigned
            $truck->update(['status' => 'assigned']);

            return $job->load(['harvests', 'truck', 'driver']);
        });
    }

    // -------------------------------------------------------
    // KNAPSACK — Maximize kg loaded without exceeding capacity
    // Uses a greedy approach sorted by quantity descending:
    // pick the largest harvests first until truck is full.
    // -------------------------------------------------------
    private function knapsack(Collection $harvests, float $capacity): Collection
    {
        // Sort by quantity descending — biggest loads first
        $sorted   = $harvests->sortByDesc('quantity_kg')->values();
        $selected = collect();
        $used     = 0.0;

        foreach ($sorted as $harvest) {
            $qty = (float) $harvest->quantity_kg;

            if ($used + $qty <= $capacity) {
                $selected->push($harvest);
                $used += $qty;
            }

            // If truck is more than 95% full, stop — good enough
            if ($used >= $capacity * 0.95) break;
        }

        return $selected;
    }

    // -------------------------------------------------------
    // GREEDY NEAREST NEIGHBOR — Order farms by proximity
    // Starting from the logistics hub (start point),
    // always go to the closest unvisited farm next.
    // -------------------------------------------------------
    private function greedyNearestNeighbor(
        Collection $harvests,
        float $startLat,
        float $startLng
    ): Collection {
        $unvisited = $harvests->values()->toArray();
        $ordered   = collect();
        $currentLat = $startLat;
        $currentLng = $startLng;

        while (!empty($unvisited)) {
            $nearestIndex    = 0;
            $nearestDistance = PHP_FLOAT_MAX;

            foreach ($unvisited as $i => $harvest) {
                $dist = $this->haversine(
                    $currentLat, $currentLng,
                    (float) $harvest->latitude,
                    (float) $harvest->longitude
                );

                if ($dist < $nearestDistance) {
                    $nearestDistance = $dist;
                    $nearestIndex    = $i;
                }
            }

            $nearest    = $unvisited[$nearestIndex];
            $ordered->push($nearest);
            $currentLat = (float) $nearest->latitude;
            $currentLng = (float) $nearest->longitude;

            array_splice($unvisited, $nearestIndex, 1);
        }

        return $ordered;
    }

    // -------------------------------------------------------
    // HAVERSINE — Straight-line distance between two coords (km)
    // -------------------------------------------------------
    private function haversine(
        float $lat1, float $lng1,
        float $lat2, float $lng2
    ): float {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    // -------------------------------------------------------
    // EMPTY PLAN helper
    // -------------------------------------------------------
    private function emptyPlan(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'stops'   => [],
        ];
    }
}
