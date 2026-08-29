<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\HaulRequest;
use App\Models\HarvestStatus;
use App\Models\Notification;
use App\Models\PoolingJob;
use App\Models\User;
use Illuminate\Console\Command;

class ExpireBookedHaulRequests extends Command
{
    protected $signature = 'haul-requests:auto-expire-booked';
    protected $description = 'Reopen booked haul requests whose chosen logistics partner never created a pooling job within 48 hours.';

    public function handle(): int
    {
        $cutoff = now()->subHours(48);

        $bookedRequests = HaulRequest::where('status', 'booked')
            ->where('updated_at', '<=', $cutoff)
            ->with('harvest')
            ->get();

        $count = 0;

        foreach ($bookedRequests as $haulRequest) {
            $harvest = $haulRequest->harvest;

            if (!$harvest) {
                continue;
            }

            // If the chosen partner is actively working on a route covering this
            // harvest, leave the booking alone.
            $activeJob = PoolingJob::whereIn('status', ['pending', 'confirmed', 'in_progress', 'awaiting_confirmation'])
                ->whereHas('harvests', fn($q) => $q->where('harvest_id', $harvest->id))
                ->exists();

            if ($activeJob) {
                continue;
            }

            // Reopen the haul request so other partners can express intent again.
            $haulRequest->update(['status' => 'open']);

            // Decline the accepted intent — that partner failed to fulfill.
            $haulRequest->intents()
                ->where('status', 'accepted')
                ->update(['status' => 'declined']);

            // Release the harvest back to the logistics marketplace.
            if ($harvest->status === HarvestStatus::BOOKED) {
                $harvest->update(['status' => HarvestStatus::SOLD]);
            }

            Notification::create([
                'user_id' => $haulRequest->user_id,
                'title'   => 'Haul Booking Expired',
                'message' => "The logistics partner you chose for your harvest '{$harvest->crop_type}' never started a route. Your haul request has been reopened so other partners can serve you.",
                'link'    => route('farmer.haul-requests'),
                'type'    => 'haul_booking_expired',
            ]);

            $adminId = User::where('role', 'admin')->first()?->id ?? 1;

            AuditLog::create([
                'admin_id'    => $adminId,
                'action'      => 'auto_expired_booked_haul_request',
                'target_type' => 'haul_requests',
                'target_id'   => $haulRequest->id,
                'notes'       => "Haul request #{$haulRequest->id} reopened after 48h because the accepted partner never created a pooling job.",
            ]);

            $count++;
        }

        $this->info("Reopened {$count} expired booked haul request(s).");

        return self::SUCCESS;
    }
}
