<?php

namespace App\Console\Commands;

use App\Models\PoolingJob;
use Illuminate\Console\Command;

class AutoRejectExpiredProposals extends Command
{
    protected $signature = 'proposals:auto-reject-expired';
    protected $description = 'Auto-reject pooling proposals that have expired (48h no response).';

    public function handle(): int
    {
        $cutoff = now()->subHours(48);

        $expiredJobs = PoolingJob::where('status', 'pending')
            ->where('created_at', '<=', $cutoff)
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
                \App\Models\Notification::create([
                    'user_id' => $job->logisticsProfile->user_id,
                    'title' => 'Proposal Expired & Cancelled',
                    'message' => "Proposal #{$job->id} was auto-cancelled after 48 hours with no farmer response.",
                    'link' => route('pooling.index'),
                    'type' => 'proposal_expired',
                ]);
            }

            // Free harvests back to 'sold'
            foreach ($job->harvests as $harvest) {
                if ($harvest->status === 'assigned') {
                    $harvest->update(['status' => 'sold']);
                }
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
