<?php

namespace App\Http\Controllers;

use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
use App\Models\TrackingRecord;
use App\Services\ETAService;
use App\Traits\GeometryHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class TrackingController
 * 
 * Handles real-time GPS telemetry for active jobs.
 * 
 * Flow:
 * 1. Ingress (store): Active driver's mobile client registers periodic location updates
 *    (latitude, longitude) and sends them here. Works offline via sw.js queue.
 * 2. Egress (latest): Logistics manager's dashboard checks driver's current coordinates
 *    to display live updates on the tracking map interface.
 */
class TrackingController extends Controller
{
    use GeometryHelper;

    /**
     * Unified tracking dashboard view for farmers, logistics partners, and buyers.
     */
    public function index()
    {
        $user = Auth::user();
        
        $query = PoolingJob::whereIn('status', ['confirmed', 'in_progress', 'awaiting_confirmation']);
        
        if ($user->role === 'farmer') {
            $query->whereHas('harvests', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->role === 'logistics_partner') {
            $query->where('logistics_profile_id', $user->logisticsProfile?->id);
        } elseif ($user->role === 'buyer') {
            $query->where('buyer_id', $user->id);
        } else {
            abort(403);
        }
        
        $activeJobs = $query->with(['truck', 'driver', 'harvests.crop', 'harvests.farmer.farmerProfile', 'harvests.destination', 'latestTracking'])->latest()->take(50)->get();
        
        return view('tracking.index', compact('activeJobs'));
    }

    /**
     * INGRESS ENDPOINT: Drivers stream GPS coordinates to this endpoint every 15 seconds.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pooling_job_id'  => 'required|integer|exists:pooling_jobs,id',
            'latitude'        => 'required|numeric|between:-90,90',
            'longitude'       => 'required|numeric|between:-180,180',
            'accuracy_meters' => 'nullable|numeric|min:0|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $job = PoolingJob::findOrFail($request->pooling_job_id);

        // Security Validation: Ensure the authenticated user is the assigned driver for this trip
        if ($job->driver_id !== Auth::id()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized fleet streaming.'], 403);
        }

        // Allow GPS pings when job is in_progress or confirmed (pre-flight tracking)
        if ($job->status !== PoolingJobStatus::IN_PROGRESS && $job->status !== PoolingJobStatus::CONFIRMED) {
            return response()->json(['status' => 'error', 'message' => 'Job is not in transit.'], 422);
        }

        // Rate limiting: max 1 ping per 5 seconds per driver
        $recentPing = TrackingRecord::where('driver_id', Auth::id())
            ->where('posted_at', '>=', now()->subSeconds(5))
            ->exists();
        if ($recentPing) {
            return response()->json(['status' => 'success', 'message' => 'Rate limited.']); // silently accept but don't store
        }

        // GPS accuracy filter: reject if accuracy > 500m
        $accuracy = $request->float('accuracy_meters');
        if ($accuracy !== null && $accuracy > 500) {
            return response()->json(['status' => 'success', 'message' => 'GPS accuracy too low.']); // silently accept but don't store
        }

        // Deduplication: skip if same coords within 30 seconds
        $lastRecord = TrackingRecord::where('pooling_job_id', $job->id)
            ->latest('id')
            ->first();
        if ($lastRecord) {
            $samePos = abs((float) $lastRecord->latitude - (float) $request->latitude) < 0.0001
                && abs((float) $lastRecord->longitude - (float) $request->longitude) < 0.0001;
            $recentTime = $lastRecord->posted_at->diffInSeconds(now()) < 30;
            if ($samePos && $recentTime) {
                return response()->json(['status' => 'success', 'message' => 'Duplicate ping ignored.']);
            }
        }

        // Compute speed and bearing from last known position
        $speedKmh = null;
        $bearing = null;

        if ($lastRecord) {
            $prevLat = (float) $lastRecord->latitude;
            $prevLng = (float) $lastRecord->longitude;
            $newLat = (float) $request->latitude;
            $newLng = (float) $request->longitude;

            $timeDiff = $lastRecord->posted_at->diffInSeconds(now());
            if ($timeDiff > 0) {
                $dist = $this->haversine($prevLat, $prevLng, $newLat, $newLng);
                $speedKmh = round(($dist / $timeDiff) * 3600, 2);
            }

            $bearing = $this->calculateBearing($prevLat, $prevLng, $newLat, $newLng);
        }

        // Lightweight write to database
        $record = TrackingRecord::create([
            'pooling_job_id'  => $job->id,
            'driver_id'       => Auth::id(),
            'latitude'        => (float) $request->latitude,
            'longitude'       => (float) $request->longitude,
            'speed_kmh'       => $speedKmh,
            'bearing'         => $bearing,
            'accuracy_meters' => $accuracy,
            'posted_at'       => now(),
        ]);

        // Real-time broadcast is handled by WebSocketServer (polls tracking_records from DB every 2s).
        // No per-request socket connect needed.

        // Return minimal payload for mobile bandwidth efficiency
        return response()->json(['status' => 'success'], 201);
    }

    /**
     * EGRESS ENDPOINT: The dashboards poll this endpoint every 10 seconds.
     */
    public function latest($jobId)
    {
        $user = Auth::user();
        $job = PoolingJob::findOrFail($jobId);
        
        $authorized = false;
        if ($user->role === 'admin') {
            $authorized = true;
        } elseif ($user->role === 'logistics_partner' && $job->logistics_profile_id === $user->logisticsProfile?->id) {
            $authorized = true;
        } elseif ($user->role === 'driver' && $job->driver_id === $user->id) {
            $authorized = true;
        } elseif ($user->role === 'buyer' && $job->buyer_id === $user->id) {
            $authorized = true;
        } elseif ($user->role === 'farmer') {
            $hasHarvest = $job->harvests()->where('user_id', $user->id)->exists();
            if ($hasHarvest) {
                $authorized = true;
            }
        }
        
        if (!$authorized) {
            return response()->json(['status' => 'error', 'message' => 'Access denied.'], 403);
        }

        // Fetch absolute newest entry recorded for this fleet tracking route
        $latestNode = TrackingRecord::where('pooling_job_id', $jobId)
            ->latest('posted_at') // Use posted_at instead of id for chronological ordering
            ->first();

        if (!$latestNode) {
            return response()->json([
                'status' => 'empty',
                'message' => 'No GPS telemetry recorded yet for this active route.'
            ], 200);
        }

        // Synchronized to match JavaScript payload keys in your route-optimization blade view
        return response()->json([
            'status' => 'success',
            'data'   => [
                'latitude'  => (float) $latestNode->latitude,
                'longitude' => (float) $latestNode->longitude,
                'speed_kmh' => (float) ($latestNode->speed_kmh ?? 0),
                'bearing'   => (float) ($latestNode->bearing ?? 0),
                'posted_at' => $latestNode->posted_at->toIso8601String(),
            ]
        ], 200);
    }

    /**
     * ETA endpoint — returns estimated arrival time for a job.
     */
    public function eta($jobId)
    {
        $user = Auth::user();
        $job = PoolingJob::findOrFail($jobId);

        $authorized = $this->isAuthorizedForJob($user, $job);
        if (!$authorized) {
            return response()->json(['status' => 'error', 'message' => 'Access denied.'], 403);
        }

        // Basic rate limit: cache the ETA for 10 seconds to prevent over-fetching
        $cacheKey = "eta_job_{$jobId}";
        $eta = \Illuminate\Support\Facades\Cache::remember($cacheKey, 10, function () use ($job) {
            $etaService = app(ETAService::class);
            return $etaService->getETAForJob($job);
        });

        return response()->json([
            'status' => 'success',
            'data'   => $eta,
        ], 200);
    }

    /**
     * Calculate bearing (azimuth) between two GPS points.
     */
    private function calculateBearing(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLng = deg2rad($lng2 - $lng1);
        $y = sin($dLng) * cos(deg2rad($lat2));
        $x = cos(deg2rad($lat1)) * sin(deg2rad($lat2)) - sin(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos($dLng);
        return (rad2deg(atan2($y, $x)) + 360) % 360;
    }

    /**
     * Check if user is authorized to view tracking for a job.
     */
    private function isAuthorizedForJob($user, $job): bool
    {
        if ($user->role === 'admin') return true;
        if ($user->role === 'logistics_partner' && $job->logistics_profile_id === $user->logisticsProfile?->id) return true;
        if ($user->role === 'driver' && $job->driver_id === $user->id) return true;
        if ($user->role === 'buyer' && $job->buyer_id === $user->id) return true;
        if ($user->role === 'farmer') {
            return $job->harvests()->where('user_id', $user->id)->exists();
        }
        return false;
    }
}
