<?php

namespace App\Console\Commands;

use App\Models\Negotiation;
use App\Models\Notification;
use Illuminate\Console\Command;

class AutoCloseStaleNegotiations extends Command
{
    protected $signature = 'negotiations:auto-close-stale';
    protected $description = 'Auto-close negotiations that have been OPEN with no activity for 48 hours.';

    public function handle(): int
    {
        $cutoff = now()->subHours(48);

        $staleNegotiations = Negotiation::where('status', 'OPEN')
            ->where(function ($q) use ($cutoff) {
                $q->where('last_activity_at', '<=', $cutoff)
                  ->orWhereNull('last_activity_at')
                  ->where('created_at', '<=', $cutoff);
            })
            ->get();

        $count = 0;

        foreach ($staleNegotiations as $neg) {
            $neg->update(['status' => 'CANCELLED']);

            // Restore harvest status — check if there are other completed deals
            if ($neg->harvest && $neg->harvest->status === 'negotiating') {
                $hasCompletedDeals = $neg->harvest->negotiations()
                    ->where('id', '!=', $neg->id)
                    ->where('status', 'COMPLETED')
                    ->exists();

                if ($hasCompletedDeals) {
                    $isIndependent = $neg->harvest->user?->farmerProfile?->affiliation_type === 'independent';
                    $neg->harvest->update([
                        'status'     => 'partially_sold',
                        'visibility' => $isIndependent ? 'buyers_only' : 'both',
                    ]);
                } else {
                    $neg->harvest->update(['status' => 'active']);
                }
            }

            // Notify both parties
            foreach ([$neg->buyer_id, $neg->farmer_id] as $userId) {
                Notification::create([
                    'user_id' => $userId,
                    'title' => 'Negotiation Closed (Inactive)',
                    'message' => "Negotiation for product #{$neg->harvest_id} was closed due to 48 hours of inactivity.",
                    'link' => route('negotiations.room', $neg->id),
                    'type' => 'negotiation_closed',
                ]);
            }

            $count++;
        }

        $this->info("Closed {$count} stale negotiation(s).");

        return self::SUCCESS;
    }
}
