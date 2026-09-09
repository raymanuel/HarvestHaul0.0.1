<?php

namespace App\Traits;

trait HasNearbyScope
{
    /**
     * Bounding box pre-filter for proximity queries.
     * Returns records within the approximate rectangular bounds of $radiusKm
     * around the given coordinates. Apply Haversine in PHP for precise distance.
     */
    public function scopeNearby($query, float $lat, float $lng, float $radiusKm)
    {
        $latOffset = $radiusKm / 111.32;
        $lngOffset = $radiusKm / (111.32 * cos(deg2rad($lat)));

        return $query->whereBetween('latitude', [$lat - $latOffset, $lat + $latOffset])
                     ->whereBetween('longitude', [$lng - $lngOffset, $lng + $lngOffset]);
    }
}
