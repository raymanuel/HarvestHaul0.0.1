<?php

namespace App\Http\Controllers;

use App\Models\Harvest;
use App\Models\PoolingJob;
use App\Models\Truck;
use App\Services\ResourcePoolingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PoolingJobController extends Controller
{
    // Define the property here so it exists for the entire class
    protected $poolingService;

    // Use constructor injection to assign the service
    public function __construct(ResourcePoolingService $poolingService)
    {
        $this->poolingService = $poolingService;
    }

    // Static middleware definition (Laravel 11+ style)
    public static function middleware(): array
    {
        return ['auth'];
    }


    /**
     * Generate a pooling plan (no DB writes).
     * Called via AJAX from the route-optimization view.
     */
    public function plan(Request $request)
    {
        try {
            // Use explicit Validator to guarantee JSON error response instead of 302 HTML redirects
            $validator = Validator::make($request->all(), [
                'truck_id'      => 'required|integer|exists:trucks,id',
                'harvest_ids'   => 'required|array|min:1',
                'harvest_ids.*' => 'integer|exists:harvests,id',
                'start_lat'     => 'required|numeric',
                'start_lng'     => 'required|numeric',
                'end_lat'       => 'required|numeric',
                'end_lng'       => 'required|numeric',
                'radius_km'     => 'required|numeric|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }

            $logisticsProfile = Auth::user()->logisticsProfile;

            if (!$logisticsProfile) {
                return response()->json(['error' => 'No logistics profile found.'], 403);
            }

            $truck = Truck::where('id', $request->truck_id)
                ->where('logistics_profile_id', $logisticsProfile->id)
                ->where('status', 'available')
                ->first();

            if (!$truck) {
                return response()->json(['error' => 'Truck not found or currently unavailable.'], 404);
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

        } catch (\Exception $e) {
            // Catch any algorithm crashes and return them cleanly to the UI
            return response()->json(['error' => 'Algorithm Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Confirm and save a pooling plan to DB.
     * Marks harvests as 'assigned', truck as 'assigned'.
     */
    public function confirm(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'truck_id'       => 'required|integer|exists:trucks,id',
                'harvest_ids'    => 'required|array|min:1',
                'harvest_ids.*'  => 'integer|exists:harvests,id',
                'total_kg'       => 'required|numeric',
                'start_lat'      => 'required|numeric',
                'start_lng'      => 'required|numeric',
                'end_lat'        => 'required|numeric',
                'end_lng'        => 'required|numeric',
                'radius_km'      => 'required|numeric|min:1',
                'notes'          => 'nullable|string|max:500',
                'route_geometry' => 'required|array', // Enforce geometry presence from the map interface
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }

            $logisticsProfile = Auth::user()->logisticsProfile;

            if (!$logisticsProfile) {
                return response()->json(['error' => 'No logistics profile found.'], 403);
            }

            $truck = Truck::where('id', $request->truck_id)
                ->where('logistics_profile_id', $logisticsProfile->id)
                ->where('status', 'available')
                ->first();

            if (!$truck) {
                return response()->json(['error' => 'Truck not found or currently unavailable.'], 404);
            }

            // Re-run plan so confirm() receives a clean baseline plan array
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

            // Bind request-driven notes and spatial geometry parameters into the execution array
            $plan['notes']          = $request->notes ?? null;
            $plan['route_geometry'] = $request->route_geometry;

            // Execute database persistence model
            $job = $this->poolingService->confirm($plan, $logisticsProfile->id);

            // Compute & store per-farmer cost shares in pivot table
            $totalKg     = (float) $job->total_kg;
            $basePrice   = (float) ($job->negotiated_price ?? $job->price_reference ?? 0);

            if ($totalKg > 0 && $basePrice > 0) {
                foreach ($job->harvests as $harvest) {
                    $harvestKg  = (float) $harvest->pivot->quantity_kg;
                    $proportion = $harvestKg / $totalKg;
                    $costShare  = round($basePrice * $proportion, 2);

                    $job->harvests()->updateExistingPivot($harvest->id, [
                        'cost_share' => $costShare,
                    ]);
                }
            }

            // Create notifications for driver and farmers
            if ($job->driver_id) {
                \App\Models\Notification::create([
                    'user_id' => $job->driver_id,
                    'title' => 'New Route Assigned',
                    'message' => "You have been assigned to Route #{$job->id}.",
                    'link' => route('driver.dashboard'),
                ]);
            }

            foreach ($job->harvests as $harvest) {
                \App\Models\Notification::create([
                    'user_id' => $harvest->user_id,
                    'title' => 'New Pooling Proposal',
                    'message' => "Your harvest '{$harvest->crop->name}' has been pooled into Route #{$job->id}.",
                    'link' => route('farmer.proposals'),
                ]);
            }

            return response()->json([
                'success'        => true,
                'pooling_job_id' => $job->id,
                'message'        => 'Pooling job confirmed. ' . count($plan['selected_harvests']) . ' farm(s) assigned.',
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Database Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * List all pooling jobs for the logged-in logistics partner.
     */
    public function index()
    {
        $logisticsProfile = auth()->user()->logisticsProfile;

        // Fetch all pending proposals eager loading truck and harvest counters
        $proposals = \App\Models\PoolingJob::where('logistics_profile_id', $logisticsProfile->id)
            ->where('status', 'pending')
            ->with(['truck', 'harvests.farmer'])
            ->latest()
            ->get();

        return view('logistics.proposals-index', compact('proposals'));
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

    /**
     * Display the Negotiation Hub / Proposal Inbox for the Authenticated Farmer.
     * Fetches pending pooled jobs that include this farmer's inventory crops.
     */
    public function farmerProposals()
    {
        $user = auth()->user();

        // Query pending jobs containing a harvest entry owned by this farmer
        $proposals = PoolingJob::where('status', 'pending')
            ->whereHas('harvests', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['truck', 'logisticsProfile', 'harvests' => function ($query) use ($user) {
                $query->where('user_id', $user->id)->with(['crop', 'cropVariety', 'destination']);
            }])
            ->latest()
            ->get();

        return view('farmers.farmer-proposals', compact('proposals'));
    }
}
