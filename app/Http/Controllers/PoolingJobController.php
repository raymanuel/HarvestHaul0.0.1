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
            $this->recalculateCostShares($job);

            foreach ($job->harvests as $harvest) {
                \App\Models\Notification::create([
                    'user_id' => $harvest->user_id,
                    'title' => 'New Pooling Proposal',
                    'message' => "Your harvest '{$harvest->crop->name}' has been pooled into Route #{$job->id}.",
                    'link' => route('farmer.proposals'),
                ]);
            }

            \App\Models\AuditLog::create([
                'admin_id'    => Auth::id(),
                'action'      => 'confirmed_pooling_plan',
                'target_type' => 'pooling_job',
                'target_id'   => $job->id,
                'notes'       => "Logistics Partner " . Auth::user()->name . " confirmed route #{$job->id} (Total weight: {$job->total_kg} kg, Price: ₱" . ($job->negotiated_price ?? $job->price_reference ?? 0) . ").",
            ]);

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

    public function show(PoolingJob $poolingJob)
    {
        $logisticsProfile = Auth::user()->logisticsProfile;

        if ($poolingJob->logistics_profile_id !== $logisticsProfile->id) {
            abort(403);
        }

        return redirect()->route('pooling.cost-ledger', $poolingJob);
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

    /**
     * Recalculate cost shares proportionally for all harvests in a job.
     */
    private function recalculateCostShares(PoolingJob $job)
    {
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
    }

    /**
     * Farmer accepts a pending pooling proposal.
     */
    public function acceptProposal(PoolingJob $poolingJob)
    {
        $user = auth()->user();

        // Find the farmer's harvest inside the job
        $harvest = $poolingJob->harvests()->where('user_id', $user->id)->first();
        if (!$harvest) {
            abort(403, 'Unauthorized. Harvest not found in this route.');
        }

        // Update pivot status
        $poolingJob->harvests()->updateExistingPivot($harvest->id, [
            'status' => 'accepted'
        ]);

        // Eager load harvests to get fresh statuses
        $poolingJob->load('harvests');

        // Check if all farmers have accepted
        $allAccepted = true;
        foreach ($poolingJob->harvests as $h) {
            if ($h->pivot->status !== 'accepted') {
                $allAccepted = false;
                break;
            }
        }

        if ($allAccepted) {
            $poolingJob->status = 'confirmed';
            $poolingJob->confirmed_at = now();
            $poolingJob->save();

            // Notify driver
            if ($poolingJob->driver_id) {
                \App\Models\Notification::create([
                    'user_id' => $poolingJob->driver_id,
                    'title' => 'New Route Confirmed',
                    'message' => "All farmers accepted. Route #{$poolingJob->id} has been dispatched to you.",
                    'link' => route('driver.dashboard'),
                ]);
            }

            // Notify logistics
            $logisticsUser = $poolingJob->logisticsProfile->user;
            if ($logisticsUser) {
                \App\Models\Notification::create([
                    'user_id' => $logisticsUser->id,
                    'title' => 'Proposal Confirmed',
                    'message' => "All farmers accepted Proposal #{$poolingJob->id}. Route is now confirmed.",
                    'link' => route('pooling.index'),
                ]);
            }
        }

        return back()->with('success', 'You have accepted the pooling proposal.');
    }

    /**
     * Farmer rejects a pending pooling proposal.
     */
    public function rejectProposal(PoolingJob $poolingJob)
    {
        $user = auth()->user();

        $harvest = $poolingJob->harvests()->where('user_id', $user->id)->first();
        if (!$harvest) {
            abort(403, 'Unauthorized.');
        }

        // Set harvest status back to sold so it can be pooled elsewhere
        $harvest->status = 'sold';
        $harvest->save();

        // Detach from the pooling job
        $poolingJob->harvests()->detach($harvest->id);

        $poolingJob->load('harvests');

        if ($poolingJob->harvests->isEmpty()) {
            $poolingJob->status = 'cancelled';
            $poolingJob->save();

            // Free the truck
            if ($poolingJob->truck) {
                $poolingJob->truck->update(['status' => 'available']);
            }
        } else {
            // Update job counts and weight
            $totalKg = $poolingJob->harvests->sum('pivot.quantity_kg');
            $poolingJob->total_kg = $totalKg;
            $poolingJob->farm_count = $poolingJob->harvests->count();
            $poolingJob->save();

            // Recalculate cost shares for the remaining farmers
            $this->recalculateCostShares($poolingJob);
        }

        // Notify logistics partner
        $logisticsUser = $poolingJob->logisticsProfile->user;
        if ($logisticsUser) {
            \App\Models\Notification::create([
                'user_id' => $logisticsUser->id,
                'title' => 'Farmer Rejected Proposal',
                'message' => "Farmer {$user->name} rejected the proposal for Route #{$poolingJob->id}.",
                'link' => route('pooling.index'),
            ]);
        }

        return back()->with('success', 'You rejected the proposal. Your crop is back on the haul board.');
    }

    /**
     * Farmer submits a counter-proposal price bid.
     */
    public function counterProposal(Request $request, PoolingJob $poolingJob)
    {
        $user = auth()->user();

        $request->validate([
            'counter_price' => 'required|numeric|min:1|max:999999'
        ]);

        $harvest = $poolingJob->harvests()->where('user_id', $user->id)->first();
        if (!$harvest) {
            abort(403, 'Unauthorized.');
        }

        // Set new negotiated price
        $poolingJob->negotiated_price = $request->counter_price;
        $poolingJob->save();

        // Reset all other farmers to pending, mark this farmer as accepted
        foreach ($poolingJob->harvests as $h) {
            $status = ($h->user_id === $user->id) ? 'accepted' : 'pending';
            $poolingJob->harvests()->updateExistingPivot($h->id, [
                'status' => $status
            ]);
        }

        // Recalculate cost shares based on new price
        $this->recalculateCostShares($poolingJob);

        // Notify logistics partner
        $logisticsUser = $poolingJob->logisticsProfile->user;
        if ($logisticsUser) {
            \App\Models\Notification::create([
                'user_id' => $logisticsUser->id,
                'title' => 'New Price Counter-Offer',
                'message' => "Farmer {$user->name} counter-proposed ₱" . number_format($request->counter_price, 2) . " for Route #{$poolingJob->id}.",
                'link' => route('pooling.index'),
            ]);
        }

        return back()->with('success', 'Counter-proposal price submitted successfully.');
    }

    /**
     * Logistics accepts the farmer's counter-offer.
     */
    public function logisticsAcceptCounter(PoolingJob $poolingJob)
    {
        $logisticsProfile = auth()->user()->logisticsProfile;
        if ($poolingJob->logistics_profile_id !== $logisticsProfile->id) {
            abort(403);
        }

        // Recalculate splits to lock it in
        $this->recalculateCostShares($poolingJob);

        // Check if all farmers are accepted
        $poolingJob->load('harvests');
        $allAccepted = true;
        foreach ($poolingJob->harvests as $h) {
            if ($h->pivot->status !== 'accepted') {
                $allAccepted = false;
                break;
            }
        }

        if ($allAccepted) {
            $poolingJob->status = 'confirmed';
            $poolingJob->confirmed_at = now();
            $poolingJob->save();

            // Notify driver
            if ($poolingJob->driver_id) {
                \App\Models\Notification::create([
                    'user_id' => $poolingJob->driver_id,
                    'title' => 'New Route Confirmed',
                    'message' => "Route #{$poolingJob->id} counter-proposal accepted and confirmed.",
                    'link' => route('driver.dashboard'),
                ]);
            }
        }

        return back()->with('success', 'You accepted the farmer\'s price counter-offer.');
    }

    /**
     * Logistics submits a new counter-bid to farmers.
     */
    public function logisticsCounter(Request $request, PoolingJob $poolingJob)
    {
        $logisticsProfile = auth()->user()->logisticsProfile;
        if ($poolingJob->logistics_profile_id !== $logisticsProfile->id) {
            abort(403);
        }

        $request->validate([
            'negotiated_price' => 'required|numeric|min:1|max:999999'
        ]);

        $poolingJob->negotiated_price = $request->negotiated_price;
        $poolingJob->save();

        // Reset all farmers to pending
        foreach ($poolingJob->harvests as $h) {
            $poolingJob->harvests()->updateExistingPivot($h->id, [
                'status' => 'pending'
            ]);

            // Notify farmer
            \App\Models\Notification::create([
                'user_id' => $h->user_id,
                'title' => 'New Hauling Bid Offered',
                'message' => "Logistics operator offered a new bid of ₱" . number_format($request->negotiated_price, 2) . " for Route #{$poolingJob->id}.",
                'link' => route('farmer.proposals'),
            ]);
        }

        // Recalculate cost shares
        $this->recalculateCostShares($poolingJob);

        return back()->with('success', 'New bid price submitted to farmers.');
    }
}
