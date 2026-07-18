<?php

namespace App\Services;

use App\Models\DriverProfile;
use App\Models\FuelLog;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\PoolingJob;
use App\Models\Truck;
use App\Traits\GeometryHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    use GeometryHelper;

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
        // STEP 1: Fetch only SOLD/PARTIALLY_SOLD harvests with GPS coords and full relationships.
        // Inactive/assigned harvests are excluded — prevents double-booking.
        $harvests = Harvest::whereIn('id', $nearbyHarvestIds)
            ->whereIn('status', HarvestStatus::logisticsVisible())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['crop', 'cropVariety', 'farmer.farmerProfile', 'destination', 'negotiations' => fn($q) => $q->where('status', 'COMPLETED')])
            ->get();

        // Validate all selected harvests are within radius from start point
        foreach ($harvests as $h) {
            $dist = $this->haversine($startLat, $startLng, (float) $h->latitude, (float) $h->longitude);
            if ($dist > $radiusKm) {
                return $this->emptyPlan("Harvest #{$h->id} ({$h->crop_type}) is {$dist}km away — exceeds {$radiusKm}km radius.");
            }
        }

        // Check all harvests belong to the same buyer (or no buyer conflict)
        $harvestIds = $harvests->pluck('id');
        $completedNegotiations = \App\Models\Negotiation::whereIn('harvest_id', $harvestIds)
            ->where('status', 'COMPLETED')
            ->get()
            ->keyBy('harvest_id');
        $buyerIds = $harvests->map(fn($h) => $completedNegotiations->get($h->id)?->buyer_id)
            ->unique()->filter();
        if ($buyerIds->count() > 1) {
            return $this->emptyPlan('Selected harvests have different buyers. Multi-buyer pooling is not yet supported.');
        }

        // Check no harvest is already in another pending pooling job
        $conflictingIds = \App\Models\PoolingJob::where('status', 'pending')
            ->whereHas('harvests', fn($q) => $q->whereIn('harvest_id', $nearbyHarvestIds))
            ->pluck('id');
        if ($conflictingIds->isNotEmpty()) {
            return $this->emptyPlan('Some harvests are already in another pending pooling job (#' . $conflictingIds->implode(', #') . ').');
        }

        if ($harvests->isEmpty()) {
            return $this->emptyPlan('No sold harvests found for the selected farms.');
        }

        // Fuel Sufficiency Check: soft warning if no recent fuel logs
        $latestFuelLog = FuelLog::where('truck_id', $truck->id)->latest()->first();
        $fuelWarning = null;
        if (!$latestFuelLog) {
            $fuelWarning = 'Truck has no recent fuel logs. Recommend refueling before dispatch.';
        }

        // STEP 2: Knapsack selection — pick harvests that fit within truck capacity.
        // Use negotiated volume from completed negotiation, not raw quantity_kg.
        // Guard: skip harvests without completed negotiations.
        $harvests = $harvests->filter(function ($harvest) {
            $completedNegotiation = $harvest->negotiations->first();
            if (!$completedNegotiation) {
                Log::warning("Harvest #{$harvest->id} reached pooling service without completed negotiation. Skipping.");
                return false;
            }
            return true;
        });

        // Update quantity_kg in-memory to negotiated_volume for knapsack
        foreach ($harvests as $h) {
            $negotiation = $h->negotiations->first();
            if ($negotiation && $negotiation->negotiated_volume) {
                $h->quantity_kg = $negotiation->negotiated_volume;
            }
        }

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
            'message'           => $fuelWarning,
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
            // Check route_geometry is valid JSON with valid coordinates
            if (isset($plan['route_geometry'])) {
                $geom = $plan['route_geometry'];
                if (!is_array($geom)) {
                    throw new \InvalidArgumentException('route_geometry must be an array');
                }
            }

            // Use pessimistic locking on the truck
            $truck = Truck::where('id', $plan['truck_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Re-verify truck is still available (race condition guard)
            if ($truck->status !== 'available') {
                throw new \RuntimeException('Truck is no longer available.');
            }

            // If driver assigned, verify driver is still active
            if ($truck->driver_id) {
                $driver = \App\Models\User::where('id', $truck->driver_id)
                    ->whereHas('driverProfile', fn($q) => $q->where('employment_status', 'active'))
                    ->exists();
                if (!$driver) {
                    throw new \RuntimeException('Assigned driver is no longer active.');
                }
            }

            // Re-verify harvests still have sold/partially_sold status (could have changed since plan preview)
            $soldCount = Harvest::whereIn('id', collect($plan['selected_harvests'])->pluck('harvest_id'))
                ->whereIn('status', HarvestStatus::logisticsVisible())
                ->count();
            $expectedCount = count($plan['selected_harvests']);
            if ($soldCount !== $expectedCount) {
                throw new \RuntimeException('Some harvests are no longer available (status changed since plan preview).');
            }

            // Build and save the PoolingJob record.
            $job = new PoolingJob();
            $job->logistics_profile_id = $logisticsProfileId;
            $job->truck_id             = $truck->id;
            $job->driver_id            = $truck->driver_id;  // assigned driver inherits from truck
            $job->status               = 'pending';           // starts as a pending proposal
            $job->total_kg             = $plan['total_kg'];
            $job->truck_capacity_kg    = $plan['truck_capacity_kg'];
            $job->farm_count           = $plan['farm_count'];
            $job->start_latitude       = $plan['start_lat'];
            $job->start_longitude      = $plan['start_lng'];
            $job->end_latitude         = $plan['end_lat'];
            $job->end_longitude        = $plan['end_lng'];
            $job->radius_km            = $plan['radius_km'];
            $job->planned_distance_km  = $plan['total_distance_km'] ?? null;
            $job->price_reference      = $plan['price_reference'] ?? null;
            $job->negotiated_price     = $plan['price_reference'] ?? null; // initial bid is reference price
            $job->notes                = $plan['notes'] ?? null;
            $job->proposal_expires_at  = $plan['proposal_expires_at'] ?? now()->addHours(48);
            $job->confirmed_at         = null;                // will be populated once confirmed
            $job->route_geometry       = $plan['route_geometry'] ?? null; // OSRM route JSON for map display

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

            $job->save();

            // Attach each harvest to the job via the pivot table.
            // pickup_order defines the driver's stop sequence.
            foreach ($plan['stops'] as $stop) {
                $job->harvests()->attach($stop['harvest_id'], [
                    'pickup_order' => $stop['pickup_order'],
                    'quantity_kg'  => $stop['quantity_kg'],
                    'status'       => 'pending',          // starts as pending farmer approval
                ]);
                // Lock the harvest — remove from active marketplace
                Harvest::where('id', $stop['harvest_id'])->update(['status' => 'assigned']);
            }

            // Mark truck as reserved — prevents it from appearing in new routing sessions
            $truck->update(['status' => 'reserved']);

            // Update driver's last_assigned_at for load balancing in future auto-assignment
            if ($truck->driver_id) {
                DriverProfile::where('user_id', $truck->driver_id)
                    ->update(['last_assigned_at' => now()]);
            }

            return $job->load(['harvests', 'truck', 'driver']);
        });
    }

    // ─────────────────────────────────────────────────────────
    // ALGORITHMS
    // ─────────────────────────────────────────────────────────

    /**
     * Optimal 0/1 Knapsack — Select harvests that fit in the truck.
     *
     * STRATEGY:
     *   For n ≤ 20: brute-force over all 2ⁿ subsets picks the exact best fit.
     *   For n > 20: falls back to greedy (heaviest-first) as approximation.
     *
     * WHY OPTIMAL: Greedy can leave 10–20% capacity unused when smaller
     * harvests could fill the gaps. For n ≤ 20 the brute force is fast
     * (~1M iterations × 20 items ≈ 20M ops — well within PHP's limits).
     */
    private function knapsack(Collection $harvests, float $capacity): Collection
    {
        $items = $harvests->values();
        $n = $items->count();

        if ($n === 0) return collect();
        if ($n === 1) {
            return (float) $items[0]->quantity_kg <= $capacity ? collect([$items[0]]) : collect();
        }

        // For large n, fall back to greedy (fast approximation)
        if ($n > 20) {
            return $this->greedyKnapsack($harvests, $capacity);
        }

        // Brute force: enumerate all subsets, pick the one that maximizes
        // total weight without exceeding capacity. Breaks ties by item count
        // (more farmers served is preferred).
        $bestWeight = 0.0;
        $bestMask = 0;
        $bestCount = 0;

        for ($mask = 1; $mask < (1 << $n); $mask++) {
            $weight = 0.0;
            $count = 0;

            for ($i = 0; $i < $n; $i++) {
                if ($mask & (1 << $i)) {
                    $weight += (float) $items[$i]->quantity_kg;
                    $count++;
                    if ($weight > $capacity) break;
                }
            }

            if ($weight <= $capacity && ($weight > $bestWeight || ($weight === $bestWeight && $count > $bestCount))) {
                $bestWeight = $weight;
                $bestMask = $mask;
                $bestCount = $count;
            }
        }

        if ($bestMask === 0) return collect();

        $selected = collect();
        for ($i = 0; $i < $n; $i++) {
            if ($bestMask & (1 << $i)) {
                $selected->push($items[$i]);
            }
        }
        return $selected;
    }

    /**
     * Greedy fallback — heaviest-first, used when n > 20.
     */
    private function greedyKnapsack(Collection $harvests, float $capacity): Collection
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

    /**
     * Returns a standardized empty/failure plan response.
     * Used when the algorithm can't produce a valid plan.
     */
    private function emptyPlan(string $message): array
    {
        return ['success' => false, 'message' => $message, 'stops' => []];
    }
}
