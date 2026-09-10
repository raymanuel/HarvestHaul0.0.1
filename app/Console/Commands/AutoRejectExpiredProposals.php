<?php

namespace App\Console\Commands;

use App\Models\HarvestStatus;
use App\Models\Notification;
use App\Models\PoolingJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoRejectExpiredProposals extends Command
{
    protected $signature = 'proposals:auto-reject-expired';
    protected $description = 'Auto-reject pooling proposals that have expired (48h no response).';

    public function handle(): int
    {
        $cutoff = now()->subHours(48);

        $expiredJobs = PoolingJob::where('status', 'pending')
            ->where(DB::raw('COALESCE(proposal_expires_at, created_at)'), '<=', $cutoff)
            ->get();

        $count = 0;

        foreach ($expiredJobs as $job) {
            $job->status = 'cancelled';
            $job->save();

            if ($job->truck) {
                $job->truck->update(['status' => 'available']);
            }

            // Notify logistics partner
            if ($job->logisticsProfile && $job->logisticsProfile->user_id) {
                Notification::create([
                    'user_id' => $job->logisticsProfile->user_id,
                    'title' => 'Proposal Expired & Cancelled',
                    'message' => "Proposal #{$job->id} was auto-cancelled after 48 hours with no farmer response.",
                    'link' => route('pooling.index'),
                    'type' => 'proposal_expired',
                ]);
            }

            // Notify each participating farmer
            $notifiedFarmers = [];
            foreach ($job->harvests as $harvest) {
                if (isset($notifiedFarmers[$harvest->user_id])) {
                    continue;
                }
                $notifiedFarmers[$harvest->user_id] = true;

                Notification::create([
                    'user_id' => $harvest->user_id,
                    'title'   => 'Route Offer Expired',
                    'message' => "Your route offer #{$job->id} expired. Your crop is back on the haul board — create a new haul request or wait for a new offer.",
                    'link'    => route('farmer.proposals'),
                    'type'    => 'proposal_expired',
                ]);
            }

            // Free harvests back to an appropriate status
            foreach ($job->harvests as $harvest) {
                if ($harvest->status === HarvestStatus::ASSIGNED) {
                    $completedNeg = $harvest->negotiations()
                        ->where('status', 'COMPLETED')
                        ->first();

                    $negotiatedVolume = (float) ($completedNeg?->negotiated_volume ?? 0);
                    $harvestQuantity  = (float) $harvest->quantity_kg;

                    if ($completedNeg && $negotiatedVolume > 0 && $negotiatedVolume < $harvestQuantity) {
                        $harvest->update(['status' => HarvestStatus::PARTIALLY_SOLD]);
                    } elseif ($completedNeg && $negotiatedVolume >= $harvestQuantity) {
                        $harvest->update(['status' => HarvestStatus::SOLD]);
                    } else {
                        $harvest->update(['status' => HarvestStatus::ACTIVE]);
                    }
                }

                // Set pivot to terminal state
                $job->harvests()->updateExistingPivot($harvest->id, ['status' => 'cancelled']);
            }

            \App\Models\AuditLog::create([
                'admin_id' => 0,
                'action' => 'auto_rejected_expired_proposal',
                'target_type' => 'pooling_jobs',
                'target_id' => $job->id,
                'notes' => "Proposal #{$job->id} auto-rejected after 48 hours with no farmer response.",
            ]);

            $count++;
        }

        $this->info("Auto-rejected {$count} expired proposal(s).");

        return self::SUCCESS;
    }
}
