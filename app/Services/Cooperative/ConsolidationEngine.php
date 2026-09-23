<?php

namespace App\Services\Cooperative;

use App\Models\BuyerOrder;
use App\Models\Cooperative;
use App\Models\HaulJob;
use App\Models\HaulRequest;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Routing\HaversineService;
use App\Services\Routing\RoutingServiceContract;
use App\Services\Weather\WeatherService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Cooperative pickup consolidation engine.
 *
 * Picks the approved pickup requests for a cooperative on a given date,
 * bin-packs them into one or more truck-sized candidate trips (first-fit
 * decrease knapsack), then builds a road-based route per candidate using
 * the public OSRM `/table` matrix with a nearest-neighbor seed + 2-opt
 * improvement. Requests that fit no available truck are returned as
 * "unassigned" rather than forced into an overloaded trip.
 *
 * Road-distance figures are advisory only — they never affect the agreed
 * per-kg billing. If OSRM is unavailable, the engine falls back to
 * haversine distance and still returns a plan.
 */
class ConsolidationEngine
{
    public function __construct(
        private RoutingServiceContract $routing,
        private HaversineService $haversine,
        private WeatherService $weather,
    ) {}

    public function planForDate(Cooperative $cooperative, string $date): array
    {
        $requests = $cooperative->haulRequests()
            ->with('farmer.farmerProfile')
            ->whereDate('preferred_pickup_date', $date)
            ->where('status', HaulRequest::STATUS_APPROVED)
            ->whereNotNull(['pickup_location_lat', 'pickup_location_lng'])
            ->get();

        $trucks = $cooperative->trucks()->where('status', 'available')->get();
        $availableDrivers = $this->availableDrivers($cooperative, $date);

        $coopLat = (float) ($cooperative->latitude ?? 0);
        $coopLng = (float) ($cooperative->longitude ?? 0);
        $depot = ['lat' => $coopLat, 'lng' => $coopLng];

        $weatherAdvisory = $this->weatherAdvisory($cooperative, $date);

        $packed = $this->binPackByCapacity($requests, $trucks, $cooperative);

        $groups = [];
        foreach ($packed['groups'] as $i => $bin) {
            $stops = $this->buildStopPoints($bin['requests'], $depot);
            $matrix = $this->fetchRoadMatrix($stops);
            $proposed = $this->buildProposedPlan($bin, $stops, $matrix, $depot, $weatherAdvisory['buffer']);

            // Spec 7.2: "OSRM Final Route" happens before "Map Display" and
            // "Human Review" — the real road route must be visible while
            // reviewing, not only persisted silently at trip creation.
            $orderedRequests = collect($proposed['stop_ids'])
                ->map(fn ($stopId) => $bin['requests']->firstWhere('id', (int) str_replace('req_', '', $stopId)))
                ->filter()
                ->values();
            $routeGeometry = $orderedRequests->isNotEmpty()
                ? $this->fetchFinalRoute($orderedRequests, $cooperative)
                : null;

            $groups[] = [
                'label'          => 'TRIP '.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'truck'          => $bin['truck'],
                'requests'       => $bin['requests'],
                'ordered_requests' => $orderedRequests,
                'stops'          => $stops,
                'matrix'         => $matrix['source'],
                'load_kg'        => round($bin['load_kg'], 2),
                'capacity_kg'    => $bin['truck'] ? (float) $bin['truck']->capacity_kg : 0,
                'proposed'       => $proposed,
                'route_geometry' => $routeGeometry,
            ];
        }

        return [
            'date'              => $date,
            'trucks'            => $trucks,
            'available_drivers' => $availableDrivers,
            'groups'            => $groups,
            'unassigned'        => $packed['unassigned'],
            'capacity'          => $this->capacityBreakdown($packed['groups']),
            'weather'           => $weatherAdvisory,
        ];
    }

    /**
     * Outbound delivery counterpart to planForDate() (spec 14 — Outbound
     * Delivery Management). Sources accepted BuyerOrders instead of
     * approved HaulRequests; reuses every routing/scheduling primitive
     * below unchanged (fetchRoadMatrix/nearestNeighbor/twoOpt/
     * buildArrivalSchedule/buildProposedPlan are already stop-shape
     * generic) — only the two model-typed extraction steps
     * (buildDeliveryStopPoints, binPackOrdersByCapacity) are duplicated.
     */
    public function planDeliveriesForDate(Cooperative $cooperative, string $date): array
    {
        $orders = $cooperative->buyerOrders()
            ->with('buyer')
            ->where('status', BuyerOrder::STATUS_ACCEPTED)
            ->whereDoesntHave('stop')
            ->whereNotNull(['delivery_latitude', 'delivery_longitude'])
            ->where(function ($q) use ($date) {
                $q->whereNull('preferred_delivery_date')->orWhereDate('preferred_delivery_date', $date);
            })
            ->get();

        $trucks = $cooperative->trucks()->where('status', 'available')->get();
        $availableDrivers = $this->availableDrivers($cooperative, $date);

        $coopLat = (float) ($cooperative->latitude ?? 0);
        $coopLng = (float) ($cooperative->longitude ?? 0);
        $depot = ['lat' => $coopLat, 'lng' => $coopLng];

        $weatherAdvisory = $this->weatherAdvisory($cooperative, $date);

        $packed = $this->binPackOrdersByCapacity($orders, $trucks);

        $groups = [];
        foreach ($packed['groups'] as $i => $bin) {
            $stops = $this->buildDeliveryStopPoints($bin['requests'], $depot);
            $matrix = $this->fetchRoadMatrix($stops);
            $proposed = $this->buildProposedPlan($bin, $stops, $matrix, $depot, $weatherAdvisory['buffer']);

            $orderedOrders = collect($proposed['stop_ids'])
                ->map(fn ($stopId) => $bin['requests']->firstWhere('id', (int) str_replace('ord_', '', $stopId)))
                ->filter()
                ->values();
            $routeGeometry = $orderedOrders->isNotEmpty()
                ? $this->fetchFinalRouteForOrders($orderedOrders, $cooperative)
                : null;

            $groups[] = [
                'label'          => 'TRIP '.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'truck'          => $bin['truck'],
                'orders'         => $bin['requests'],
                'ordered_orders' => $orderedOrders,
                'stops'          => $stops,
                'matrix'         => $matrix['source'],
                'load_kg'        => round($bin['load_kg'], 2),
                'capacity_kg'    => $bin['truck'] ? (float) $bin['truck']->capacity_kg : 0,
                'proposed'       => $proposed,
                'route_geometry' => $routeGeometry,
            ];
        }

        return [
            'date'              => $date,
            'trucks'            => $trucks,
            'available_drivers' => $availableDrivers,
            'groups'            => $groups,
            'unassigned'        => $packed['unassigned'],
            'capacity'          => $this->capacityBreakdown($packed['groups']),
            'weather'           => $weatherAdvisory,
        ];
    }

    private function buildDeliveryStopPoints(Collection $orders, array $depot): array
    {
        $stops = [['id' => 'depot', 'lat' => $depot['lat'], 'lng' => $depot['lng']]];
        foreach ($orders as $o) {
            $stops[] = [
                'id'         => 'ord_'.$o->id,
                'lat'        => (float) $o->delivery_latitude,
                'lng'        => (float) $o->delivery_longitude,
                'weight_kg'  => (float) $o->total_kg,
                'earliest'   => null,
                'latest'     => null,
                'request_id' => $o->id,
            ];
        }

        return $stops;
    }

    private function binPackOrdersByCapacity(Collection $orders, Collection $trucks): array
    {
        $bins = $trucks->sortByDesc('capacity_kg')->values()
            ->map(fn ($truck) => ['truck' => $truck, 'requests' => collect(), 'load_kg' => 0.0])
            ->all();

        $unassigned = collect();

        foreach ($orders->sortByDesc('total_kg') as $order) {
            $weight = (float) $order->total_kg;
            $placed = false;

            foreach ($bins as &$bin) {
                if ($bin['load_kg'] + $weight <= (float) $bin['truck']->capacity_kg) {
                    $bin['requests']->push($order);
                    $bin['load_kg'] += $weight;
                    $placed = true;
                    break;
                }
            }
            unset($bin);

            if (! $placed) {
                $unassigned->push($order);
            }
        }

        $bins = array_values(array_filter($bins, fn ($b) => $b['requests']->isNotEmpty()));

        return ['groups' => $bins, 'unassigned' => $unassigned];
    }

    public function fetchFinalRouteForOrders(Collection $orderedOrders, Cooperative $cooperative): ?array
    {
        $depot = ['lat' => (float) ($cooperative->latitude ?? 0), 'lng' => (float) ($cooperative->longitude ?? 0)];
        $stops = $this->buildDeliveryStopPoints($orderedOrders, $depot);

        $points = collect($stops)->push($stops[0])
            ->map(fn ($s) => [$s['lat'], $s['lng']])
            ->all();

        return $this->routing->route($points);
    }

    public function buildScheduleForOrders(Collection $orderedOrders, Cooperative $cooperative): array
    {
        $depot = ['lat' => (float) ($cooperative->latitude ?? 0), 'lng' => (float) ($cooperative->longitude ?? 0)];
        $stops = $this->buildDeliveryStopPoints($orderedOrders, $depot);
        $matrix = $this->fetchRoadMatrix($stops);
        $orderedStopIds = $orderedOrders->map(fn ($o) => 'ord_'.$o->id)->all();

        return $this->buildArrivalSchedule($orderedStopIds, $stops, $matrix)['schedule'];
    }

    /**
     * Delivery personnel not already assigned to another active trip on this
     * date (spec 6.2/6.3 — "available personnel" is a planning input).
     *
     * When $nearTo is given (['lat'=>, 'lng'=>]), sorted by distance from
     * each driver's most recent GPS ping (TrackingRecord — drivers only post
     * while actively on a trip, so this is "nearest to wherever they last
     * worked," not a live position). A ping older than
     * config('harvesthaul.logistics.driver_position_max_age_days') is
     * treated as unreliable and ignored — those drivers sort after everyone
     * with a recent position, alphabetically among themselves. No position
     * data at all is not invented.
     */
    public function availableDrivers(Cooperative $cooperative, string $date, ?int $excludeHaulJobId = null, ?array $nearTo = null): Collection
    {
        $bookedDriverIds = HaulJob::where('cooperative_id', $cooperative->id)
            ->whereDate('pickup_date', $date)
            ->where('status', '!=', HaulJob::STATUS_CANCELLED)
            ->when($excludeHaulJobId, fn ($q) => $q->where('id', '!=', $excludeHaulJobId))
            ->whereNotNull('delivery_personnel_id')
            ->pluck('delivery_personnel_id');

        $drivers = User::where('role', UserRole::DELIVERY_PERSONNEL->value)
            ->where('cooperative_id', $cooperative->id)
            ->whereNotIn('id', $bookedDriverIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        if (! $nearTo) {
            return $drivers;
        }

        $maxAgeDays = (int) config('harvesthaul.logistics.driver_position_max_age_days', 7);
        $cutoff = now()->subDays($maxAgeDays);

        $lastPositions = \App\Models\TrackingRecord::whereIn('driver_id', $drivers->pluck('id'))
            ->where('posted_at', '>=', $cutoff)
            ->orderByDesc('posted_at')
            ->get()
            ->unique('driver_id')
            ->keyBy('driver_id');

        return $drivers->map(function ($driver) use ($lastPositions, $nearTo) {
            $position = $lastPositions->get($driver->id);
            $driver->distance_km = $position
                ? $this->haversine->distanceKm((float) $position->latitude, (float) $position->longitude, (float) $nearTo['lat'], (float) $nearTo['lng'])
                : null;

            return $driver;
        })->sort(function ($a, $b) {
            if ($a->distance_km === null && $b->distance_km === null) {
                return $a->name <=> $b->name;
            }
            if ($a->distance_km === null) {
                return 1;
            }
            if ($b->distance_km === null) {
                return -1;
            }

            return $a->distance_km <=> $b->distance_km ?: $a->name <=> $b->name;
        })->values();
    }

    private function buildStopPoints(Collection $requests, array $depot): array
    {
        $stops = [['id' => 'depot', 'lat' => $depot['lat'], 'lng' => $depot['lng']]];
        foreach ($requests as $r) {
            $stops[] = [
                'id'         => 'req_'.$r->id,
                'lat'        => (float) $r->pickup_location_lat,
                'lng'        => (float) $r->pickup_location_lng,
                'weight_kg'  => (float) $r->estimated_weight_kg,
                'earliest'   => $this->toMinutes($r->pickup_window_start),
                'latest'      => $this->toMinutes($r->pickup_window_end),
                'request_id' => $r->id,
            ];
        }

        return $stops;
    }

    private function fetchRoadMatrix(array $stops): array
    {
        $points = collect($stops)->map(fn ($s) => [$s['lat'], $s['lng']])->all();

        return $this->routing->table($points);
    }

    private function toMinutes(\Carbon\Carbon|string|null $time): ?float
    {
        if (! $time) {
            return null;
        }

        $hhmm = $time instanceof \Carbon\Carbon ? $time->format('H:i') : $time;

        return (float) explode(':', $hhmm)[0] * 60 + ((float) explode(':', $hhmm)[1]);
    }

    /**
     * Knapsack-style grouping (spec 6.3/6.4): first-fit-decrease — largest
     * requests first, into the largest trucks first. A request that fits no
     * available truck is "incompatible" (spec 6.3) and returned unassigned
     * rather than forced into an overloaded trip.
     */
    private function binPackByCapacity(Collection $requests, Collection $trucks, Cooperative $cooperative): array
    {
        // A cooperative can widen or tighten this from its own settings —
        // falls back to the platform default when unset.
        $maxRadiusKm = (float) ($cooperative->max_cluster_radius_km
            ?? config('harvesthaul.consolidation.max_cluster_radius_km', 20));

        $bins = $trucks->sortByDesc('capacity_kg')->values()
            ->map(fn ($truck) => ['truck' => $truck, 'requests' => collect(), 'load_kg' => 0.0])
            ->all();

        $unassigned = collect();

        foreach ($requests->sortByDesc('estimated_weight_kg') as $req) {
            $weight = (float) $req->estimated_weight_kg;
            $placed = false;

            foreach ($bins as &$bin) {
                $fitsCapacity = $bin['load_kg'] + $weight <= (float) $bin['truck']->capacity_kg;
                $fitsGeography = $bin['requests']->isEmpty()
                    || $this->withinClusterRadius($req, $bin['requests'], $maxRadiusKm);

                if ($fitsCapacity && $fitsGeography) {
                    $bin['requests']->push($req);
                    $bin['load_kg'] += $weight;
                    $placed = true;
                    break;
                }
            }
            unset($bin);

            if (! $placed) {
                $unassigned->push($req);
            }
        }

        $bins = array_values(array_filter($bins, fn ($b) => $b['requests']->isNotEmpty()));

        return ['groups' => $bins, 'unassigned' => $unassigned];
    }

    /**
     * Weight alone must not decide who shares a truck (spec: consolidation
     * is not based on capacity alone). Distance is measured from the
     * request's pickup point to the bin's current centroid — the average
     * lat/lng of stops already placed in it — rather than to the nearest
     * individual stop, so a group can't chain its way across a wide span
     * through a series of "close enough to the last one" hops.
     */
    private function withinClusterRadius(HaulRequest $req, Collection $binRequests, float $maxRadiusKm): bool
    {
        if ($req->pickup_location_lat === null || $req->pickup_location_lng === null) {
            return true; // no coordinates to filter on — don't block on missing data
        }

        $lats = $binRequests->pluck('pickup_location_lat')->filter(fn ($v) => $v !== null);
        $lngs = $binRequests->pluck('pickup_location_lng')->filter(fn ($v) => $v !== null);

        if ($lats->isEmpty()) {
            return true;
        }

        return $this->haversine->distanceKm(
            (float) $req->pickup_location_lat,
            (float) $req->pickup_location_lng,
            (float) $lats->avg(),
            (float) $lngs->avg(),
        ) <= $maxRadiusKm;
    }

    /**
     * Weather advisory for a cooperative's depot on a planned date (advisory
     * only — never blocks planning). weather.severity/forecast feed the
     * planning-page banner; weather.buffer feeds the ETA calculation below.
     * A failed/unavailable lookup degrades to 'unknown' severity and a 1.0
     * (no-op) buffer — planning behaves exactly as it did before this
     * feature existed.
     */
    private function weatherAdvisory(Cooperative $cooperative, string $date): array
    {
        $lat = (float) ($cooperative->latitude ?? 0);
        $lng = (float) ($cooperative->longitude ?? 0);

        $forecast = ($lat && $lng) ? $this->weather->forecastAt($lat, $lng, Carbon::parse($date)) : null;
        $severity = $forecast ? $this->weather->severity($forecast) : 'unknown';
        $buffer = $forecast ? $this->weather->etaBufferFor($severity) : 1.0;

        return compact('forecast', 'severity', 'buffer');
    }

    private function buildProposedPlan(array $group, array $stops, array $matrix, array $depot, float $weatherBuffer = 1.0): array
    {
        $truck = $group['truck'] ?? null;

        // Nearest-neighbor seed from the depot, then a single 2-opt pass.
        $orderedStopIds = $this->nearestNeighbor($stops, $depot, $matrix);
        $orderedStopIds = $this->twoOpt($orderedStopIds, $stops, $matrix, $weatherBuffer);

        $totalMin = 0.0;
        $totalKm  = 0.0;
        if ($matrix['source'] === 'osrm' && $matrix['durations']) {
            $idx = collect($orderedStopIds)->map(fn ($id) => array_search($id, array_column($stops, 'id')));
            // depot(0) → stops → depot
            $seq = [0, ...$idx->values()->all(), 0];
            // Walk by POSITION, not by value — $seq holds matrix indices as
            // its values, and a stop's index can coincide with an earlier
            // array position, which silently double-counted (or skipped)
            // legs when this looped "foreach ($seq as $i)" and reused $i as
            // a position into $seq itself.
            for ($p = 0; $p < count($seq) - 1; $p++) {
                $from = $seq[$p];
                $to   = $seq[$p + 1];
                $totalMin += (float) ($matrix['durations'][$from][$to] ?? 0);
                $totalKm  += ((float) ($matrix['distances'][$from][$to] ?? 0)) / 1000.0;
            }
        } else {
            $totalKm = $matrix['total_km'] ?? 0;
            $totalMin = $matrix['total_min'] ?? 0;
        }

        $arrival = $this->buildArrivalSchedule($orderedStopIds, $stops, $matrix, $weatherBuffer);
        $capacityKg = $truck ? (float) $truck->capacity_kg : 0;

        return [
            'truck'        => $truck,
            'stop_ids'     => $orderedStopIds,
            'distance_km'  => round($totalKm, 2),
            'travel_time'  => $this->formatMinutes($totalMin),
            'windows_ok'   => $arrival['windows_ok'],
            'schedule'     => $arrival['schedule'],
            'load_kg'      => round($group['load_kg'] ?? 0, 2),
            'utilization'  => $capacityKg ? round((($group['load_kg'] ?? 0) / $capacityKg) * 100, 1) : 0,
        ];
    }

    private function nearestNeighbor(array $stops, array $depot, array $matrix): array
    {
        $ids = array_values(array_filter(array_column($stops, 'id'), fn ($id) => $id !== 'depot'));

        if ($matrix['source'] === 'osrm' && $matrix['durations']) {
            $current = 0; // depot index
            $ordered = [];
            $remaining = $ids;
            while ($remaining) {
                $best = null;
                // [deadline (window close, or +INF when no window), travel
                // time] — picking the smallest pair puts the tightest
                // deadline first, falling back to nearest-by-time among
                // stops with the same (or no) deadline. A farmer whose
                // window closes soon must not be skipped for a farm that's
                // merely closer but has hours of slack.
                $bestKey = null;
                foreach ($remaining as $id) {
                    $idx = array_search($id, array_column($stops, 'id'));
                    $stop = collect($stops)->firstWhere('id', $id);
                    $deadline = $stop['latest'] ?? INF;
                    $d = (float) ($matrix['durations'][$current][$idx] ?? INF);
                    $key = [$deadline, $d];

                    if ($bestKey === null || $key < $bestKey) {
                        $bestKey = $key;
                        $best = $id;
                    }
                }
                $ordered[] = $best;
                $remaining = array_values(array_diff($remaining, [$best]));
                $current = array_search($best, array_column($stops, 'id'));
            }

            return $ordered;
        }

        // Haversine fallback ordering.
        usort($ids, fn ($a, $b) => $this->distanceToStop($a, $stops, $depot) <=> $this->distanceToStop($b, $stops, $depot));

        return $ids;
    }

    private function distanceToStop(string $id, array $stops, array $depot): float
    {
        $stop = collect($stops)->firstWhere('id', $id);

        return $this->haversine->distanceKm($depot['lat'], $depot['lng'], $stop['lat'], $stop['lng']);
    }

    private function twoOpt(array $order, array $stops, array $matrix, float $weatherBuffer = 1.0): array
    {
        if ($matrix['source'] !== 'osrm' || ! $matrix['durations']) {
            return $order;
        }

        $stopIds = array_column($stops, 'id');
        $idx = array_map(fn ($id) => array_search($id, $stopIds), $order);
        // Route is depot(0) → stops → depot(0).
        $seq = [0, ...$idx, 0];

        $toStopIds = fn (array $seq): array => array_map(
            fn ($i) => (string) $stops[$i]['id'],
            array_slice($seq, 1, count($seq) - 2)
        );
        $countViolations = fn (array $seq): int => collect(
            $this->buildArrivalSchedule($toStopIds($seq), $stops, $matrix, $weatherBuffer)['schedule']
        )->where('window_ok', false)->count();

        // A shorter total route is worthless if buying it means a farmer
        // gets missed by hours — a swap is only taken when it's both
        // faster AND doesn't newly break a pickup window that wasn't
        // already broken in the order it's replacing.
        $currentViolations = $countViolations($seq);

        $improved = true;
        while ($improved) {
            $improved = false;
            for ($i = 1; $i < count($seq) - 2; $i++) {
                for ($j = $i + 1; $j < count($seq) - 1; $j++) {
                    $before = ($matrix['durations'][$seq[$i - 1]][$seq[$i]] ?? 0)
                        + ($matrix['durations'][$seq[$j]][$seq[$j + 1]] ?? 0);
                    $after = ($matrix['durations'][$seq[$i - 1]][$seq[$j]] ?? 0)
                        + ($matrix['durations'][$seq[$i]][$seq[$j + 1]] ?? 0);

                    if ($before - $after <= 0.000001) {
                        continue;
                    }

                    $candidate = $seq;
                    $this->reverseSlice($candidate, $i, $j);
                    $candidateViolations = $countViolations($candidate);

                    if ($candidateViolations > $currentViolations) {
                        continue;
                    }

                    $seq = $candidate;
                    $currentViolations = $candidateViolations;
                    $improved = true;
                }
            }
        }

        return $toStopIds($seq);
    }

    private function reverseSlice(array &$seq, int $start, int $end): void
    {
        while ($start < $end) {
            $tmp = $seq[$start];
            $seq[$start] = $seq[$end];
            $seq[$end] = $tmp;
            $start++;
            $end--;
        }
    }

    /**
     * Per-stop arrival schedule (spec 7.7) — arrival/wait/departure computed
     * from actual leg travel time (matrix or haversine fallback), not just an
     * aggregate ok/not-ok flag. Service duration per stop is configurable and
     * scales for large loads (spec 7.8) instead of a hardcoded constant.
     */
    private function buildArrivalSchedule(array $orderedStopIds, array $stops, array $matrix, float $weatherBuffer = 1.0): array
    {
        $baseService = (float) config('harvesthaul.pickup.base_service_minutes', 15);
        $largeService = (float) config('harvesthaul.pickup.large_load_service_minutes', 30);
        $threshold = (float) config('harvesthaul.pickup.large_load_threshold_kg', 2000);

        $schedule = [];
        $windowsOk = true;
        // A farmer's pickup_window_start/end (earliest/latest below) are
        // clock time as minutes-since-midnight — arrival/departure must
        // start on that same clock, not at 0 (minutes since the truck left
        // the depot), or every window comparison compares two different
        // clocks and produces nonsense wait times.
        $departure = (float) config('harvesthaul.pickup.dispatch_start_minutes', 360);
        $previousId = 'depot';

        foreach ($orderedStopIds as $id) {
            $stop = collect($stops)->firstWhere('id', $id);
            if (! $stop) {
                continue;
            }

            // Weather buffer (1.0 = no change) applied to travel time only,
            // before the window check below — so a weather-delayed schedule
            // also makes the existing "misses the farmer's pickup window"
            // detection more accurate, not just a display number.
            $travel = $this->legMinutes($stops, $matrix, $previousId, $id) * $weatherBuffer;
            $arrival = $departure + $travel;

            $waitMin = 0.0;
            if ($stop['earliest'] !== null) {
                $waitMin = max(0.0, $stop['earliest'] - $arrival);
            }

            $stopWindowOk = true;
            if ($stop['latest'] !== null && $arrival > $stop['latest']) {
                $stopWindowOk = false;
                $windowsOk = false;
            }

            $service = ($stop['weight_kg'] ?? 0) >= $threshold ? $largeService : $baseService;
            $departure = $arrival + $waitMin + $service;

            $schedule[] = [
                'request_id'    => $stop['request_id'] ?? null,
                'stop_id'       => $id,
                'arrival_min'   => round($arrival, 1),
                'wait_min'      => round($waitMin, 1),
                'departure_min' => round($departure, 1),
                'window_ok'     => $stopWindowOk,
            ];

            $previousId = $id;
        }

        return ['schedule' => $schedule, 'windows_ok' => $windowsOk];
    }

    private function legMinutes(array $stops, array $matrix, string $fromId, string $toId): float
    {
        if ($matrix['source'] === 'osrm' && $matrix['durations']) {
            $stopIds = array_column($stops, 'id');
            $i = array_search($fromId, $stopIds);
            $j = array_search($toId, $stopIds);

            return (float) ($matrix['durations'][$i][$j] ?? 0);
        }

        $from = collect($stops)->firstWhere('id', $fromId);
        $to = collect($stops)->firstWhere('id', $toId);

        if (! $from || ! $to) {
            return 0.0;
        }

        return $this->haversine->distanceKm($from['lat'], $from['lng'], $to['lat'], $to['lng']) / 25.0 * 60.0;
    }

    /**
     * Final route geometry (spec 7.10) — called once a stop order is
     * accepted (trip creation), separate from the `/table` matrix used
     * during planning. Advisory: a failure here must not block trip
     * creation, so it returns null rather than throwing.
     */
    public function fetchFinalRoute(Collection $orderedRequests, Cooperative $cooperative): ?array
    {
        $depot = ['lat' => (float) ($cooperative->latitude ?? 0), 'lng' => (float) ($cooperative->longitude ?? 0)];
        $stops = $this->buildStopPoints($orderedRequests, $depot);

        $points = collect($stops)->push($stops[0])
            ->map(fn ($s) => [$s['lat'], $s['lng']])
            ->all();

        return $this->routing->route($points);
    }

    /**
     * Arrival schedule for an already-decided stop order (human-reviewed),
     * used to populate HaulJobStop.planned_arrival_at at trip creation.
     */
    public function buildScheduleForRequests(Collection $orderedRequests, Cooperative $cooperative): array
    {
        $depot = ['lat' => (float) ($cooperative->latitude ?? 0), 'lng' => (float) ($cooperative->longitude ?? 0)];
        $stops = $this->buildStopPoints($orderedRequests, $depot);
        $matrix = $this->fetchRoadMatrix($stops);
        $orderedStopIds = $orderedRequests->map(fn ($r) => 'req_'.$r->id)->all();

        return $this->buildArrivalSchedule($orderedStopIds, $stops, $matrix)['schedule'];
    }

    private function capacityBreakdown(array $groups): array
    {
        return collect($groups)->map(function ($g) {
            $cap = $g['truck'] ? (float) $g['truck']->capacity_kg : 1;
            $cap = $cap ?: 1;

            return [
                'truck'   => $g['truck'] ? $g['truck']->truck_name.' ('.$g['truck']->plate_number.')' : 'No truck available',
                'load_kg' => round($g['load_kg'], 2),
                'cap_kg'  => round($cap, 2),
                'pct'     => round(($g['load_kg'] / $cap) * 100, 1),
            ];
        })->toArray();
    }

    private function formatMinutes(float $minutes): string
    {
        if (! $minutes) {
            return '—';
        }

        $h = floor($minutes / 60);
        $m = round($minutes % 60);

        return $h > 0 ? "{$h} h {$m} m" : "{$m} min";
    }
}
