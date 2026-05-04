<?php

namespace App\Http\Controllers;

use App\Models\Harvest;
use App\Models\PoolingJob;
use App\Models\Truck;
use App\Services\ResourcePoolingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PoolingJobController extends Controller
{
    public function __construct(protected ResourcePoolingService $poolingService)
    {
        $this->middleware('auth');
    }

    /**
     * Generate a pooling plan (no DB writes).
     * Called via AJAX from the route-optimization view.
     */
    public function plan(Request $request)
    {
        $request->validate([
            'truck_id'          => 'required|integer|exists:trucks,id',
            'harvest_ids'       => 'required|array|min:1',
            'harvest_ids.*'     => 'integer|exists:harvests,id',
            'start_lat'         => 'required|numeric',
            'start_lng'         => 'required|numeric',
            'end_lat'           => 'required|numeric',
            'end_lng'           => 'required|numeric',
            'radius_km'         => 'required|numeric|min:1',
        ]);

        $logisticsProfile = Auth::user()->logisticsProfile;

        if (!$logisticsProfile) {
            return response()->json(['error' => 'No logistics profile found.'], 403);
        }

        $truck = Truck::where('id', $request->truck_id)
            ->where('logistics_profile_id', $logisticsProfile->id)
            ->where('status', 'available')
            ->first();

        if (!$truck) {
            return response()->json(['error' => 'Truck not found or not available.'], 404);
        }

        $plan = $this->poolingService->plan(
            truck: $truck,
            nearbyHarvestIds: $request->harvest_ids,
            startLat: (float) $request->start_lat,
            startLng: (float) $request->start_lng,
            endLat: (float) $request->end_lat,
            endLng: (float) $request->end_lng,
            radiusKm: (float) $request->radius_km,
        );

        return response()->json($plan);
    }

    /**
     * Confirm and save a pooling plan to DB.
     * Marks harvests as 'assigned', truck as 'assigned'.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'truck_id'          => 'required|integer|exists:trucks,id',
            'harvest_ids'       => 'required|array|min:1',
            'harvest_ids.*'     => 'integer|exists:harvests,id',
            'total_kg'          => 'required|numeric',
            'start_lat'         => 'required|numeric',
            'start_lng'         => 'required|numeric',
            'end_lat'           => 'required|numeric',
            'end_lng'           => 'required|numeric',
            'radius_km'         => 'required|numeric|min:1',
            'notes'             => 'nullable|string|max:500',
        ]);

        $logisticsProfile = Auth::user()->logisticsProfile;

        if (!$logisticsProfile) {
            return response()->json(['error' => 'No logistics profile found.'], 403);
        }

        $truck = Truck::where('id', $request->truck_id)
            ->where('logistics_profile_id', $logisticsProfile->id)
            ->where('status', 'available')
            ->first();

        if (!$truck) {
            return response()->json(['error' => 'Truck not found or not available.'], 404);
        }

        // Re-run plan so confirm() receives a clean plan array
        $plan = $this->poolingService->plan(
            truck: $truck,
            nearbyHarvestIds: $request->harvest_ids,
            startLat: (float) $request->start_lat,
            startLng: (float) $request->start_lng,
            endLat: (float) $request->end_lat,
            endLng: (float) $request->end_lng,
            radiusKm: (float) $request->radius_km,
        );

        if (empty($plan['selected_harvests'])) {
            return response()->json(['error' => 'No harvests could be selected for this plan.'], 422);
        }

        // Merge optional notes into plan before saving
        $plan['notes'] = $request->notes ?? null;

        $job = $this->poolingService->confirm($plan, $logisticsProfile->id);

        return response()->json([
            'success'       => true,
            'pooling_job_id' => $job->id,
            'message'       => 'Pooling job confirmed. ' . count($plan['selected_harvests']) . ' farm(s) assigned.',
        ]);
    }

    /**
     * List all pooling jobs for the logged-in logistics partner.
     */
    public function index()
    {
        $logisticsProfile = Auth::user()->logisticsProfile;

        $jobs = PoolingJob::where('logistics_profile_id', $logisticsProfile->id)
            ->with(['truck', 'harvests'])
            ->latest()
            ->paginate(15);

        return view('pooling.index', compact('jobs'));
    }

    /**
     * Show a single pooling job detail.
     */
    public function show(PoolingJob $poolingJob)
    {
        $logisticsProfile = Auth::user()->logisticsProfile;

        if ($poolingJob->logistics_profile_id !== $logisticsProfile->id) {
            abort(403);
        }

        $poolingJob->load(['truck', 'harvests.destination']);

        return view('pooling.show', compact('poolingJob'));
    }
}
