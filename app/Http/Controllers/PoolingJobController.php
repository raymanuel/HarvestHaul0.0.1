<?php

namespace App\Http\Controllers;

use App\Actions\ConfirmPoolingPlanAction;
use App\Http\Requests\PlanPoolingJobRequest;
use App\Http\Requests\ConfirmPoolingJobRequest;
use App\Http\Requests\CounterProposalRequest;
use App\Http\Requests\LogisticsCounterRequest;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
use App\Models\NegotiationStatus;
use App\Models\Truck;
use App\Services\ResourcePoolingService;
use App\Services\WeatherService;
use App\Traits\GeometryHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PoolingJobController extends Controller
{
    use GeometryHelper;

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
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
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

            if (!empty($plan['selected_harvests'])) {
                $weatherService = app(WeatherService::class);
                $weatherAlerts = [];
                $severeWeather = false;

                foreach ($plan['stops'] as $stop) {
                    $wx = $weatherService->getWeather($stop['latitude'], $stop['longitude']);
                    if ($wx && !empty($wx['is_severe'])) {
                        $severeWeather = true;
                        $weatherAlerts[] = $stop['crop'] . ' at ' . ($stop['farm_location'] ?? 'farm') . ': ' . ($wx['advisory'] ?? 'Severe weather');
                    } elseif ($wx && $wx['condition'] !== 'Unknown' && $wx['condition'] !== 'Clear') {
                        $weatherAlerts[] = $stop['crop'] . ' at ' . ($stop['farm_location'] ?? 'farm') . ': ' . ($wx['condition'] ?? '') . ' — ' . ($wx['description'] ?? '');
                    }
                }

                $plan['weather_alerts'] = $weatherAlerts;
                $plan['weather_severe'] = $severeWeather;

                if ($severeWeather) {
                    $plan['message'] = '⚠️ Severe weather detected along route. Consider rescheduling.';
                } elseif (!empty($weatherAlerts)) {
                    $plan['message'] = 'Weather conditions: ' . implode(' | ', array_slice($weatherAlerts, 0, 3)) . (count($weatherAlerts) > 3 ? ' (+' . (count($weatherAlerts) - 3) . ' more)' : '');
                }
            }

            return response()->json($plan);

        } catch (\Exception $e) {
            Log::error('Pooling plan algorithm error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Route planning failed. Please try again or contact support.'], 500);
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
                'total_kg'       => 'required|numeric|min:0.01',
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

            // Build plan from request data instead of re-running the algorithm
            // (avoids race condition where harvests get claimed between plan preview and confirm)
            $harvests = Harvest::whereIn('id', $request->harvest_ids)->with(['crop', 'negotiations'])->get();

            if ($harvests->isEmpty()) {
                return response()->json(['error' => 'No harvests could be selected for this plan.'], 422);
            }

            // Server-side validation: submitted total_kg must match actual harvest sum within 1% tolerance
            $totalKg = (float) $request->total_kg;
            // Use negotiated volume for validation, not raw quantity_kg
            $actualHarvestSum = $harvests->sum(function ($h) {
                $completedNegotiation = $h->negotiations->firstWhere('status', 'COMPLETED');
                return $completedNegotiation ? (float) $completedNegotiation->negotiated_volume : (float) $h->quantity_kg;
            });
            if ($totalKg < ($actualHarvestSum * 0.99) || $totalKg > ($actualHarvestSum * 1.01)) {
                return response()->json(['error' => 'Submitted total_kg (' . $totalKg . ' kg) does not match actual harvest sum (' . $actualHarvestSum . ' kg).'], 422);
            }

            $stops = [];
            $order = 1;
            foreach ($harvests as $h) {
                $completedNegotiation = $h->negotiations->firstWhere('status', 'COMPLETED');
                $stops[] = [
                    'harvest_id' => $h->id,
                    'pickup_order' => $order++,
                    'latitude' => (float) ($h->latitude ?? 0),
                    'longitude' => (float) ($h->longitude ?? 0),
                    'quantity_kg' => $completedNegotiation ? (float) $completedNegotiation->negotiated_volume : (float) $h->quantity_kg,
                    'crop' => $h->crop->name ?? $h->crop_type ?? 'Unknown',
                ];
            }

            // Compute total_distance_km from stops (collection + distribution + return)
            $collectionDistance = 0.0;
            $currentLat = (float) $request->start_lat;
            $currentLng = (float) $request->start_lng;
            foreach ($stops as $stop) {
                $collectionDistance += $this->haversine($currentLat, $currentLng, $stop['latitude'], $stop['longitude']);
                $currentLat = $stop['latitude'];
                $currentLng = $stop['longitude'];
            }

            $distributionDistance = 0.0;
            foreach ($harvests as $h) {
                $dLat = (float) ($h->destination_latitude ?? 0);
                $dLng = (float) ($h->destination_longitude ?? 0);
                if ($dLat && $dLng) {
                    $distributionDistance += $this->haversine($currentLat, $currentLng, $dLat, $dLng);
                    $currentLat = $dLat;
                    $currentLng = $dLng;
                }
            }

            $returnDistance = $this->haversine($currentLat, $currentLng, (float) $request->end_lat, (float) $request->end_lng);
            $totalDistance = $collectionDistance + $distributionDistance + $returnDistance;
            $priceReference = ($totalDistance * 15.00) + ($totalKg * 0.50) + 250.00;

            $plan = [
                'selected_harvests' => $harvests->pluck('id')->toArray(),
                'stops' => $stops,
                'total_kg' => $totalKg,
                'truck_id' => $truck->id,
                'truck_capacity_kg' => (float) $truck->capacity_kg,
                'farm_count' => $harvests->count(),
                'start_lat' => (float) $request->start_lat,
                'start_lng' => (float) $request->start_lng,
                'end_lat' => (float) $request->end_lat,
                'end_lng' => (float) $request->end_lng,
                'radius_km' => (float) $request->radius_km,
                'total_distance_km' => round($totalDistance, 2),
                'price_reference' => round($priceReference, 2),
            ];

            // Set proposal expiration (48 hours from now)
            $plan['proposal_expires_at'] = now()->addHours(48);

            // Bind request-driven notes and spatial geometry parameters into the execution array
            $plan['notes']          = $request->notes ?? null;
            $plan['route_geometry'] = $request->route_geometry;

            $job = app(ConfirmPoolingPlanAction::class)->execute($plan, $logisticsProfile->id);

            return response()->json([
                'success'        => true,
                'pooling_job_id' => $job->id,
                'message'        => 'Pooling job confirmed. ' . count($plan['selected_harvests']) . ' farm(s) assigned.',
            ]);

        } catch (\Exception $e) {
            Log::error('Pooling confirm error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Route confirmation failed. Please try again or contact support.'], 500);
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
            ->take(50)
            ->get();

        // Also fetch recently cancelled proposals so the logistics partner can see farmer rejections
        $cancelledProposals = \App\Models\PoolingJob::where('logistics_profile_id', $logisticsProfile->id)
            ->where('status', 'cancelled')
            ->where('updated_at', '>=', now()->subHours(24))
            ->with(['truck', 'harvests.farmer'])
            ->latest('updated_at')
            ->take(20)
            ->get();

        return view('logistics.proposals-index', compact('proposals', 'cancelledProposals'));
    }

    public function show(PoolingJob $poolingJob)
    {
        $this->authorize('update', $poolingJob);

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
            ->take(50)
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
            // Use weight × distance allocation (same formula as ResourcePoolingService)
            $allocationScores = [];
            $totalAllocationScore = 0.0;

            foreach ($job->harvests as $harvest) {
                $qty = (float) ($harvest->pivot->quantity_kg ?: $harvest->quantity_kg);
                $hLat = (float) ($harvest->latitude ?? 0);
                $hLng = (float) ($harvest->longitude ?? 0);
                $dLat = (float) ($harvest->destination_latitude ?? 0);
                $dLng = (float) ($harvest->destination_longitude ?? 0);

                $individualDistance = $this->haversine($hLat, $hLng, $dLat, $dLng);
                if ($individualDistance < 1.0) $individualDistance = 1.0;

                $score = $qty * $individualDistance;
                $allocationScores[$harvest->id] = $score;
                $totalAllocationScore += $score;
            }

            if ($totalAllocationScore > 0) {
                $harvestArray = $job->harvests->toArray();
                $assignedTotal = 0.0;
                $pivotUpdates = [];

                foreach ($harvestArray as $index => $harvest) {
                    $proportion = $allocationScores[$harvest['id']] / $totalAllocationScore;

                    // Last farmer absorbs any rounding difference to ensure sum equals basePrice exactly
                    if ($index === count($harvestArray) - 1) {
                        $costShare = round($basePrice - $assignedTotal, 2);
                    } else {
                        $costShare = round($basePrice * $proportion, 2);
                        $assignedTotal += $costShare;
                    }

                    $pivotUpdates[$harvest['id']] = ['cost_share' => $costShare];
                }

                // Batch update all pivot rows in one query
                foreach ($pivotUpdates as $harvestId => $data) {
                    $job->harvests()->updateExistingPivot($harvestId, $data);
                }
            }
        }
    }

    /**
     * Farmer accepts a pending pooling proposal.
     */
    public function acceptProposal(PoolingJob $poolingJob)
    {
        $user = auth()->user();

        $poolingJob->load('harvests');
        $this->authorize('view', $poolingJob);

        $harvest = $poolingJob->harvests()->where('user_id', $user->id)->first();

        if ($poolingJob->status !== PoolingJobStatus::PENDING) {
            abort(422, 'This proposal is no longer open for changes.');
        }

        if ($poolingJob->proposal_expires_at && $poolingJob->proposal_expires_at->isPast()) {
            abort(410, 'This proposal has expired. Please wait for a new one.');
        }

        $poolingJob->harvests()->updateExistingPivot($harvest->id, [
            'status' => 'accepted'
        ]);

        $poolingJob->load('harvests');

        $allAccepted = true;
        $anyRejected = false;
        foreach ($poolingJob->harvests as $h) {
            if ($h->pivot->status === 'rejected') {
                $anyRejected = true;
            } elseif ($h->pivot->status !== 'accepted') {
                $allAccepted = false;
            }
        }

        if ($allAccepted) {
            $poolingJob->status = PoolingJobStatus::CONFIRMED;
            $poolingJob->confirmed_at = now();
            $poolingJob->save();

            // Bulk update all harvest statuses
            Harvest::whereIn('id', $poolingJob->harvests->pluck('id'))->update(['status' => HarvestStatus::ASSIGNED]);

            // Batch create notifications
            $notifications = [];
            if ($poolingJob->driver_id) {
                $notifications[] = [
                    'user_id'    => $poolingJob->driver_id,
                    'title'      => 'New Route Confirmed',
                    'message'    => "All farmers accepted. Route #{$poolingJob->id} has been dispatched to you.",
                    'link'       => route('driver.dashboard'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $logisticsUser = $poolingJob->logisticsProfile->user;
            if ($logisticsUser) {
                $notifications[] = [
                    'user_id'    => $logisticsUser->id,
                    'title'      => 'Proposal Confirmed',
                    'message'    => "All farmers accepted Proposal #{$poolingJob->id}. Route is now confirmed.",
                    'link'       => route('pooling.index'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($notifications)) {
                \App\Models\Notification::insert($notifications);
            }
        } elseif ($anyRejected) {
            \App\Models\Notification::create([
                'user_id' => $poolingJob->logisticsProfile?->user_id,
                'title' => 'Proposal Partially Rejected',
                'message' => "Some farmers rejected Proposal #{$poolingJob->id}. Review and adjust.",
                'link' => route('pooling.index'),
            ]);
        }

        return back()->with('success', 'You have accepted the pooling proposal.');
    }

    /**
     * Farmer rejects a pending pooling proposal.
     */
    public function rejectProposal(PoolingJob $poolingJob)
    {
        $user = auth()->user();

        $poolingJob->load('harvests');
        $this->authorize('view', $poolingJob);

        if ($poolingJob->status !== PoolingJobStatus::PENDING) {
            abort(422, 'This proposal is no longer open for changes.');
        }

        if ($poolingJob->proposal_expires_at && $poolingJob->proposal_expires_at->isPast()) {
            abort(410, 'This proposal has expired.');
        }

        $harvest = $poolingJob->harvests()->where('user_id', $user->id)->first();

        if ($harvest->status === HarvestStatus::ASSIGNED) {
            $hasCompletedDeals = $harvest->negotiations()
                ->where('status', NegotiationStatus::COMPLETED)
                ->exists();

            if ($hasCompletedDeals) {
                $isIndependent = $harvest->user?->farmerProfile?->affiliation_type === 'independent';
                $harvest->status = HarvestStatus::PARTIALLY_SOLD;
                $harvest->visibility = $isIndependent ? 'buyers_only' : 'both';
            } else {
                $harvest->status = HarvestStatus::ACTIVE;
            }
            $harvest->save();
        }

        $poolingJob->harvests()->detach($harvest->id);

        $poolingJob->load('harvests');

        if ($poolingJob->harvests->isEmpty()) {
            $poolingJob->status = PoolingJobStatus::CANCELLED;
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

            // Reset remaining farmers' acceptance status — they must re-approve new shares
            $pendingFarmerIds = [];
            foreach ($poolingJob->harvests as $remaining) {
                if ($remaining->pivot->status === 'accepted') {
                    $poolingJob->harvests()->updateExistingPivot($remaining->id, [
                        'status' => 'pending'
                    ]);
                    $pendingFarmerIds[] = $remaining->user_id;
                }
            }

            // Bulk create notifications for recalculated cost shares
            if (!empty($pendingFarmerIds)) {
                $notifications = [];
                foreach ($pendingFarmerIds as $farmerId) {
                    $notifications[] = [
                        'user_id'    => $farmerId,
                        'title'      => 'Cost Shares Recalculated — Re-approval Required',
                        'message'    => "A farmer rejected Route #{$poolingJob->id}. Your cost share has been recalculated. Please review and re-accept.",
                        'link'       => route('farmer.proposals'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                \App\Models\Notification::insert($notifications);
            }
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
    public function counterProposal(CounterProposalRequest $request, PoolingJob $poolingJob)
    {
        $user = auth()->user();

        $poolingJob->load('harvests');
        $this->authorize('view', $poolingJob);

        if ($poolingJob->status !== PoolingJobStatus::PENDING) {
            abort(422, 'This proposal is no longer open for changes.');
        }

        if ($poolingJob->proposal_expires_at && $poolingJob->proposal_expires_at->isPast()) {
            abort(410, 'This proposal has expired. Please wait for a new one.');
        }

        $validated = $request->validated();
        $harvest = $poolingJob->harvests()->where('user_id', $user->id)->first();

        // Enforce negotiation rounds limit
        if ($poolingJob->negotiation_rounds >= 5) {
            return back()->with('error', 'Maximum negotiation rounds reached (5). Accept the current offer or reject the proposal.');
        }

        // Enforce price bounds: counter-price must be within ±75% of the reference price
        $referencePrice = (float) ($poolingJob->price_reference ?? 0);
        if ($referencePrice <= 0) {
            return back()->with('error', 'Cannot counter-propose. Reference price is not set.');
        }

        $minAllowed = $referencePrice * 0.25;
        $maxAllowed = $referencePrice * 1.75;
        if ($validated['counter_price'] < $minAllowed || $validated['counter_price'] > $maxAllowed) {
            return back()->with('error', 'Counter-price must be between ₱' . number_format($minAllowed, 2) . ' and ₱' . number_format($maxAllowed, 2) . ' based on the reference price.');
        }

        // Update ONLY this farmer's cost_share
        $poolingJob->harvests()->updateExistingPivot($harvest->id, [
            'cost_share' => $validated['counter_price'],
        ]);

        // Mark only this farmer as accepted (their counter), leave others unchanged
        $poolingJob->harvests()->updateExistingPivot($harvest->id, [
            'status' => 'accepted'
        ]);

        // Increment negotiation rounds
        $poolingJob->negotiation_rounds = ($poolingJob->negotiation_rounds ?? 0) + 1;
        $poolingJob->save();

        // Notify logistics partner
        $logisticsUser = $poolingJob->logisticsProfile->user;
        if ($logisticsUser) {
            \App\Models\Notification::create([
                'user_id' => $logisticsUser->id,
                'title' => 'New Price Counter-Offer',
                'message' => "Farmer {$user->name} counter-proposed ₱" . number_format($validated['counter_price'], 2) . " for Route #{$poolingJob->id}.",
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
        $this->authorize('update', $poolingJob);

        if ($poolingJob->status !== PoolingJobStatus::PENDING) {
            return back()->with('error', 'This proposal is no longer pending.');
        }

        if ($poolingJob->proposal_expires_at && $poolingJob->proposal_expires_at->isPast()) {
            return back()->with('error', 'This proposal has expired.');
        }

        if (!$poolingJob->buyer_id) {
            return back()->with('error', 'No buyer assigned to this job. Cannot confirm without a buyer.');
        }

        $poolingJob->load('harvests');
        $totalCostShare = $poolingJob->harvests->sum(function ($h) {
            return (float) ($h->pivot->cost_share ?? 0);
        });
        $poolingJob->negotiated_price = $totalCostShare;
        $poolingJob->save();
        $allAccepted = true;
        foreach ($poolingJob->harvests as $h) {
            if ($h->pivot->status !== 'accepted') {
                $allAccepted = false;
                break;
            }
        }

        if ($allAccepted) {
            $poolingJob->status = PoolingJobStatus::CONFIRMED;
            $poolingJob->confirmed_at = now();
            $poolingJob->save();

            // Bulk update all harvest statuses
            Harvest::whereIn('id', $poolingJob->harvests->pluck('id'))->update(['status' => HarvestStatus::ASSIGNED]);

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
    public function logisticsCounter(LogisticsCounterRequest $request, PoolingJob $poolingJob)
    {
        $this->authorize('update', $poolingJob);

        $logisticsProfile = Auth::user()->logisticsProfile;

        if (!$logisticsProfile) {
            return back()->with('error', 'No logistics profile found.');
        }

        if ($poolingJob->status !== PoolingJobStatus::PENDING) {
            abort(422, 'This proposal is no longer open for changes.');
        }

        if ($poolingJob->proposal_expires_at && $poolingJob->proposal_expires_at->isPast()) {
            abort(410, 'This proposal has expired. Please create a new proposal.');
        }

        $validated = $request->validated();

        // Enforce price bounds: counter-price must be within ±75% of the reference price
        $referencePrice = (float) ($poolingJob->price_reference ?? 0);
        if ($referencePrice > 0) {
            $minAllowed = $referencePrice * 0.25;
            $maxAllowed = $referencePrice * 1.75;
            if ($validated['negotiated_price'] < $minAllowed || $validated['negotiated_price'] > $maxAllowed) {
                return back()->with('error', 'Price must be between ₱' . number_format($minAllowed, 2) . ' and ₱' . number_format($maxAllowed, 2) . ' based on the reference price.');
            }
        }

        $poolingJob->negotiated_price = $validated['negotiated_price'];
        $poolingJob->save();

        // Recalculate per-farmer cost shares proportionally from the new total
        $this->recalculateCostShares($poolingJob);
        $poolingJob->load('harvests'); // Refresh pivot data after recalculation

        // Reset all farmers to pending so they must re-approve updated shares
        foreach ($poolingJob->harvests as $h) {
            $poolingJob->harvests()->updateExistingPivot($h->id, [
                'status' => 'pending'
            ]);
        }

        // Reload pivot data after all updates so notifications show correct cost shares
        $poolingJob->load('harvests');

        // Bulk create notifications for all farmers
        $notifications = [];
        foreach ($poolingJob->harvests as $h) {
            $farmerShare = $h->pivot->cost_share ?? 0;
            $notifications[] = [
                'user_id'    => $h->user_id,
                'title'      => 'New Hauling Bid Offered',
                'message'    => "Logistics operator offered a new bid for Route #{$poolingJob->id}. Your cost share is ₱" . number_format($farmerShare, 2) . ". Please review and re-accept.",
                'link'       => route('farmer.proposals'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if (!empty($notifications)) {
            \App\Models\Notification::insert($notifications);
        }

        return back()->with('success', 'New bid price submitted to farmers.');
    }
}
