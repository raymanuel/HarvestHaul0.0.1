<?php

namespace App\Http\Controllers;

use App\Models\PoolingJob;
use App\Models\TrackingRecord;
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
    /**
     * INGRESS ENDPOINT: Drivers stream GPS coordinates to this endpoint every 15 seconds.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pooling_job_id' => 'required|integer|exists:pooling_jobs,id',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $job = PoolingJob::findOrFail($request->pooling_job_id);

        // Security Validation: Ensure the authenticated user is the assigned driver for this trip
        if ($job->driver_id !== Auth::id()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized fleet streaming.'], 403);
        }

        // Hard limitation check: Only log coordinates if the delivery trip is active
        if ($job->status !== 'in_progress') {
            return response()->json(['status' => 'error', 'message' => 'Job is not in transit.'], 422);
        }

        // Lightweight write to database
        $record = TrackingRecord::create([
            'pooling_job_id' => $job->id,
            'driver_id'      => Auth::id(),
            'latitude'       => (float) $request->latitude,
            'longitude'      => (float) $request->longitude,
            'posted_at'      => now(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Telemetry node registered.',
            'data'    => [
                'latitude'  => $record->latitude,
                'longitude' => $record->longitude,
            ]
        ], 201);
    }

    /**
     * EGRESS ENDPOINT: The Coordinator layout polls this endpoint every 10 seconds.
     */
    public function latest($jobId)
    {
        // Enforce coordinator access validation
        $logisticsProfile = Auth::user()->logisticsProfile;
        if (!$logisticsProfile) {
            return response()->json(['status' => 'error', 'message' => 'Access denied.'], 403);
        }

        // Fetch absolute newest entry recorded for this fleet tracking route
        $latestNode = TrackingRecord::where('pooling_job_id', $jobId)
            ->latest('id') // Indexes directly against autoincrement for fast parsing
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
                'posted_at' => $latestNode->posted_at->toIso8601String(),
            ]
        ], 200);
    }
}
