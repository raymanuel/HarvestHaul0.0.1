<?php

namespace App\Services\Geocoding;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reverse-geocodes a coordinate to a human-readable address via OpenStreetMap's
 * free Nominatim service. Never throws — a lookup failure just means the caller
 * gets back null and falls back to whatever the user typed themselves.
 */
class NominatimGeocodingService
{
    /**
     * Look up the display address for a coordinate. Returns null on any failure
     * (network error, non-200, missing field) — callers must treat this as
     * "no address available", never as an error to surface.
     */
    public function reverse(float $lat, float $lng): ?string
    {
        $cacheKey = sprintf('geocode:reverse:%.5F,%.5F', $lat, $lng);

        // Only successful lookups are cached — a transient outage must not
        // poison the cache with a null result for a full day.
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => config('services.nominatim.user_agent'),
                'Accept-Language' => 'en',
            ])->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'json',
                'lat' => $lat,
                'lon' => $lng,
                'zoom' => 18,
                'addressdetails' => 1,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $address = $response->json('display_name');

            if ($address) {
                Cache::put($cacheKey, $address, now()->addDay());
            }

            return $address;
        } catch (Throwable $e) {
            Log::warning('Nominatim reverse geocoding failed', ['lat' => $lat, 'lng' => $lng, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
