<?php

namespace App\Services;

use App\Models\Harvest;
use App\Models\PoolingJob;
use App\Models\Truck;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ═══════════════════════════════════════════════════════════════
 * SERVICE: ResourcePoolingService
 * ═══════════════════════════════════════════════════════════════
 * The routing and cost-splitting brain of HarvestHaul.
 *
 * PURPOSE:
 *   Given a truck and a set of harvests, this service:
 *   1. Selects which harvests fit the truck (knapsack algorithm)
 *   2. Sequences pickup stops in optimal order (nearest neighbor)
 *   3. Sequences drop-off stops in optimal order
 *   4. Calculates total route distance (haversine formula)
 *   5. Generates a reference price based on distance + weight
 *   6. Splits the price proportionally per farmer (weight × distance score)
 *   7. Persists the confirmed plan to the database (DB transaction)
 *
 * CALLED FROM:
 *   PoolingJobController@plan    → preview (no DB writes)
 *   PoolingJobController@confirm → persist to DB
 *
 * ALGORITHMS USED:
 *   - Greedy Knapsack (harvest selection by weight)
 *   - Greedy Nearest Neighbor (TSP approximation for route order)
 *   - Haversine Formula (great-circle distance between GPS coords)
 *   - Weight × Distance Allocation Score (proportional cost split)
 * ═══════════════════════════════════════════════════════════════
 */
class ResourcePoolingService
{
    // ─────────────────────────────────────────────────────────
    // MAIN ENTRY POINT
    // ─────────────────────────────────────────────────────────

    /**
     * plan() — Generate a pooling route plan. NO database writes.
     *
     * @param Truck $truck             The truck being loaded
     * @param array $nearbyHarvestIds  IDs of harvests in range (from map UI)
     * @param float $startLat/Lng      Logistics depot / truck base GPS coords
     * @param float $endLat/Lng        Return destination (fleet base) GPS coords
     * @param float $radiusKm          Search radius from the dispatch console
     * @return array                   Plan array or ['success' => false, ...] on failure
     */
    public function plan(
        Truck $truck,
        array $nearbyHarvestIds,
        float $startLat,
        float $startLng,
        float $endLat,
        float $endLng,
        float $radiusKm
    ): array {
<<<<<<< HEAD
        // STEP 1: Fetch only SOLD harvests with GPS coords and full relationships.
        // Inactive/assigned harvests are excluded — prevents double-booking.
        $harvests = Harvest::whereIn('id', $nearbyHarvestIds)
            ->where('status', 'sold')
=======
        // STEP 1: Fetch only ACTIVE harvests with GPS coords and full relationships.
        // Inactive/assigned harvests are excluded — prevents double-booking.
        $harvests = Harvest::whereIn('id', $nearbyHarvestIds)
            ->where('status', 'active')
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['crop', 'cropVariety', 'farmer.farmerProfile', 'destination'])
            ->get();

        if ($harvests->isEmpty()) {
<<<<<<< HEAD
            return $this->emptyPlan('No sold harvests found for the selected farms.');
=======
            return $this->emptyPlan('No active harvests found for the selected farms.');
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
        }

        // STEP 2: Knapsack selection — pick harvests that fit within truck capacity.
        // Sorted heaviest-first to maximize load utilization. Stops at 95% fill.
        $selected = $this->knapsack($harvests, (float) $truck->capacity_kg);

        if ($selected->isEmpty()) {
            return $this->emptyPlan('No harvests fit within the truck capacity.');
        }

        // STEP 3 (Phase A): Order pickup stops using Greedy Nearest Neighbor.
        // Starts from the depot (startLat/Lng) and greedily visits the closest
        // unvisited farm at each step. Good enough approximation for rural PH routes.
        $orderedPickups = $this->greedyNearestNeighbor($selected, $startLat, $startLng);

        // Calculate total distance traveled during the collection phase.
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

        // STEP 4 (Phase B): Order drop-off stops from last pickup location.
        // Deduplicates destinations (multiple farmers → same market = 1 stop).
        $orderedDropoffs = $this->sequenceDropoffs($selected, $currentLat, $currentLng);

        // Calculate total distance traveled during the distribution/delivery phase.
        $distributionDistance = 0.0;
        foreach ($orderedDropoffs as $drop) {
            $distributionDistance += $this->haversine($currentLat, $currentLng, $drop['lat'], $drop['lng']);
            $currentLat = $drop['lat'];
            $currentLng = $drop['lng'];
        }

        // Add the return trip distance from last market back to the fleet depot.
        $returnBaseDistance = $this->haversine($currentLat, $currentLng, $endLat, $endLng);
        $totalDistance = $collectionDistance + $distributionDistance + $returnBaseDistance;

        // STEP 5: Compute financial estimates.
        $totalKg     = $selected->sum('quantity_kg');
        $loadPercent = round(($totalKg / $truck->capacity_kg) * 100, 1);

        // Reference price formula:
        //   Distance component: ₱15.00 per km
        //   Weight component:   ₱0.50 per kg
        //   Fixed base fee:     ₱250.00
        // NOTE: This is an estimate — can be overridden by negotiated_price.
        $priceReference = ($totalDistance * 15.00) + ($totalKg * 0.50) + 250.00;

        // STEP 6: Proportional cost allocation.
        // Each farmer's share = based on (their_weight × their_haul_distance).
        // This is fairer than pure weight-only splitting because farmers
        // with distant drop-offs consume more fuel per kg.
        $allocationScores = [];
        $totalAllocationScore = 0.0;

        foreach ($selected as $harvest) {
            $qty = (float) $harvest->quantity_kg;
            $hLat = (float) ($harvest['latitude'] ?? 0);
            $hLng = (float) ($harvest['longitude'] ?? 0);
            $dLat = (float) ($harvest['destination_latitude'] ?? 0);
            $dLng = (float) ($harvest['destination_longitude'] ?? 0);

            // Individual haul distance from farm → destination.
            // Minimum 1 km to prevent division-by-zero on co-located points.
            $individualDistance = $this->haversine($hLat, $hLng, $dLat, $dLng);
            if ($individualDistance < 1.0) $individualDistance = 1.0;

            // Allocation score = weight × distance (heavier AND farther = larger share)
            $score = $qty * $individualDistance;
            $allocationScores[$harvest->id] = $score;
            $totalAllocationScore += $score;
        }

        // Map each pickup stop into the output format, attaching the per-farmer cost split.
        $stops = $orderedPickups->values()->map(function ($harvest, $index) use ($priceReference, $allocationScores, $totalAllocationScore) {
            $h = is_array($harvest) ? $harvest : $harvest->toArray();

            $harvestId    = $h['id'];
            $harvestScore = $allocationScores[$harvestId] ?? 0;

            // This farmer's proportion of total cost.
            $costProportion = $totalAllocationScore > 0 ? ($harvestScore / $totalAllocationScore) : 0;
            $farmerShare    = $priceReference * $costProportion;

            return [
                'pickup_order'       => $index + 1,                          // Stop number in driver sequence
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
                'individual_cost'    => round($farmerShare, 2)               // Per-farmer cost estimate
            ];
        });

        // Return the complete plan payload.
        // This same array is passed back to confirm() for persistence.
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
            // Simplified harvest list (used by confirm endpoint to re-attach to pivot)
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

    // ─────────────────────────────────────────────────────────
    // PERSISTENCE (DB write — called after farmer confirms)
    // ─────────────────────────────────────────────────────────

    /**
     * confirm() — Saves a finalized pooling plan to the database.
     * Wrapped in a DB transaction to ensure atomicity:
     * if ANY step fails, ALL changes are rolled back.
     *
     * WHAT IT DOES:
     * 1. Creates a PoolingJob record
     * 2. Attaches harvests to job via pivot (pickup_order, quantity_kg)
     * 3. Marks each harvest as 'assigned' (removes from active marketplace)
     * 4. Sets truck status → 'reserved' (prevents double-booking)
     *
     * @param array $plan               The plan array from plan()
     * @param int   $logisticsProfileId The logistics partner who owns this job
     * @return PoolingJob               The persisted job (with relationships loaded)
     */
    public function confirm(array $plan, int $logisticsProfileId): PoolingJob
    {
        return DB::transaction(function () use ($plan, $logisticsProfileId) {
            $truck = Truck::findOrFail($plan['truck_id']);

            // Build and save the PoolingJob record.
            $job = new PoolingJob();
            $job->logistics_profile_id = $logisticsProfileId;
            $job->truck_id             = $truck->id;
            $job->driver_id            = $truck->driver_id;  // assigned driver inherits from truck
<<<<<<< HEAD
            $job->status               = 'confirmed';         // confirmed and dispatched to driver
=======
            $job->status               = 'pending';           // pending until driver accepts
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            $job->total_kg             = $plan['total_kg'];
            $job->truck_capacity_kg    = $plan['truck_capacity_kg'];
            $job->farm_count           = $plan['farm_count'];
            $job->start_latitude       = $plan['start_lat'];
            $job->start_longitude      = $plan['start_lng'];
            $job->end_latitude         = $plan['end_lat'];
            $job->end_longitude        = $plan['end_lng'];
            $job->radius_km            = $plan['radius_km'];
            $job->price_reference      = $plan['price_reference'] ?? null;
            $job->negotiated_price     = null;                // will be set after negotiation
            $job->notes                = $plan['notes'] ?? null;
            $job->confirmed_at         = now();
            $job->route_geometry       = $plan['route_geometry'] ?? null; // OSRM route JSON for map display
<<<<<<< HEAD

            // Set buyer_id from the first harvest stop's completed negotiation
            $firstStop = $plan['stops'][0] ?? null;
            if ($firstStop) {
                $negotiation = \App\Models\Negotiation::where('harvest_id', $firstStop['harvest_id'])
                    ->where('status', 'COMPLETED')
                    ->first();
                if ($negotiation) {
                    $job->buyer_id = $negotiation->buyer_id;
                }
            }

=======
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            $job->save();

            // Attach each harvest to the job via the pivot table.
            // pickup_order defines the driver's stop sequence.
            foreach ($plan['stops'] as $stop) {
                $job->harvests()->attach($stop['harvest_id'], [
                    'pickup_order' => $stop['pickup_order'],
                    'quantity_kg'  => $stop['quantity_kg'],
                    // Note: cost_share is computed and stored by PoolingJobController@confirm
                    // AFTER this method returns, using negotiated_price if set.
                ]);
                // Lock the harvest — remove from active marketplace listing
                Harvest::where('id', $stop['harvest_id'])->update(['status' => 'assigned']);
            }

            // Mark truck as reserved — prevents it from appearing in new routing sessions
            $truck->update(['status' => 'reserved']);

            return $job->load(['harvests', 'truck', 'driver']);
        });
    }

    // ─────────────────────────────────────────────────────────
    // ALGORITHMS
    // ─────────────────────────────────────────────────────────

    /**
     * Greedy Knapsack — Select harvests that fit in the truck.
     *
     * STRATEGY: Sort by heaviest harvest first (maximize kg loaded),
     * then greedily add each item if it fits in remaining capacity.
     * Stops early when 95% full (avoid leaving 1 tiny harvest unfilled).
     *
     * WHY NOT EXACT KNAPSACK: True 0/1 knapsack is O(n×W) DP.
     * For typical 5–20 harvests in PH rural context, greedy is fast enough.
     */
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
            // Early exit: truck is 95% full — good enough, stop trying to squeeze more in
            if ($used >= $capacity * 0.95) break;
        }
        return $selected;
    }

    /**
     * Greedy Nearest Neighbor — Orders pickup stops to minimize travel distance.
     *
     * STRATEGY: Start from depot, repeatedly visit the closest unvisited farm.
     * Classic TSP heuristic — not optimal but O(n²) and practical for small n.
     *
     * WHY NOT EXACT TSP: NP-hard for large n. For 5–15 farms, nearest-neighbor
     * typically achieves within 15–20% of optimal — acceptable for rural logistics.
     */
    private function greedyNearestNeighbor(Collection $harvests, float $startLat, float $startLng): Collection
    {
        $unvisited  = $harvests->toArray();
        $ordered    = collect();
        $currentLat = $startLat;
        $currentLng = $startLng;

        while (!empty($unvisited)) {
            $nearestIndex    = 0;
            $nearestDistance = PHP_FLOAT_MAX;

            // Find the closest unvisited farm from current position
            foreach ($unvisited as $i => $harvest) {
                $dist = $this->haversine($currentLat, $currentLng, (float)($harvest['latitude'] ?? 0), (float)($harvest['longitude'] ?? 0));
                if ($dist < $nearestDistance) {
                    $nearestDistance = $dist;
                    $nearestIndex    = $i;
                }
            }

            // Move to that farm and remove it from the unvisited list
            $nearest = $unvisited[$nearestIndex];
            $ordered->push($nearest);
            $currentLat = (float) ($nearest['latitude'] ?? 0);
            $currentLng = (float) ($nearest['longitude'] ?? 0);
            array_splice($unvisited, $nearestIndex, 1);
        }
        return $ordered;
    }

    /**
     * Sequence drop-offs (delivery stops) optimally.
     * Same nearest-neighbor approach applied to unique destinations.
     * Deduplicates: if 3 farmers all drop at the same market, it's 1 stop.
     *
     * Starts from the last pickup location (end of collection phase).
     */
    private function sequenceDropoffs(Collection $selectedHarvests, float $lastPickupLat, float $lastPickupLng): array
    {
        // Deduplicate destinations — multiple farmers can share the same drop-off market
        $uniqueDests = $selectedHarvests->map(function($h) {
            return [
                'lat' => (float) ($h->destination_latitude ?? 0),
                'lng' => (float) ($h->destination_longitude ?? 0),
            ];
        })->unique(fn($d) => $d['lat'].'-'.$d['lng'])->values()->all();

        $orderedDrops = [];
        $currentLat   = $lastPickupLat;
        $currentLng   = $lastPickupLng;

        while (!empty($uniqueDests)) {
            $nearestIndex = 0;
            $nearestDist  = PHP_FLOAT_MAX;

            foreach ($uniqueDests as $i => $dest) {
                $dist = $this->haversine($currentLat, $currentLng, $dest['lat'], $dest['lng']);
                if ($dist < $nearestDist) {
                    $nearestDist  = $dist;
                    $nearestIndex = $i;
                }
            }

            $nearest        = $uniqueDests[$nearestIndex];
            $orderedDrops[] = $nearest;
            $currentLat     = $nearest['lat'];
            $currentLng     = $nearest['lng'];
            array_splice($uniqueDests, $nearestIndex, 1);
        }

        return $orderedDrops;
    }

    /**
     * Haversine Formula — Great-circle distance between two GPS coordinates.
     *
     * Returns distance in KILOMETERS.
     * Accounts for Earth's curvature (radius = 6371 km).
     * Used for: collection distances, distribution distances, return distance, allocation scores.
     *
     * @param float $lat1/$lng1  Origin point
     * @param float $lat2/$lng2  Destination point
     * @return float             Distance in km
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Returns a standardized empty/failure plan response.
     * Used when the algorithm can't produce a valid plan.
     */
    private function emptyPlan(string $message): array
    {
        return ['success' => false, 'message' => $message, 'stops' => []];
    }
}
