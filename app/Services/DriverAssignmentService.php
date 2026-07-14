<?php

namespace App\Services;

use App\Models\PoolingJob;
use App\Models\Truck;
use App\Models\User;
use App\Models\DriverProfile;
use App\Traits\GeometryHelper;
use Illuminate\Support\Collection;

class DriverAssignmentService
{
    use GeometryHelper;

    public function findNearestAvailableDriver(float $pickupLat, float $pickupLng, int $logisticsProfileId, ?float $maxRadiusKm = 50): ?array
    {
        $drivers = $this->getAvailableDrivers($logisticsProfileId);

        if ($drivers->isEmpty()) return null;

        $closest = null;
        $closestDist = PHP_FLOAT_MAX;

        foreach ($drivers as $driver) {
            // Use real-time heartbeat GPS if available (within last 5 min), else fallback to profile
            $heartbeat = \App\Models\DriverHeartbeat::where('driver_id', $driver->id)
                ->where('reported_at', '>=', now()->subMinutes(5))
                ->latest('reported_at')
                ->first();

            if ($heartbeat) {
                $lat = (float) $heartbeat->latitude;
                $lng = (float) $heartbeat->longitude;
            } else {
                $lat = $driver->driverProfile?->latitude ?? $driver->latitude;
                $lng = $driver->driverProfile?->longitude ?? $driver->longitude;
            }

            if (!$lat || !$lng) continue;

            // Check driver rest period — not assigned if shift ended within last 8 hours
            if ($driver->driverProfile?->last_shift_ended_at) {
                $shiftEnd = now()->setTimeFromTimeString($driver->driverProfile->last_shift_ended_at);
                if ($shiftEnd->diffInHours(now()) < 8) {
                    continue;
                }
            }

            $dist = $this->haversine($pickupLat, $pickupLng, (float) $lat, (float) $lng);
            if ($dist < $closestDist) {
                $closestDist = $dist;
                $closest = [
                    'driver' => $driver,
                    'distance_km' => round($dist, 2),
                    'truck' => $this->getBestTruckForDriver($driver, $logisticsProfileId),
                ];
            }
        }

        if (!$closest || ($maxRadiusKm && $closest['distance_km'] > $maxRadiusKm)) return null;

        return $closest;
    }

    public function getAvailableDrivers(int $logisticsProfileId): Collection
    {
        $busyDriverIds = PoolingJob::whereIn('status', [
            'confirmed', 'in_progress', 'pending', 'awaiting_confirmation'
        ])->pluck('driver_id')->unique();

        return User::where('role', 'driver')
            ->whereHas('driverProfile', function ($q) use ($logisticsProfileId) {
                $q->where('partner_id', $logisticsProfileId)
                  ->where('employment_status', 'active');
            })
            ->whereNotIn('id', $busyDriverIds)
            ->with('driverProfile')
            ->get();
    }

    public function assignDriver(Truck $truck, int $driverId): void
    {
        $truck->update(['driver_id' => $driverId]);
        DriverProfile::where('user_id', $driverId)->update(['last_assigned_at' => now()]);
    }

    private function getBestTruckForDriver(User $driver, int $logisticsProfileId): ?Truck
    {
        return Truck::where('logistics_profile_id', $logisticsProfileId)
            ->where('status', 'available')
            ->where('driver_id', $driver->id)
            ->with('driver')
            ->first();
    }
}
