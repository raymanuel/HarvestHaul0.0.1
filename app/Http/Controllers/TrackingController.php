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
        
        $activeJobs = $query->with(['truck', 'driver', 'harvests.crop', 'harvests.farmer.farmerProfile', 'harvests.destination'])->latest()->get();
        
        $activeJobs->each(function ($job) {
            $job->latestTracking = TrackingRecord::where('pooling_job_id', $job->id)
                ->latest('id')
                ->first();
        });
        
        return view('tracking.index', compact('activeJobs'));
    }

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
