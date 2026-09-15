<?php

namespace App\Console\Commands;

use App\Models\OutboundOrder;
use App\Models\PoolingJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoCompleteDeliveries extends Command
{
    protected $signature = 'deliveries:auto-complete';
    protected $description = 'Auto-complete deliveries awaiting buyer confirmation for over 48 hours.';

    public function handle(): int
    {
        $cutoff = now()->subHours(48);

        $jobs = PoolingJob::where('status', 'awaiting_confirmation')
            ->where('completed_at', '<=', $cutoff)
            ->where(function ($q) {
                $q->where('leg_type', '!=', 'outbound')->orWhereNull('leg_type');
            })
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
                'admin_id'    => null,
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

        $outboundCutoff = now()->subHours(48);

        $orders = OutboundOrder::where('status', 'awaiting_confirmation')
            ->where(function ($q) use ($outboundCutoff) {
                $q->where('delivered_at', '<=', $outboundCutoff)
                    ->orWhere('completed_at', '<=', $outboundCutoff);
            })
            ->get();

        foreach ($orders as $order) {
            DB::transaction(function () use ($order, &$count) {
                $order->update([
                    'status'       => 'completed',
                    'confirmed_at' => now(),
                    'completed_at' => now(),
                ]);

                $order->tracking_token = null;
                $order->save();

                $job = $order->poolingJob;
                if ($job && $job->status === \App\Models\PoolingJobStatus::AWAITING_CONFIRMATION) {
                    $job->update(['status' => \App\Models\PoolingJobStatus::COMPLETED, 'completed_at' => now()]);
                }

                if ($job && $job->truck) {
                    $job->truck->update(['status' => 'available']);
                }

                \App\Models\AuditLog::create([
                    'admin_id'    => null,
                    'action'      => 'auto_complete_outbound',
                    'target_type' => 'outbound_orders',
                    'target_id'   => $order->id,
                    'notes'       => "Outbound order #{$order->id} auto-completed after 48 hours without customer confirmation.",
                ]);

                if ($order->logisticsProfile && $order->logisticsProfile->user_id) {
                    \App\Models\Notification::create([
                        'user_id' => $order->logisticsProfile->user_id,
                        'title'   => 'Outbound Delivery Auto-Completed',
                        'message' => "Outbound order #{$order->id} was auto-completed after 48 hours without customer confirmation.",
                        'link'    => route('coop.outbound.show', $order),
                    ]);
                }

                $count++;
            });
        }

        $this->info("Auto-completed {$count} deliveries.");

        return self::SUCCESS;
    }
}
