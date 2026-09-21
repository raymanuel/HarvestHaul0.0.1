<?php

namespace App\Services\Routing;

interface RoutingServiceContract
{
    /**
     * Distance/duration matrix for an ordered list of [lat, lng] points
     * (index 0 is the depot by convention — callers decide the order).
     *
     * Returns:
     *   source: 'osrm'|'advisory' — 'advisory' means the live request failed
     *           and the figures are a haversine fallback, never to be shown
     *           as road distance.
     *   durations: Collection<int, Collection<int, float>>|null seconds matrix
     *   distances: Collection<int, Collection<int, float>>|null meters matrix
     *   total_min, total_km: float — closed-loop total (depot → all → depot)
     */
    public function table(array $points): array;

    /**
     * Road route geometry/distance/duration for an ordered sequence of
     * [lat, lng] points. Returns null if no route could be determined
     * (advisory-only feature — callers must not block on this).
     */
    public function route(array $points): ?array;
}
