<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Lazy auto-complete deliveries older than 48 hours without cron setup
        try {
            if (!\Illuminate\Support\Facades\Cache::has('last_delivery_autocomplete_check')) {
                \Illuminate\Support\Facades\Cache::put('last_delivery_autocomplete_check', true, 300); // 5-minute cooldown
                
                $cutoff = now()->subHours(48);
                $oldJobs = \App\Models\PoolingJob::where('status', 'in_progress')
                    ->where('updated_at', '<', $cutoff)
                    ->get();

                foreach ($oldJobs as $job) {
                    $job->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);

                    // Update child harvests
                    foreach ($job->harvests as $harvest) {
                        $harvest->update(['status' => 'completed']);
                    }

                    // Reset truck status to available
                    if ($job->truck) {
                        $job->truck->update(['status' => 'available']);
                    }

                    $adminId = \App\Models\User::where('role', 'admin')->first()?->id ?? 1;

                    // Create log
                    \App\Models\AuditLog::create([
                        'admin_id'    => $adminId,
                        'action'      => 'autocomplete_pooling_job',
                        'target_type' => 'pooling_job',
                        'target_id'   => $job->id,
                        'notes'       => "System auto-completed Route #{$job->id} after 48 hours in transit.",
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Silence boot exceptions during setup/migrations
        }
    }
}
