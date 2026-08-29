<?php

namespace App\Services;

use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\NegotiationStatus;
use App\Models\Notification;
use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
use App\Models\User;
use App\Traits\GeometryHelper;
use App\Traits\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PoolingJobService
{
    use GeometryHelper, Notifiable;

    protected ResourcePoolingService $poolingService;
    protected InvoiceService $invoiceService;

    public function __construct(ResourcePoolingService $poolingService, InvoiceService $invoiceService)
    {
        $this->poolingService = $poolingService;
        $this->invoiceService = $invoiceService;
    }

    public function preparePlan(User $user, array $validated): array
    {
        $logisticsProfile = $user->logisticsProfile;

        if (!$logisticsProfile) {
            return ['error' => 'No logistics profile found.', 'status' => 403];
        }

        $truck = \App\Models\Truck::where('id', $validated['truck_id'])
            ->where('logistics_profile_id', $logisticsProfile->id)
            ->where('status', 'available')
            ->first();

        if (!$truck) {
            return ['error' => 'Truck not found or currently unavailable.', 'status' => 404];
        }

        $plan = $this->poolingService->plan(
            truck: $truck,
            nearbyHarvestIds: $validated['harvest_ids'],
            startLat: (float) $validated['start_lat'],
            startLng: (float) $validated['start_lng'],
            endLat: (float) $validated['end_lat'],
            endLng: (float) $validated['end_lng'],
            radiusKm: (float) $validated['radius_km'],
            haulingRatePerKg: (float) $validated['hauling_rate_per_kg'],
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

        return $plan;
    }

    public function getProposalsForPartner(int $logisticsProfileId): array
    {
        $proposals = PoolingJob::where('logistics_profile_id', $logisticsProfileId)
            ->where('status', 'pending')
            ->with(['truck', 'harvests.farmer', 'harvests.negotiations' => fn($q) => $q->where('status', NegotiationStatus::COMPLETED)])
            ->latest()
            ->take(50)
            ->get();

        $cancelledProposals = PoolingJob::where('logistics_profile_id', $logisticsProfileId)
            ->where('status', 'cancelled')
            ->where('updated_at', '>=', now()->subHours(24))
            ->with(['truck', 'harvests.farmer'])
            ->latest('updated_at')
            ->take(20)
            ->get();

        $readyForDispatch = PoolingJob::where('logistics_profile_id', $logisticsProfileId)
            ->where('status', 'confirmed')
            ->where('updated_at', '>=', now()->subHours(48))
            ->with(['truck', 'harvests.farmer'])
            ->latest('updated_at')
            ->take(20)
            ->get();

        return compact('proposals', 'cancelledProposals', 'readyForDispatch');
    }

    public function getProposalsForFarmer(User $user): Collection
    {
        return PoolingJob::where('status', 'pending')
            ->whereHas('harvests', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['truck', 'logisticsProfile', 'harvests' => function ($query) use ($user) {
                $query->where('user_id', $user->id)->with(['crop', 'cropVariety', 'destination', 'negotiations' => fn($q) => $q->where('status', NegotiationStatus::COMPLETED)]);
            }])
            ->latest()
            ->take(50)
            ->get();
    }

    public function canAcceptProposal(PoolingJob $job, User $user): array
    {
        $job->load('harvests');
        $harvest = $job->harvests()->where('user_id', $user->id)->first();

        if ($job->status !== PoolingJobStatus::PENDING) {
            return ['success' => false, 'error' => 'This proposal is no longer open for changes.', 'status' => 422];
        }

        if ($job->proposal_expires_at && $job->proposal_expires_at->isPast()) {
            return ['success' => false, 'error' => 'This proposal has expired. Please wait for a new one.', 'status' => 410];
        }

        $job->harvests()->updateExistingPivot($harvest->id, ['status' => 'accepted']);
        $job->load('harvests');

        $ownShare = (float) ($harvest->pivot->cost_share ?? 0);
        self::notifyHaulingCostShare($user->id, $job->id, $ownShare);

        $coopLogisticsUserId = $job->logisticsProfile?->user_id;
        if ($coopLogisticsUserId) {
            $acceptedCount = $job->harvests->filter(fn($h) => $h->pivot->status === 'accepted')->count();
            $totalFarmers = $job->harvests->count();
            self::notifyFarmerAcceptedProposal($coopLogisticsUserId, $user->name, $job->id, $acceptedCount, $totalFarmers);
        }

        // A rejection only shrinks the route: remaining (non-rejected) farmers
        // accepting confirm the route. Rejected farmers no longer block it.
        $remaining = $job->harvests->filter(fn($h) => $h->pivot->status !== 'rejected');
        $allRemainingAccepted = $remaining->isNotEmpty()
            && $remaining->every(fn($h) => $h->pivot->status === 'accepted');

        if ($allRemainingAccepted) {
            $job->status = PoolingJobStatus::CONFIRMED;
            $job->confirmed_at = now();
            $job->save();

            try {
                $this->invoiceService->generateInvoice($job);
            } catch (\Throwable $e) {
                Log::warning("Invoice generation failed for Route #{$job->id}: " . $e->getMessage());
            }

            Harvest::whereIn('id', $job->harvests->pluck('id'))->update(['status' => HarvestStatus::ASSIGNED]);
            self::notifyRouteConfirmed($job);
        } elseif ($job->harvests->contains(fn($h) => $h->pivot->status === 'rejected')) {
            self::notifyProposalPartiallyRejected($job->logisticsProfile?->user_id, $job->id);
        }

        return ['success' => true];
    }

    public function canRejectProposal(PoolingJob $job, User $user): array
    {
        $job->load('harvests');

        if ($job->status !== PoolingJobStatus::PENDING) {
            return ['success' => false, 'error' => 'This proposal is no longer open for changes.', 'status' => 422];
        }

        if ($job->proposal_expires_at && $job->proposal_expires_at->isPast()) {
            return ['success' => false, 'error' => 'This proposal has expired.', 'status' => 410];
        }

        $harvest = $job->harvests()->where('user_id', $user->id)->first();

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

        $job->harvests()->detach($harvest->id);
        $job->load('harvests');

        if ($job->harvests->isEmpty()) {
            $job->status = PoolingJobStatus::CANCELLED;
            $job->save();

            if ($job->truck) {
                $job->truck->update(['status' => 'available']);
            }
        } else {
            $totalKg = $job->harvests->sum('pivot.quantity_kg');
            $job->total_kg = $totalKg;
            $job->farm_count = $job->harvests->count();
            $job->save();

            app(\App\Actions\ConfirmPoolingPlanAction::class)->recalculateCostShares($job);

            // With per-farmer agreed hauling rates, each farmer's share is fixed
            // (their rate x their kg), so a rejection does not change the others'
            // shares — no re-approval cascade needed.
            if (app(\App\Actions\ConfirmPoolingPlanAction::class)::usesPerFarmerRates($job)) {
                $pendingFarmerIds = [];
            } else {
                $pendingFarmerIds = [];
                foreach ($job->harvests as $remaining) {
                    if ($remaining->pivot->status === 'accepted') {
                        $job->harvests()->updateExistingPivot($remaining->id, ['status' => 'pending']);
                        $pendingFarmerIds[] = $remaining->user_id;
                    }
                }
            }

            if (!empty($pendingFarmerIds)) {
                $notifications = [];
                foreach ($pendingFarmerIds as $farmerId) {
                    $notifications[] = [
                        'user_id'    => $farmerId,
                        'title'      => 'Cost Shares Recalculated — Re-approval Required',
                        'message'    => "A farmer rejected Route #{$job->id}. Your cost share has been recalculated. Please review and re-accept.",
                        'link'       => route('farmer.proposals'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                Notification::insert($notifications);
            }
        }

        $logisticsUser = $job->logisticsProfile->user;
        if ($logisticsUser) {
            Notification::create([
                'user_id' => $logisticsUser->id,
                'title' => 'Farmer Rejected Proposal',
                'message' => "Farmer {$user->name} rejected the proposal for Route #{$job->id}.",
                'link' => route('pooling.index'),
            ]);
        }

        return ['success' => true];
    }

    public function confirmPoolingPlan(array $validated, int $logisticsProfileId): array
    {
        $harvests = Harvest::whereIn('id', $validated['harvest_ids'])->with(['crop', 'negotiations'])->get();

        if ($harvests->isEmpty()) {
            return ['error' => 'No harvests could be selected for this plan.', 'status' => 422];
        }

        $totalKg = (float) $validated['total_kg'];
        $actualHarvestSum = $harvests->sum(function ($h) {
            $completedNegotiation = $h->negotiations->firstWhere('status', 'COMPLETED');
            return $completedNegotiation ? (float) $completedNegotiation->negotiated_volume : (float) $h->quantity_kg;
        });
        if ($totalKg < ($actualHarvestSum * 0.99) || $totalKg > ($actualHarvestSum * 1.01)) {
            return ['error' => 'Submitted total_kg (' . $totalKg . ' kg) does not match actual harvest sum (' . $actualHarvestSum . ' kg).', 'status' => 422];
        }

        $stops = $this->buildStops($harvests);
        $distance = $this->calculateDistance($stops, $harvests, $validated['start_lat'], $validated['start_lng'], $validated['end_lat'], $validated['end_lng']);

        $haulingRate = (float) ($validated['hauling_rate_per_kg'] ?? 0);
        $plan = [
            'selected_harvests'   => $harvests->pluck('id')->toArray(),
            'stops'               => $stops,
            'total_kg'            => $totalKg,
            'truck_id'            => $validated['truck_id'],
            'truck_capacity_kg'   => $validated['truck_capacity_kg'] ?? 0,
            'farm_count'          => $harvests->count(),
            'start_lat'           => (float) $validated['start_lat'],
            'start_lng'           => (float) $validated['start_lng'],
            'end_lat'             => (float) $validated['end_lat'],
            'end_lng'             => (float) $validated['end_lng'],
            'radius_km'           => (float) $validated['radius_km'],
            'total_distance_km'   => round($distance, 2),
            'price_reference'     => round($haulingRate * $totalKg, 2),
            'hauling_rate_per_kg' => $haulingRate,
            'proposal_expires_at' => now()->addHours(48),
            'notes'               => $validated['notes'] ?? null,
            'route_geometry'      => $validated['route_geometry'],
        ];

        $job = app(\App\Actions\ConfirmPoolingPlanAction::class)->execute($plan, $logisticsProfileId);

        $notifiedFarmers = [];
        foreach ($harvests as $h) {
            if (isset($notifiedFarmers[$h->user_id])) {
                continue;
            }
            $notifiedFarmers[$h->user_id] = true;
            self::notifyNewRouteProposal($h->user_id, $h->crop->name ?? $h->crop_type, $job->id);
        }

        return [
            'success'        => true,
            'pooling_job_id' => $job->id,
            'message'        => 'Pooling job confirmed. ' . count($plan['selected_harvests']) . ' farm(s) assigned.',
        ];
    }

    public function confirmProposal(PoolingJob $job): void
    {
        $job->load('harvests.crop');

        $allAccepted = $job->harvests->every(fn($h) => $h->pivot->status === 'accepted');

        if ($allAccepted) {
            $job->status = PoolingJobStatus::CONFIRMED;
            $job->confirmed_at = now();
            $job->save();

            try {
                $this->invoiceService->generateInvoice($job);
            } catch (\Throwable $e) {
                Log::warning("Invoice generation failed for Route #{$job->id}: " . $e->getMessage());
            }

            Harvest::whereIn('id', $job->harvests->pluck('id'))->update(['status' => HarvestStatus::ASSIGNED]);
            self::notifyRouteConfirmed($job);

            \App\Models\AuditLog::create([
                'admin_id'    => $job->logisticsProfile?->user_id ?? 1,
                'action'      => 'confirmed_pooling_plan',
                'target_type' => 'pooling_job',
                'target_id'   => $job->id,
                'notes'       => "Route #{$job->id} confirmed. Total weight: {$job->total_kg} kg.",
            ]);
        }
    }

    public function loadHarvests(PoolingJob $job): void
    {
        $job->load('harvests');
    }

    private function buildStops($harvests): array
    {
        $stops = [];
        $order = 1;
        foreach ($harvests as $h) {
            $completedNegotiation = $h->negotiations->firstWhere('status', 'COMPLETED');
            $stops[] = [
                'harvest_id'   => $h->id,
                'pickup_order' => $order++,
                'latitude'     => (float) ($h->latitude ?? 0),
                'longitude'    => (float) ($h->longitude ?? 0),
                'quantity_kg'  => $completedNegotiation ? (float) $completedNegotiation->negotiated_volume : (float) $h->quantity_kg,
                'crop'         => $h->crop->name ?? $h->crop_type ?? 'Unknown',
            ];
        }
        return $stops;
    }

    private function calculateDistance(array $stops, $harvests, float $startLat, float $startLng, float $endLat, float $endLng): float
    {
        $collectionDistance = 0.0;
        $currentLat = $startLat;
        $currentLng = $startLng;
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

        $returnDistance = $this->haversine($currentLat, $currentLng, $endLat, $endLng);

        return $collectionDistance + $distributionDistance + $returnDistance;
    }
}
