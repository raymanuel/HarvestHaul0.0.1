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
    // MAIN ENTRY POINT (Supports Multi-Drop Sorting & Cost Splits)
    // -------------------------------------------------------
    public function plan(
        Truck $truck,
        array $nearbyHarvestIds,
        float $startLat,
        float $startLng,
        float $endLat,
        float $endLng,
        float $radiusKm
    ): array {
        // 1. Eager load harvests with nested farmer profiles
        $harvests = Harvest::whereIn('id', $nearbyHarvestIds)
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['crop', 'cropVariety', 'farmer.farmerProfile', 'destination'])
            ->get();

        if ($harvests->isEmpty()) {
            return $this->emptyPlan('No active harvests found for the selected farms.');
        }

        // 2. Knapsack calculation optimization layer
        $selected = $this->knapsack($harvests, (float) $truck->capacity_kg);

        if ($selected->isEmpty()) {
            return $this->emptyPlan('No harvests fit within the truck capacity.');
        }

        // 3. Phase A Sequencing: Ordered Pickups (Greedy Nearest Neighbor)
        $orderedPickups = $this->greedyNearestNeighbor($selected, $startLat, $startLng);

        // Calculate Collection Run Distance
        $collectionDistance = 0.0;
        $currentLat = $startLat;
        $currentLng = $startLng;

        foreach ($orderedPickups as $harvest) {
            $hLat = (float) ($harvest['latitude'] ?? 0);
            $hLng = (float) ($harvest['longitude'] ?? 0);
            $collectionDistance += $this->haversine($currentLat, $currentLng, $hLat, $hLng);
            $currentLat = $hLat;
            $currentLng = $hLng;
        }

        // 4. Phase B Sequencing: Ordered Drop-offs starting from last pickup location
        $orderedDropoffs = $this->sequenceDropoffs($selected, $currentLat, $currentLng);

        // Calculate Distribution Run Distance
        $distributionDistance = 0.0;
        foreach ($orderedDropoffs as $drop) {
            $distributionDistance += $this->haversine($currentLat, $currentLng, $drop['lat'], $drop['lng']);
            $currentLat = $drop['lat'];
            $currentLng = $drop['lng'];
        }

        // Append final log return path from last market drop back to fleet garage base
        $returnBaseDistance = $this->haversine($currentLat, $currentLng, $endLat, $endLng);
        $totalDistance = $collectionDistance + $distributionDistance + $returnBaseDistance;

        // 5. Compute Financial Framework Models
        $totalKg     = $selected->sum('quantity_kg');
        $loadPercent = round(($totalKg / $truck->capacity_kg) * 100, 1);

        // Base Reference Cost Formula
        $priceReference = ($totalDistance * 15.00) + ($totalKg * 0.50) + 250.00;

        // 6. PERFECT SYNCHRONIZATION: Weight-Distance Product Allocation Score
        $allocationScores = [];
        $totalAllocationScore = 0.0;

        foreach ($selected as $harvest) {
            $qty = (float) $harvest->quantity_kg;
            $hLat = (float) ($harvest['latitude'] ?? 0);
            $hLng = (float) ($harvest['longitude'] ?? 0);
            $dLat = (float) ($harvest['destination_latitude'] ?? 0);
            $dLng = (float) ($harvest['destination_longitude'] ?? 0);

            // Calculate direct baseline line haul factor for this individual cargo element
            $individualDistance = $this->haversine($hLat, $hLng, $dLat, $dLng);
            if ($individualDistance < 1.0) $individualDistance = 1.0; // Prevent division by zero fractions

            $score = $qty * $individualDistance;
            $allocationScores[$harvest->id] = $score;
            $totalAllocationScore += $score;
        }

        $stops = $orderedPickups->values()->map(function ($harvest, $index) use ($priceReference, $allocationScores, $totalAllocationScore) {
            $h = is_array($harvest) ? $harvest : $harvest->toArray();

            $harvestId = $h['id'];
            $harvestScore = $allocationScores[$harvestId] ?? 0;

            // Determine proportion share of the actual sequenced trip cost
            $costProportion = $totalAllocationScore > 0 ? ($harvestScore / $totalAllocationScore) : 0;
            $farmerShare = $priceReference * $costProportion;

            return [
                'pickup_order'       => $index + 1,
                'harvest_id'         => $harvestId,
                'farmer_name'        => $h['farmer']['name'] ?? 'Unknown',
                'farm_location'      => $h['farmer']['farmer_profile']['farm_location'] ?? '—',
                'crop'               => $h['crop']['name'] ?? $h['crop_type'] ?? '—',
                'variety'            => $h['crop_variety']['name'] ?? $h['variety'] ?? '—',
                'quantity_kg'        => (float) ($h['quantity_kg'] ?? 0),
                'latitude'           => (float) ($h['latitude'] ?? 0),
                'longitude'          => (float) ($h['longitude'] ?? 0),
                'destination_label'  => $h['destination']['name'] ?? '—',
                'destination_lat'    => (float) ($h['destination_latitude'] ?? 0),
                'destination_lng'    => (float) ($h['destination_longitude'] ?? 0),
                'individual_cost'    => round($farmerShare, 2)
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
            'total_distance_km' => round($totalDistance, 2),
            'price_reference'   => round($priceReference, 2),
            'stops'             => $stops,
            'selected_harvests' => $stops->map(fn($s) => [
                'harvest_id'    => $s['harvest_id'],
                'farm_name'     => $s['farmer_name'],
                'farm_location' => $s['farm_location'],
                'crop'          => $s['crop'] . ($s['variety'] !== '—' ? ' (' . $s['variety'] . ')' : ''),
                'quantity_kg'   => $s['quantity_kg'],
                'pickup_order'  => $s['pickup_order'],
                'split_cost'    => $s['individual_cost']
            ])->values(),
        ];
    }

    // -------------------------------------------------------
    // CONFIRM — Persists proposal payload safely to storage
    // -------------------------------------------------------
    public function confirm(array $plan, int $logisticsProfileId): PoolingJob
    {
        return DB::transaction(function () use ($plan, $logisticsProfileId) {
            $truck = Truck::findOrFail($plan['truck_id']);

            $job = new PoolingJob();
            $job->logistics_profile_id = $logisticsProfileId;
            $job->truck_id             = $truck->id;
            $job->driver_id            = $truck->driver_id;
            $job->status               = 'pending';
            $job->total_kg             = $plan['total_kg'];
            $job->truck_capacity_kg    = $plan['truck_capacity_kg'];
            $job->farm_count           = $plan['farm_count'];
            $job->start_latitude       = $plan['start_lat'];
            $job->start_longitude      = $plan['start_lng'];
            $job->end_latitude         = $plan['end_lat'];
            $job->end_longitude        = $plan['end_lng'];
            $job->radius_km            = $plan['radius_km'];
            $job->price_reference      = $plan['price_reference'] ?? null;
            $job->negotiated_price     = null;
            $job->notes                = $plan['notes'] ?? null;
            $job->confirmed_at         = now();
            $job->route_geometry       = $plan['route_geometry'] ?? null;
            $job->save();

            foreach ($plan['stops'] as $stop) {
                $job->harvests()->attach($stop['harvest_id'], [
                    'pickup_order' => $stop['pickup_order'],
                    'quantity_kg'  => $stop['quantity_kg'],
                ]);
                Harvest::where('id', $stop['harvest_id'])->update(['status' => 'assigned']);
            }

            $truck->update(['status' => 'reserved']);

            return $job->load(['harvests', 'truck', 'driver']);
        });
    }

    // -------------------------------------------------------
    // ALGORITHMIC HELPERS
    // -------------------------------------------------------
    private function knapsack(Collection $harvests, float $capacity): Collection
    {
        $sorted   = $harvests->sortByDesc('quantity_kg')->values();
        $selected = collect();
        $used     = 0.0;

        foreach ($sorted as $harvest) {
            $qty = (float) $harvest->quantity_kg;
            if ($used + $qty <= $capacity) {
                $selected->push($harvest);
                $used += $qty;
            }
            if ($used >= $capacity * 0.95) break;
        }
        return $selected;
    }

    private function greedyNearestNeighbor(Collection $harvests, float $startLat, float $startLng): Collection
    {
        $unvisited = $harvests->toArray();
        $ordered   = collect();
        $currentLat = $startLat;
        $currentLng = $startLng;

        while (!empty($unvisited)) {
            $nearestIndex    = 0;
            $nearestDistance = PHP_FLOAT_MAX;

            foreach ($unvisited as $i => $harvest) {
                $dist = $this->haversine($currentLat, $currentLng, (float)($harvest['latitude'] ?? 0), (float)($harvest['longitude'] ?? 0));
                if ($dist < $nearestDistance) {
                    $nearestDistance = $dist;
                    $nearestIndex    = $i;
                }
            }

            $nearest = $unvisited[$nearestIndex];
            $ordered->push($nearest);
            $currentLat = (float) ($nearest['latitude'] ?? 0);
            $currentLng = (float) ($nearest['longitude'] ?? 0);
            array_splice($unvisited, $nearestIndex, 1);
        }
        return $ordered;
    }

    private function sequenceDropoffs(Collection $selectedHarvests, float $lastPickupLat, float $lastPickupLng): array
    {
        $uniqueDests = $selectedHarvests->map(function($h) {
            return [
                'lat' => (float) ($h->destination_latitude ?? 0),
                'lng' => (float) ($h->destination_longitude ?? 0),
            ];
        })->unique(fn($d) => $d['lat'].'-'.$d['lng'])->values()->all();

        $orderedDrops = [];
        $currentLat = $lastPickupLat;
        $currentLng = $lastPickupLng;

        while (!empty($uniqueDests)) {
            $nearestIndex = 0;
            $nearestDist = PHP_FLOAT_MAX;

            foreach ($uniqueDests as $i => $dest) {
                $dist = $this->haversine($currentLat, $currentLng, $dest['lat'], $dest['lng']);
                if ($dist < $nearestDist) {
                    $nearestDist = $dist;
                    $nearestIndex = $i;
                }
            }

            $nearest = $uniqueDests[$nearestIndex];
            $orderedDrops[] = $nearest;
            $currentLat = $nearest['lat'];
            $currentLng = $nearest['lng'];
            array_splice($uniqueDests, $nearestIndex, 1);
        }

        return $orderedDrops;
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function emptyPlan(string $message): array
    {
        return ['success' => false, 'message' => $message, 'stops' => []];
    }
}
