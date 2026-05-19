<?php

namespace App\Http\Controllers;

use App\Models\PoolingJob;
use App\Models\TrackingRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackingController extends Controller
{
    /**
     * DRIVER ENDPOINT (POST)
     * Receives GPS coordinates every 15 seconds from the driver's browser.
     */
    public function store(Request $request)
    {
        $request->validate([
            'pooling_job_id' => 'required|exists:pooling_jobs,id',
            'latitude'       => 'required|numeric',
            'longitude'      => 'required|numeric',
            'posted_at'      => 'required|date',
        ]);

        $driverId = Auth::id();

        // Security Gate: Ensure the job belongs to this driver AND is currently active.
        // If a job is completed, we want the browser loop to stop tracking automatically.
        $job = PoolingJob::where('id', $request->pooling_job_id)
            ->where('driver_id', $driverId)
            ->where('status', 'in_progress')
            ->first();

        if (!$job) {
            return response()->json([
                'status' => 'ignored',
                'reason' => 'Job not active or unauthorized'
            ], 403);
        }

        TrackingRecord::create([
            'pooling_job_id' => $job->id,
            'driver_id'      => $driverId,
            'latitude'       => $request->latitude,
            'longitude'      => $request->longitude,
            'posted_at'      => $request->posted_at,
        ]);

        return response()->json(['status' => 'success']);
    }

    /**
     * COORDINATOR ENDPOINT (GET)
     * Fetches the latest coordinate every 10 seconds for the Leaflet map.
     */
    public function latest(Request $request, PoolingJob $poolingJob)
    {
        $user = Auth::user();

        // Security Gate: Enforce visibility. Only logistics partners can poll this endpoint.
        if ($user->role !== 'logistics_partner' || !$user->logisticsProfile) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Security Gate: Ensure the coordinator actually owns this pooling job.
        // This prevents data leaks if someone manually hits the API endpoint with another company's job ID.
        if ($poolingJob->logistics_profile_id !== $user->logisticsProfile->id) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        $latestRecord = TrackingRecord::where('pooling_job_id', $poolingJob->id)
            ->orderBy('posted_at', 'desc')
            ->first();

        if (!$latestRecord) {
            return response()->json(['status' => 'no_data']);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'latitude'  => $latestRecord->latitude,
                'longitude' => $latestRecord->longitude,
                'posted_at' => $latestRecord->posted_at->toIso8601String(),
            ]
        ]);
    }
}
