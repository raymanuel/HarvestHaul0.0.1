<?php

namespace App\Http\Controllers;

use App\Models\PoolingJob;
use App\Services\PoolingJobService;
use App\Services\ResourcePoolingService;
use App\Traits\GeometryHelper;
use App\Traits\Notifiable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PoolingJobController extends Controller
{
    use GeometryHelper, Notifiable;

    protected $poolingService;
    protected PoolingJobService $jobService;

    public function __construct(ResourcePoolingService $poolingService, PoolingJobService $jobService)
    {
        $this->poolingService = $poolingService;
        $this->jobService = $jobService;
    }

    public function middleware($middleware = null, array $options = []): array
    {
        return ['auth'];
    }

    public function plan(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'truck_id'      => 'required|integer|exists:trucks,id',
                'harvest_ids'   => 'required|array|min:1|max:50',
                'harvest_ids.*' => 'integer|exists:harvests,id',
                'start_lat'     => 'required|numeric',
                'start_lng'     => 'required|numeric',
                'end_lat'       => 'required|numeric',
                'end_lng'       => 'required|numeric',
                'radius_km'     => 'required|numeric|min:1|max:200',
                'hauling_rate_per_kg' => 'nullable|numeric|min:0.1',
                'farm_distances'    => 'nullable|array',
                'farm_distances.*'  => 'nullable|numeric|min:0',
                'route_distance_km' => 'nullable|numeric|min:0',
                'terrain'          => 'nullable|string|in:flat,rolling,mountainous',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }

            $result = $this->jobService->preparePlan(Auth::user(), $validator->validated());

            if (isset($result['status'])) {
                return response()->json(['error' => $result['error']], $result['status']);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Pooling plan algorithm error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Route planning failed. Please try again or contact support.'], 500);
        }
    }

    public function confirm(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'truck_id'          => 'required|integer|exists:trucks,id',
                'harvest_ids'       => 'required|array|min:1',
                'harvest_ids.*'     => 'integer|exists:harvests,id',
                'total_kg'          => 'required|numeric|min:0.01',
                'start_lat'         => 'required|numeric|between:-90,90',
                'start_lng'         => 'required|numeric|between:-180,180',
                'end_lat'           => 'required|numeric|between:-90,90',
                'end_lng'           => 'required|numeric|between:-180,180',
                'radius_km'         => 'required|numeric|min:1|max:200',
                'notes'             => 'nullable|string|max:500',
                'route_geometry'    => 'required|array',
                'hauling_rate_per_kg' => 'nullable|numeric|min:0.1',
                'stop_order'        => 'nullable|array',
                'stop_order.*'      => 'integer|exists:harvests,id',
                'farm_distances'    => 'nullable|array',
                'farm_distances.*'  => 'numeric|min:0',
                'route_distance_km' => 'nullable|numeric|min:0',
                'terrain'          => 'nullable|string|in:flat,rolling,mountainous',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }

            $logisticsProfile = Auth::user()->logisticsProfile;

            if (!$logisticsProfile) {
                return response()->json(['error' => 'No logistics profile found.'], 403);
            }

            $result = $this->jobService->confirmPoolingPlan($validator->validated(), $logisticsProfile->id);

            if (isset($result['error'])) {
                return response()->json(['error' => $result['error']], $result['status'] ?? 422);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Pooling confirm error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Route confirmation failed. Please try again or contact support.'], 500);
        }
    }

    public function planAll(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'harvest_ids'   => 'required|array|min:1|max:50',
                'harvest_ids.*' => 'integer|exists:harvests,id',
                'start_lat'     => 'required|numeric',
                'start_lng'     => 'required|numeric',
                'end_lat'       => 'required|numeric',
                'end_lng'       => 'required|numeric',
                'radius_km'     => 'required|numeric|min:1|max:200',
                'hauling_rate_per_kg' => 'nullable|numeric|min:0.1',
                'farm_distances'    => 'nullable|array',
                'farm_distances.*'  => 'nullable|numeric|min:0',
                'route_distance_km' => 'nullable|numeric|min:0',
                'terrain'          => 'nullable|string|in:flat,rolling,mountainous',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }

            $result = $this->jobService->preparePlanAll(Auth::user(), $validator->validated());

            if (isset($result['status'])) {
                return response()->json(['error' => $result['error']], $result['status']);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Pooling plan-all algorithm error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Route planning failed. Please try again or contact support.'], 500);
        }
    }

    public function confirmBatch(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'plans' => 'required|array|min:1|max:20',
                'plans.*.truck_id'            => 'required|integer|exists:trucks,id',
                'plans.*.harvest_ids'         => 'required|array|min:1',
                'plans.*.harvest_ids.*'       => 'integer|exists:harvests,id',
                'plans.*.total_kg'            => 'required|numeric|min:0.01',
                'plans.*.start_lat'           => 'required|numeric|between:-90,90',
                'plans.*.start_lng'           => 'required|numeric|between:-180,180',
                'plans.*.end_lat'             => 'required|numeric|between:-90,90',
                'plans.*.end_lng'             => 'required|numeric|between:-180,180',
                'plans.*.radius_km'           => 'required|numeric|min:1|max:200',
                'plans.*.route_geometry'      => 'required|array',
                'plans.*.hauling_rate_per_kg' => 'nullable|numeric|min:0.1',
                'plans.*.terrain'             => 'nullable|string|in:flat,rolling,mountainous',
                'plans.*.notes'               => 'nullable|string|max:500',
                'plans.*.stop_order'        => 'nullable|array',
                'plans.*.stop_order.*'      => 'integer|exists:harvests,id',
                'plans.*.farm_distances'    => 'nullable|array',
                'plans.*.farm_distances.*'  => 'numeric|min:0',
                'plans.*.route_distance_km' => 'nullable|numeric|min:0',
                'excluded'                   => 'nullable|array',
                'excluded.*.harvest_id'      => 'integer|exists:harvests,id',
                'excluded.*.reason'          => 'string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }

            $logisticsProfile = Auth::user()->logisticsProfile;

            if (!$logisticsProfile) {
                return response()->json(['error' => 'No logistics profile found.'], 403);
            }

            $jobIds = [];
            try {
                $jobIds = DB::transaction(function () use ($validator, $logisticsProfile) {
                    $ids = [];
                    foreach ($validator->validated()['plans'] as $planData) {
                        $result = $this->jobService->confirmPoolingPlan($planData, $logisticsProfile->id);

                        if (isset($result['error'])) {
                            throw new \RuntimeException('Route for truck #' . $planData['truck_id'] . ': ' . $result['error'], $result['status'] ?? 422);
                        }

                        $ids[] = $result['pooling_job_id'];
                    }
                    return $ids;
                });
            } catch (\RuntimeException $e) {
                return response()->json(['error' => $e->getMessage()], $e->getCode() !== 0 ? $e->getCode() : 500);
            } catch (\Exception $e) {
                Log::error('Pooling confirm-batch error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                return response()->json(['error' => 'Route confirmation failed. Please try again or contact support.'], 500);
            }

            $excluded = $validator->validated()['excluded'] ?? [];
            foreach ($excluded as $e) {
                $harvest = \App\Models\Harvest::find($e['harvest_id'] ?? null);
                if ($harvest) {
                    $cropName = $harvest->crop?->name ?? $harvest->crop_type ?? 'crop';
                    self::sendNotification(
                        $harvest->user_id,
                        'Route Offer — Your Crop Was Not Included',
                        "Your {$cropName} was not included in this route: {$e['reason']}. It is still available — arrange another route offer.",
                        route('farmer.proposals')
                    );
                }
            }

            return response()->json([
                'success' => true,
                'job_ids' => $jobIds,
                'message' => count($jobIds) . ' route(s) created successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Pooling confirm-batch error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Route confirmation failed. Please try again or contact support.'], 500);
        }
    }

    public function index()
    {
        $logisticsProfile = auth()->user()->logisticsProfile;
        extract($this->jobService->getProposalsForPartner($logisticsProfile->id));
        $isCoop = $logisticsProfile->logistics_type === 'cooperative';

        return view('logistics.proposals-index', compact('proposals', 'cancelledProposals', 'readyForDispatch', 'isCoop'));
    }

    public function show(PoolingJob $poolingJob)
    {
        $this->authorize('update', $poolingJob);

        return redirect()->route('pooling.cost-ledger', $poolingJob);
    }

    public function farmerProposals()
    {
        $proposals = $this->jobService->getProposalsForFarmer(auth()->user());

        return view('farmers.farmer-proposals', compact('proposals'));
    }

    public function acceptProposal(PoolingJob $poolingJob)
    {
        $user = auth()->user();

        $this->jobService->loadHarvests($poolingJob);
        $this->authorize('view', $poolingJob);

        if (!$poolingJob->harvests()->where('user_id', $user->id)->exists()) {
            abort(403, "You don't have crops on this route offer.");
        }

        $result = $this->jobService->canAcceptProposal($poolingJob, $user);

        if (isset($result['error'])) {
            abort($result['status'], $result['error']);
        }

        return back()->with('success', 'You have accepted the pooling proposal.')
            ->with('next_steps', [
                'title'   => 'Proposal accepted',
                'message' => 'You have accepted the pooling proposal.',
                'steps'   => [
                    'Your crop is now locked into this route.',
                    'Wait for the other farmers to accept before the route is confirmed.',
                    'Once confirmed, you will be notified; then confirm your quantity and upload your payment receipt under Cost Ledger.',
                ],
                'cta' => ['label' => 'View Proposal', 'url' => route('farmer.proposals')],
            ]);
    }

    public function rejectProposal(PoolingJob $poolingJob)
    {
        $user = auth()->user();

        $this->jobService->loadHarvests($poolingJob);
        $this->authorize('view', $poolingJob);

        if (!$poolingJob->harvests()->where('user_id', $user->id)->exists()) {
            abort(403, "You don't have crops on this route offer.");
        }

        $result = $this->jobService->canRejectProposal($poolingJob, $user);

        if (isset($result['error'])) {
            abort($result['status'], $result['error']);
        }

        return back()->with('success', 'You rejected the proposal. Your crop is back on the haul board.')
            ->with('next_steps', [
                'title'   => 'Proposal declined',
                'message' => 'You rejected the proposal. Your crop is back on the haul board.',
                'steps'   => [
                    'Your crop is available again for other routes.',
                    'The route may continue without you if other farmers accept.',
                    'If the route becomes empty, it will be cancelled and the truck released.',
                ],
            ]);
    }

    public function confirmProposal(PoolingJob $poolingJob)
    {
        $this->jobService->loadHarvests($poolingJob);
        $this->authorize('view', $poolingJob);

        $this->jobService->confirmProposal($poolingJob);

        return back()->with('success', 'Route confirmed successfully.')
            ->with('next_steps', [
                'title'   => 'Route confirmed',
                'message' => 'Route confirmed successfully.',
                'steps'   => [
                    'All farmers accepted, so the route is now confirmed.',
                    'Assign a driver if not yet assigned, then the driver can accept and start the job.',
                    'Monitor the route under your proposals and deliveries as it progresses.',
                ],
                'cta' => ['label' => 'View Proposals', 'url' => route('pooling.index')],
            ]);
    }
}
