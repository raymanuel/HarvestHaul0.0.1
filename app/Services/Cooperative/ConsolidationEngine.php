<?php

namespace App\Services\Cooperative;

use App\Models\Cooperative;
use App\Models\HaulJob;
use App\Models\HaulRequest;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Routing\HaversineService;
use App\Services\Routing\RoutingServiceContract;
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

        $packed = $this->binPackByCapacity($requests, $trucks);

        $groups = [];
        foreach ($packed['groups'] as $i => $bin) {
            $stops = $this->buildStopPoints($bin['requests'], $depot);
            $matrix = $this->fetchRoadMatrix($stops);
            $proposed = $this->buildProposedPlan($bin, $stops, $matrix, $depot);

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
        ];
    }

    /**
     * Delivery personnel not already assigned to another active trip on this
     * date (spec 6.2/6.3 — "available personnel" is a planning input).
     */
    public function availableDrivers(Cooperative $cooperative, string $date, ?int $excludeHaulJobId = null): Collection
    {
        $bookedDriverIds = HaulJob::where('cooperative_id', $cooperative->id)
            ->whereDate('pickup_date', $date)
            ->where('status', '!=', HaulJob::STATUS_CANCELLED)
            ->when($excludeHaulJobId, fn ($q) => $q->where('id', '!=', $excludeHaulJobId))
            ->whereNotNull('delivery_personnel_id')
            ->pluck('delivery_personnel_id');

        return User::where('role', UserRole::DELIVERY_PERSONNEL->value)
            ->where('cooperative_id', $cooperative->id)
            ->whereNotIn('id', $bookedDriverIds)
            ->orderBy('name')
            ->get(['id', 'name']);
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
    private function binPackByCapacity(Collection $requests, Collection $trucks): array
    {
        $bins = $trucks->sortByDesc('capacity_kg')->values()
            ->map(fn ($truck) => ['truck' => $truck, 'requests' => collect(), 'load_kg' => 0.0])
            ->all();

        $unassigned = collect();

        foreach ($requests->sortByDesc('estimated_weight_kg') as $req) {
            $weight = (float) $req->estimated_weight_kg;
            $placed = false;

            foreach ($bins as &$bin) {
                if ($bin['load_kg'] + $weight <= (float) $bin['truck']->capacity_kg) {
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

    private function buildProposedPlan(array $group, array $stops, array $matrix, array $depot): array
    {
        $truck = $group['truck'] ?? null;

        // Nearest-neighbor seed from the depot, then a single 2-opt pass.
        $orderedStopIds = $this->nearestNeighbor($stops, $depot, $matrix);
        $orderedStopIds = $this->twoOpt($orderedStopIds, $stops, $matrix);

        $totalMin = 0.0;
        $totalKm  = 0.0;
        if ($matrix['source'] === 'osrm' && $matrix['durations']) {
            $idx = collect($orderedStopIds)->map(fn ($id) => array_search($id, array_column($stops, 'id')));
            // depot(0) → stops → depot
            $seq = [0, ...$idx->values()->all(), 0];
            foreach ($seq as $i) {
                $next = $seq[$i + 1] ?? null;
                if ($next !== null) {
                    $totalMin += (float) $matrix['durations'][$i][$next];
                    $totalKm  += ((float) $matrix['distances'][$i][$next]) / 1000.0;
                }
            }
        } else {
            $totalKm = $matrix['total_km'] ?? 0;
            $totalMin = $matrix['total_min'] ?? 0;
        }

        $arrival = $this->buildArrivalSchedule($orderedStopIds, $stops, $matrix);
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
                $nearest = null;
                $best = INF;
                foreach ($remaining as $i => $id) {
                    $idx = array_search($id, array_column($stops, 'id'));
                    $d = (float) $matrix['durations'][$current][$idx] ?? INF;
                    if ($d < $best) {
                        $best = $d;
                        $nearest = $id;
                    }
                }
                $ordered[] = $nearest;
                $remaining = array_values(array_diff($remaining, [$nearest]));
                $current = array_search($nearest, array_column($stops, 'id'));
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

    private function twoOpt(array $order, array $stops, array $matrix): array
    {
        if ($matrix['source'] !== 'osrm' || ! $matrix['durations']) {
            return $order;
        }

        $stopIds = array_column($stops, 'id');
        $idx = array_map(fn ($id) => array_search($id, $stopIds), $order);
        // Route is depot(0) → stops → depot(0).
        $seq = [0, ...$idx, 0];

        $improved = true;
        while ($improved) {
            $improved = false;
            for ($i = 1; $i < count($seq) - 2; $i++) {
                for ($j = $i + 1; $j < count($seq) - 1; $j++) {
                    $before = ($matrix['durations'][$seq[$i - 1]][$seq[$i]] ?? 0)
                        + ($matrix['durations'][$seq[$j]][$seq[$j + 1]] ?? 0);
                    $after = ($matrix['durations'][$seq[$i - 1]][$seq[$j]] ?? 0)
                        + ($matrix['durations'][$seq[$i]][$seq[$j + 1]] ?? 0);

                    if ($before - $after > 0.000001) {
                        $this->reverseSlice($seq, $i, $j);
                        $improved = true;
                    }
                }
            }
        }

        return array_map(
            fn ($i) => (string) $stops[$i]['id'],
            array_slice($seq, 1, count($seq) - 2)
        );
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
    private function buildArrivalSchedule(array $orderedStopIds, array $stops, array $matrix): array
    {
        $baseService = (float) config('harvesthaul.pickup.base_service_minutes', 15);
        $largeService = (float) config('harvesthaul.pickup.large_load_service_minutes', 30);
        $threshold = (float) config('harvesthaul.pickup.large_load_threshold_kg', 2000);

        $schedule = [];
        $windowsOk = true;
        $departure = 0.0;
        $previousId = 'depot';

        foreach ($orderedStopIds as $id) {
            $stop = collect($stops)->firstWhere('id', $id);
            if (! $stop) {
                continue;
            }

            $travel = $this->legMinutes($stops, $matrix, $previousId, $id);
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
