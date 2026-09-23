<?php

namespace App\Services\Routing;

use App\Models\RouteCalculation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OSRM-backed routing service. Builds URLs, sends requests, parses
 * responses, caches results, and throttles live calls to the shared public
 * OSRM instance — controllers/ConsolidationEngine never see raw OSRM
 * responses or know the endpoint exists (spec: "Do not hard-code the OSRM
 * URL... never call OSRM directly from Blade/JavaScript for core routing").
 */
class OsrmRoutingService implements RoutingServiceContract
{
    public function __construct(private HaversineService $haversine) {}

    public function table(array $points): array
    {
        $coords = $this->coordString($points);
        $cached = $this->readCache('table', $coords);
        if ($cached) {
            return $cached;
        }

        $url = $this->baseUrl().'/table/v1/'.$this->profile().'/'.$coords.'?annotations=distance,duration';

        try {
            $this->throttle();
            $res = $this->request()->get($url);

            if ($res->ok() && $res->json('code') === 'Ok') {
                // OSRM returns /table durations in SECONDS. Convert once,
                // here, so every consumer of $matrix['durations'] (advisory
                // travel time, arrival schedule, time-window check) can
                // trust the value is already in minutes.
                $durations = collect($res->json('durations'))
                    ->map(fn ($row) => array_map(fn ($v) => $v / 60.0, $row));
                $distances = collect($res->json('distances'));

                // durations[0]/distances[0] are plain arrays (the depot row),
                // not Collections — array_sum, not optional()->sum() (which
                // only forwards to objects and silently returns null here).
                $result = [
                    'source'    => 'osrm',
                    'durations' => $durations,
                    'distances' => $distances,
                    'total_min' => (float) array_sum($durations[0] ?? []),
                    'total_km'  => round(((float) array_sum($distances[0] ?? [])) / 1000.0, 2),
                ];

                $this->writeCache('table', $coords, $result);

                return $result;
            }
        } catch (\Throwable $e) {
            Log::info('OSRM table request failed; using advisory distance.', ['error' => $e->getMessage()]);
        }

        $km = $this->haversine->closedLoopKm($points);

        return [
            'source'    => 'advisory',
            'durations' => null,
            'distances' => null,
            'total_min' => $this->haversine->estimatedMinutesForKm($km),
            'total_km'  => round($km, 2),
        ];
    }

    public function route(array $points): ?array
    {
        $coords = $this->coordString($points);
        $cached = $this->readCache('route', $coords);
        if ($cached) {
            return $cached;
        }

        $url = $this->baseUrl().'/route/v1/'.$this->profile().'/'.$coords.'?overview=full&geometries=geojson';

        try {
            $this->throttle();
            $res = $this->request()->get($url);

            if ($res->ok() && $res->json('code') === 'Ok') {
                $route = $res->json('routes.0');

                $result = [
                    'distance_km'  => round(($route['distance'] ?? 0) / 1000, 2),
                    'duration_min' => round(($route['duration'] ?? 0) / 60, 2),
                    'geometry'     => $route['geometry']['coordinates'] ?? null,
                ];

                $this->writeCache('route', $coords, $result);

                return $result;
            }
        } catch (\Throwable $e) {
            Log::info('OSRM route request failed.', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /** Live connectivity probe for `php artisan routing:health`. */
    public function checkHealth(): array
    {
        $depot = [7.0, 125.0];
        $stop = [7.01, 125.01];

        $tableOk = false;
        $routeOk = false;
        $error = null;

        try {
            $res = $this->request()->get($this->baseUrl().'/table/v1/'.$this->profile().'/'.$this->coordString([$depot, $stop]).'?annotations=distance,duration');
            $tableOk = $res->ok() && $res->json('code') === 'Ok';
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        try {
            $res = $this->request()->get($this->baseUrl().'/route/v1/'.$this->profile().'/'.$this->coordString([$depot, $stop]).'?overview=false');
            $routeOk = $res->ok() && $res->json('code') === 'Ok';
        } catch (\Throwable $e) {
            $error ??= $e->getMessage();
        }

        return [
            'provider' => 'osrm',
            'endpoint' => $this->baseUrl(),
            'table_ok' => $tableOk,
            'route_ok' => $routeOk,
            'error'    => $error,
        ];
    }

    private function request()
    {
        return Http::timeout((int) config('routing.osrm.timeout', 10))
            ->retry((int) config('routing.osrm.retry_times', 2), (int) config('routing.osrm.retry_sleep', 500));
    }

    private function throttle(): void
    {
        $minIntervalMs = (int) config('routing.osrm.min_interval_ms', 0);
        if ($minIntervalMs <= 0) {
            return;
        }

        $lastAt = Cache::get('osrm:last_request_at');
        if ($lastAt) {
            $elapsedMs = (microtime(true) - $lastAt) * 1000;
            $remainingMs = $minIntervalMs - $elapsedMs;
            if ($remainingMs > 0) {
                usleep((int) ($remainingMs * 1000));
            }
        }

        Cache::put('osrm:last_request_at', microtime(true), now()->addMinutes(5));
    }

    /** @param  array<int, array{0: float, 1: float}>  $points */
    private function coordString(array $points): string
    {
        return collect($points)->map(fn ($p) => $p[1].','.$p[0])->implode(';');
    }

    private function readCache(string $kind, string $coords): ?array
    {
        $row = RouteCalculation::query()
            ->notExpired()
            ->where('provider', 'osrm')
            ->where('profile', $this->profile())
            ->where('kind', $kind)
            ->where('coordinates_hash', $this->hash($coords))
            ->first();

        // Plain nested arrays after the model's array cast decodes the JSON —
        // consumers index them with [$i][$j], which works the same as the
        // Collection-of-rows shape a live fetch returns.
        return $row ? $row->result : null;
    }

    private function writeCache(string $kind, string $coords, array $result): void
    {
        RouteCalculation::updateOrCreate(
            [
                'provider'         => 'osrm',
                'profile'          => $this->profile(),
                'kind'             => $kind,
                'coordinates_hash' => $this->hash($coords),
            ],
            [
                'result'       => $result,
                'calculated_at' => now(),
                'expires_at'   => now()->addMinutes((int) config('routing.cache_ttl_minutes', 360)),
            ]
        );
    }

    private function hash(string $coords): string
    {
        return hash('sha256', $coords);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('routing.osrm.base_url', 'https://router.project-osrm.org'), '/');
    }

    private function profile(): string
    {
        return (string) config('routing.osrm.profile', 'driving');
    }
}
