<?php

namespace App\Services\Routing;

/**
 * Straight-line distance only — preliminary geographic filtering and the
 * fallback used when OSRM is unavailable. Never represent this as road
 * distance (spec: "Never represent Haversine distance as road distance").
 */
class HaversineService
{
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $p1 = deg2rad($lat1);
        $p2 = deg2rad($lat2);
        $d = deg2rad($lng2 - $lng1);
        $a = sin(($p2 - $p1) / 2) ** 2 + cos($p1) * cos($p2) * sin($d / 2) ** 2;

        return $earthRadiusKm * 2 * asin(min(1.0, sqrt($a)));
    }

    /**
     * Closed-loop total (points[0] → ... → points[last] → points[0]) in km.
     *
     * @param  array<int, array{0: float, 1: float}>  $points  [lat, lng] pairs
     */
    public function closedLoopKm(array $points): float
    {
        if (count($points) < 2) {
            return 0.0;
        }

        $total = 0.0;
        for ($i = 0; $i < count($points) - 1; $i++) {
            $total += $this->distanceKm($points[$i][0], $points[$i][1], $points[$i + 1][0], $points[$i + 1][1]);
        }
        $total += $this->distanceKm($points[count($points) - 1][0], $points[count($points) - 1][1], $points[0][0], $points[0][1]);

        return $total;
    }

    // ~25 km/h average for narrow farm roads — advisory only.
    public function estimatedMinutesForKm(float $km): float
    {
        return round($km / 25.0 * 60.0, 0);
    }
}
