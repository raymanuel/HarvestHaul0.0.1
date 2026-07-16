<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\Negotiation;
use App\Models\NegotiationMessage;
use App\Models\NegotiationStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FinalizeDealAction
{
    /**
     * Finalize a B2B negotiation: complete the deal, update harvest status, record the transaction.
     *
     * @return string 'fully_sold' or 'partially_sold'
     *
     * @throws \RuntimeException if the negotiation is not in AGREED state or volume exceeds remaining.
     */
    public function execute(Negotiation $negotiation, User $buyer, array $validated): string
    {
        $result = 'unknown';

        DB::transaction(function () use ($negotiation, $validated, $buyer, &$result) {
            $lockedNegotiation = Negotiation::lockForUpdate()->find($negotiation->id);
            if ($lockedNegotiation->status !== NegotiationStatus::AGREED) {
                throw new \RuntimeException('Cannot finalize. You must both agree to terms first.');
            }

            $harvest = Harvest::lockForUpdate()->find($lockedNegotiation->harvest_id);

            $remaining = (float) ($harvest->remaining_quantity_kg ?? $harvest->quantity_kg);
            if ((float) $lockedNegotiation->negotiated_volume > $remaining) {
                throw new \RuntimeException('Negotiated volume exceeds remaining harvest quantity. Another deal may have been finalized.');
            }

            $newRemaining = round($remaining - (float) $lockedNegotiation->negotiated_volume, 2);

            $lockedNegotiation->update([
                'status'               => NegotiationStatus::COMPLETED,
                'destination_address'  => $validated['destination_address'],
                'destination_latitude' => $validated['destination_latitude'],
                'destination_longitude' => $validated['destination_longitude'],
                'last_activity_at'     => now(),
            ]);

            if ($newRemaining <= 0) {
                $harvest->update([
                    'remaining_quantity_kg' => 0,
                    'status'                => HarvestStatus::SOLD,
                    'visibility'            => 'logistics_only',
                    'destination_address'   => $validated['destination_address'],
                    'destination_latitude'  => $validated['destination_latitude'],
                    'destination_longitude' => $validated['destination_longitude'],
                ]);
                $result = 'fully_sold';
            } else {
                $isIndependent = $harvest->user?->farmerProfile?->affiliation_type === 'independent';
                $harvest->update([
                    'remaining_quantity_kg' => $newRemaining,
                    'status'                => HarvestStatus::PARTIALLY_SOLD,
                    'visibility'            => $isIndependent ? 'buyers_only' : 'both',
                ]);
                $result = 'partially_sold';
            }

            NegotiationMessage::create([
                'negotiation_id' => $lockedNegotiation->id,
                'sender_id'      => $buyer->id,
                'message_text'   => "[System Message] Deal finalized! Drop-off submitted: {$validated['destination_address']}" .
                    ($newRemaining > 0 ? " ({$newRemaining} kg remaining available)" : ''),
            ]);

            AuditLog::create([
                'admin_id'    => $buyer->id,
                'action'      => $newRemaining <= 0 ? 'harvest_fully_sold' : 'harvest_partially_sold',
                'target_type' => 'harvest',
                'target_id'   => $harvest->id,
                'notes'       => "Buyer {$buyer->name} purchased {$lockedNegotiation->negotiated_volume}kg at ₱{$lockedNegotiation->negotiated_price}/kg. Remaining: {$newRemaining}kg.",
            ]);
        });

        return $result;
    }
}
