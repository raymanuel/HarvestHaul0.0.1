<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\PoolingJobStatus;
use App\Services\ResourcePoolingService;
use App\Traits\GeometryHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ConfirmPoolingPlanAction
{
    use GeometryHelper;

    protected ResourcePoolingService $poolingService;

    public function __construct(ResourcePoolingService $poolingService)
    {
        $this->poolingService = $poolingService;
    }

    /**
     * Confirm a pooling plan: create the job, calculate costs, notify farmers.
     */
    public function execute(array $plan, int $logisticsProfileId): \App\Models\PoolingJob
    {
        $job = $this->poolingService->confirm($plan, $logisticsProfileId);

        $job->load('harvests.crop');

        $this->recalculateCostShares($job);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'confirmed_pooling_plan',
            'target_type' => 'pooling_job',
            'target_id'   => $job->id,
            'notes'       => "Logistics Partner " . Auth::user()->name . " confirmed route #{$job->id} (Total weight: {$job->total_kg} kg, Price: ₱" . ($job->negotiated_price ?? $job->price_reference ?? 0) . ").",
        ]);

        return $job;
    }

    /**
     * Recalculate per-farmer cost shares.
     *
     * Preferred: per-farmer hauling rate agreed in the chat, so a farmer's share
     * is their own rate x their kg (fixed regardless of other farmers).
     * Fallback (no agreed per-farmer rate, e.g. independent flow): the old
     * flat route rate allocated by weight x distance.
     */
    public function recalculateCostShares(\App\Models\PoolingJob $job): void
    {
        $job->load('harvests.negotiations');

        $activeHarvests = $job->harvests->filter(fn($h) => ($h->pivot->status ?? 'pending') !== 'rejected');

        $totalKg = $activeHarvests->sum(fn($h) => (float) ($h->pivot->quantity_kg ?? $h->quantity_kg ?? 0));
        if ($totalKg <= 0) {
            return;
        }

        // Prefer per-farmer negotiated hauling rates.
        $hasPerFarmerRates = $activeHarvests->contains(fn($h) => $this->agreedHaulRate($h) !== null);

        if ($hasPerFarmerRates) {
            foreach ($activeHarvests as $h) {
                $rate = $this->agreedHaulRate($h);
                $qty  = (float) ($h->pivot->quantity_kg ?? $h->quantity_kg ?? 0);
                // Fall back to the flat rate when this farmer has no agreed rate.
                if ($rate === null) {
                    $rate = (float) ($job->hauling_rate_per_kg ?? 0);
                }
                $job->harvests()->updateExistingPivot($h->id, [
                    'cost_share' => round($rate * $qty, 2),
                ]);
            }
            // Zero out rejected pivots' cost_share
            $rejectedIds = $job->harvests->filter(fn($h) => ($h->pivot->status ?? 'pending') === 'rejected')->pluck('id');
            foreach ($rejectedIds as $rid) {
                $job->harvests()->updateExistingPivot($rid, ['cost_share' => 0]);
            }
            $job->negotiated_price = $activeHarvests->sum(fn($h) => (float) ($h->pivot->cost_share ?? 0));
            $job->save();
            return;
        }

        // Fallback: flat rate x total kg, allocated by weight x distance.
        $rate = (float) ($job->hauling_rate_per_kg ?? 0);
        $total = $rate > 0
            ? round($rate * $totalKg, 2)
            : (float) ($job->negotiated_price ?? $job->price_reference ?? 0);

        if ($total <= 0) {
            return;
        }

        $scores = [];
        $farmDistances = $job->farm_distances ?? [];
        foreach ($activeHarvests as $h) {
            $qty  = (float) ($h->pivot->quantity_kg ?? $h->quantity_kg ?? 0);
            // Prefer OSRM road distance when available, fall back to Haversine
            $dist = $farmDistances[$h->id] ?? null;
            if ($dist === null || $dist <= 0) {
                $dist = max(
                    $this->haversine(
                        (float) ($h->latitude ?? 0),
                        (float) ($h->longitude ?? 0),
                        (float) ($h->destination_latitude ?? 0),
                        (float) ($h->destination_longitude ?? 0)
                    ),
                    1.0
                );
            }
            $scores[$h->id] = $qty * max($dist, 1.0);
        }

        $totalScore = array_sum($scores);
        if ($totalScore <= 0) {
            return;
        }

        foreach ($scores as $harvestId => $score) {
            $job->harvests()->updateExistingPivot($harvestId, [
                'cost_share' => round($total * ($score / $totalScore), 2),
            ]);
        }

        // Zero out rejected pivots' cost_share
        $rejectedIds = $job->harvests->filter(fn($h) => ($h->pivot->status ?? 'pending') === 'rejected')->pluck('id');
        foreach ($rejectedIds as $rid) {
            $job->harvests()->updateExistingPivot($rid, ['cost_share' => 0]);
        }

        $job->negotiated_price = $total;
        $job->save();
    }

    private function agreedHaulRate(\App\Models\Harvest $harvest): ?float
    {
        $negotiation = $harvest->negotiations
            ->filter(fn($n) => $n->status === \App\Models\NegotiationStatus::COMPLETED)
            ->first();

        if ($negotiation && $negotiation->hauling_rate_per_kg !== null) {
            return (float) $negotiation->hauling_rate_per_kg;
        }

        return null;
    }

    /**
     * True when the job's farmers carry their own agreed hauling rates
     * (per-farmer cost shares are independent), so removing one farmer
     * does not change the others' shares.
     */
    public static function usesPerFarmerRates(\App\Models\PoolingJob $job): bool
    {
        $job->loadMissing('harvests.negotiations');

        return $job->harvests->contains(function ($h) {
            $negotiation = $h->negotiations
                ->filter(fn($n) => $n->status === \App\Models\NegotiationStatus::COMPLETED)
                ->first();
            return $negotiation && $negotiation->hauling_rate_per_kg !== null;
        });
    }
}
