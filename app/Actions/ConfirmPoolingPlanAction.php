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

        foreach ($job->harvests as $harvest) {
            try {
                \App\Models\Notification::create([
                    'user_id' => $harvest->user_id,
                    'title'   => 'New Pooling Proposal',
                    'message' => "Your harvest '{$harvest->crop->name}' has been pooled into Route #{$job->id}.",
                    'link'    => route('farmer.proposals'),
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to send pooling notification to user ' . $harvest->user_id . ': ' . $e->getMessage());
            }
        }

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
     * Recalculate per-farmer cost shares proportionally from the negotiated total.
     */
    public function recalculateCostShares(\App\Models\PoolingJob $job): void
    {
        $job->load('harvests');

        $totalCostShare = $job->harvests->sum(function ($h) {
            return (float) ($h->pivot->quantity_kg ?? 0);
        });

        $negotiatedPrice = (float) ($job->negotiated_price ?? $job->price_reference ?? 0);

        if ($totalCostShare <= 0) {
            return;
        }

        foreach ($job->harvests as $h) {
            $share = (float) ($h->pivot->quantity_kg ?? 0);
            $costShare = round(($share / $totalCostShare) * $negotiatedPrice, 2);

            $job->harvests()->updateExistingPivot($h->id, [
                'cost_share' => $costShare,
            ]);
        }
    }
}
