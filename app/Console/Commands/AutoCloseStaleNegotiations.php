<?php

namespace App\Console\Commands;

use App\Models\Negotiation;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoCloseStaleNegotiations extends Command
{
    protected $signature = 'negotiations:auto-close-stale';
    protected $description = 'Auto-close negotiations that have been OPEN/AGREED with no activity for 48 hours.';

    public function handle(): int
    {
        $cutoff = now()->subHours(48);

        $staleIds = Negotiation::whereIn('status', ['OPEN', 'AGREED'])
            ->where(function ($q) use ($cutoff) {
                $q->where('last_activity_at', '<=', $cutoff)
                  ->orWhereNull('last_activity_at')
                  ->where('created_at', '<=', $cutoff);
            })
            ->pluck('id');

        $count = 0;

        foreach ($staleIds as $negId) {
            DB::transaction(function () use ($negId) {
                $neg = Negotiation::lockForUpdate()->find($negId);
                if (!$neg || !in_array($neg->status, ['OPEN', 'AGREED'])) {
                    return;
                }

                $neg->update(['status' => 'CANCELLED']);

                if ($neg->harvest && $neg->harvest->status === 'negotiating') {
                    $hasCompletedDeals = $neg->harvest->negotiations()
                        ->where('id', '!=', $neg->id)
                        ->where('status', 'COMPLETED')
                        ->exists();

                    $isIndependent = $neg->harvest->user?->farmerProfile?->affiliation_type === 'independent';
                    $neg->harvest->update([
                        'status'     => $hasCompletedDeals ? 'partially_sold' : 'active',
                        'visibility' => $isIndependent ? 'buyers_only' : 'both',
                    ]);
                }

                foreach ([$neg->buyer_id, $neg->farmer_id] as $userId) {
                    Notification::create([
                        'user_id'  => $userId,
                        'title'    => 'Negotiation Closed (Inactive)',
                        'message'  => "Negotiation for product #{$neg->harvest_id} was closed due to 48 hours of inactivity.",
                        'link'     => route('negotiations.room', $neg->id),
                        'type'     => 'negotiation_closed',
                        'category' => 'negotiation',
                    ]);
                }
            });

            $count++;
        }

        $this->info("Closed {$count} stale negotiation(s).");

        return self::SUCCESS;
    }
}
