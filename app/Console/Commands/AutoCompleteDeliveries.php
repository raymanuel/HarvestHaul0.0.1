<?php

namespace App\Console\Commands;

use App\Models\PoolingJob;
use Illuminate\Console\Command;

class AutoCompleteDeliveries extends Command
{
    protected $signature = 'deliveries:auto-complete';
    protected $description = 'Auto-complete deliveries awaiting buyer confirmation for over 48 hours.';

    public function handle(): int
    {
        $cutoff = now()->subHours(48);

        $jobs = PoolingJob::where('status', 'awaiting_confirmation')
            ->where('completed_at', '<=', $cutoff)
            ->get();

        $count = 0;

        foreach ($jobs as $job) {
            $job->update(['status' => 'completed']);

            // Mark buyer_confirmed_at as auto-completed
            foreach ($job->harvests as $harvest) {
                $job->harvests()->updateExistingPivot($harvest->id, [
                    'buyer_confirmed_at' => now(),
                ]);
            }

            \App\Models\AuditLog::create([
                'admin_id'    => 0,
                'action'      => 'auto_complete_delivery',
                'target_type' => 'pooling_jobs',
                'target_id'   => $job->id,
                'notes'       => "Route #{$job->id} auto-completed after 48 hours without buyer confirmation.",
            ]);

            // Notify logistics
            if ($job->logisticsProfile && $job->logisticsProfile->user_id) {
                \App\Models\Notification::create([
                    'user_id' => $job->logisticsProfile->user_id,
                    'title'   => 'Delivery Auto-Completed',
                    'message' => "Route #{$job->id} was auto-completed after 48 hours.",
                    'link'    => route('pooling.cost-ledger', $job),
                ]);
            }

            $count++;
        }

        $this->info("Auto-completed {$count} deliveries.");

        return self::SUCCESS;
    }
}
