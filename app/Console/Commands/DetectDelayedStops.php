<?php

namespace App\Console\Commands;

use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Console\Command;

/**
 * Flags any active haul job stop that's still pending/arrived well past its
 * planned_arrival_at, notifies the cooperative's admins AND the specific
 * farmer/buyer waiting on that stop, then stamps delay_notified_at so it's
 * never re-notified. Feeds the "Running late" badge on the tracking pages
 * (see HaulJobStop::isFlaggedLate()).
 */
class DetectDelayedStops extends Command
{
    protected $signature = 'haul:detect-delays';

    protected $description = 'Flag haul job stops running late past their planned arrival and notify the cooperative plus the waiting farmer/buyer.';

    public function handle(): int
    {
        $thresholdMinutes = (float) config('harvesthaul.logistics.delay_threshold_minutes', 20);
        $cutoff = now()->subMinutes($thresholdMinutes);

        $stops = HaulJobStop::query()
            ->whereIn('status', [HaulJobStop::STATUS_PENDING, HaulJobStop::STATUS_ARRIVED])
            ->whereNotNull('planned_arrival_at')
            ->where('planned_arrival_at', '<', $cutoff)
            ->whereNull('delay_notified_at')
            ->whereHas('haulJob', fn ($q) => $q->whereIn('status', [HaulJob::STATUS_SCHEDULED, HaulJob::STATUS_PICKED_UP]))
            ->with(['haulJob', 'haulRequest', 'buyerOrder'])
            ->get();

        foreach ($stops as $stop) {
            $this->flagDelayed($stop);
        }

        $this->info("Flagged {$stops->count()} delayed stop(s).");

        return self::SUCCESS;
    }

    private function flagDelayed(HaulJobStop $stop): void
    {
        $job = $stop->haulJob;
        $isDelivery = $stop->isDeliveryStop();
        $label = $isDelivery ? 'delivery' : 'pickup';

        $admins = User::where('role', UserRole::COOP_ADMIN->value)
            ->where('cooperative_id', $job->cooperative_id)
            ->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id'  => $admin->id,
                'title'    => 'Stop running late',
                'message'  => "Stop {$stop->sequence_no} on trip {$job->id} ({$label}) hasn't arrived and is now past its planned time. Check in with the driver.",
                'link'     => route('coop.procurement.index'),
                'category' => 'haul',
            ]);
        }

        $waitingUserId = $isDelivery ? $stop->buyerOrder?->buyer_id : $stop->haulRequest?->farmer_id;

        if ($waitingUserId) {
            Notification::create([
                'user_id'  => $waitingUserId,
                'title'    => 'Your '.($isDelivery ? 'delivery' : 'pickup').' is running late',
                'message'  => 'The trip is behind its planned arrival time. Your cooperative has been notified.',
                'link'     => $isDelivery ? route('buyer.orders.track', $stop->buyer_order_id) : route('farmer.haul-requests.track', $stop->haul_request_id),
                'category' => 'haul',
            ]);
        }

        $stop->update(['delay_notified_at' => now()]);
    }
}
