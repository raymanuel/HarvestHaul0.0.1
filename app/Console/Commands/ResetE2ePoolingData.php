<?php

namespace App\Console\Commands;

use App\Models\Harvest;
use App\Models\PoolingJob;
use Illuminate\Console\Command;

class ResetE2ePoolingData extends Command
{
    protected $signature = 'pooling:reset-e2e {--yes : Skip confirmation prompt}';
    protected $description = 'Reset pooling/negotiation state left behind by E2E browser tests (only touches harvests with notes LIKE \'E2E%\')';

    public function handle(): int
    {
        $env = config('app.env');
        if (!$this->option('yes') && !in_array($env, ['local', 'testing'])) {
            $this->error('Refusing: pooling:reset-e2e only runs when APP_ENV=local/testing, or with the --yes safety flag.');
            return self::FAILURE;
        }
        if (!$this->option('yes') && !$this->confirm('This will cancel E2E pooling jobs and reset E2E harvest statuses. Continue?')) {
            return self::SUCCESS;
        }

        // Find all E2E harvests (the only ones our browser specs create have "E2E" in notes).
        $e2eHarvests = Harvest::where('notes', 'LIKE', 'E2E%')->pluck('id');

        if ($e2eHarvests->isEmpty()) {
            $this->info('No E2E harvests found — nothing to reset.');
            return self::SUCCESS;
        }

        $harvestCount = $e2eHarvests->count();

        // 1. Capture the job IDs FIRST (they live in the pivot rows we're about to delete).
        $jobIds = \DB::table('pooling_job_harvests')
            ->whereIn('harvest_id', $e2eHarvests)
            ->pluck('pooling_job_id')
            ->unique();

        // 2. Delete pivot rows that link E2E harvests to any pooling job, then cancel
        //    those jobs (skipping already completed/delivered ones to avoid orphaning invoices).
        $pivotDeleted = \DB::table('pooling_job_harvests')
            ->whereIn('harvest_id', $e2eHarvests)
            ->delete();

        $cancelled = 0;
        foreach ($jobIds as $jobId) {
            $job = PoolingJob::withTrashed()->find($jobId);
            if (!$job) continue;
            if (in_array($job->status, ['completed', 'cancelled'])) continue;
            $job->update(['status' => 'cancelled']);
            $cancelled++;
        }

        // 2b. Cancelling a pooling job (or deleting its pivots) never frees the truck
        //     the app reserved for it — the next E2E run would find zero usable trucks.
        //     Restore every truck tied to a cancelled E2E job back to 'available'.
        $truckIds = PoolingJob::withTrashed()
            ->whereIn('id', $jobIds)
            ->whereNotNull('truck_id')
            ->pluck('truck_id')
            ->unique();
        $trucksRestored = \App\Models\Truck::whereIn('id', $truckIds)
            ->where('status', '!=', 'available')
            ->update(['status' => 'available']);

        // 3. Reset E2E harvest statuses back to 'active' (or 'pending' if they
        //    were never promoted). This is safe because the next E2E run will
        //    re-post them fresh. Only reset harvests stuck in a pooled state.
        $resetStatuses = ['sold', 'assigned', 'in_progress', 'completed'];
        $resetCount = Harvest::where('notes', 'LIKE', 'E2E%')
            ->whereIn('status', $resetStatuses)
            ->update(['status' => 'active']);

        $this->info("E2E reset complete:");
        $this->info("  - {$harvestCount} E2E harvests found");
        $this->info("  - {$pivotDeleted} pooling_job_harvest pivot rows deleted");
        $this->info("  - {$cancelled} pooling jobs cancelled");
        $this->info("  - {$resetCount} harvest statuses reset to 'active'");
        $this->info("  - {$trucksRestored} trucks restored to 'available'");

        return self::SUCCESS;
    }
}
