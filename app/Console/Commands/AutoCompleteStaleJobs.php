<?php

namespace App\Console\Commands;

use App\Models\PoolingJob;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;

class AutoCompleteStaleJobs extends Command
{
    protected $signature = 'deliveries:auto-complete-stale';
    protected $description = 'Auto-complete in_progress deliveries older than 48 hours without recent updates.';

    public function handle(): int
    {
        $cutoff = now()->subHours(48);

        $jobs = PoolingJob::where('status', 'in_progress')
            ->where('updated_at', '<', $cutoff)
            ->get();

        $count = 0;

        foreach ($jobs as $job) {
            $job->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            foreach ($job->harvests as $harvest) {
                $harvest->update(['status' => 'completed']);
            }

            if ($job->truck) {
                $job->truck->update(['status' => 'available']);
            }

            $adminId = User::where('role', 'admin')->first()?->id ?? 1;

            AuditLog::create([
                'admin_id'    => $adminId,
                'action'      => 'autocomplete_pooling_job',
                'target_type' => 'pooling_job',
                'target_id'   => $job->id,
                'notes'       => "System auto-completed Route #{$job->id} after 48 hours in transit.",
            ]);

            $count++;
        }

        $this->info("Auto-completed {$count} stale in-progress deliveries.");

        return self::SUCCESS;
    }
}
