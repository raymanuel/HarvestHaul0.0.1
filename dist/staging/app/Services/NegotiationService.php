<?php

namespace App\Services;

use App\Exceptions\NegotiationException;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\Negotiation;
use App\Models\NegotiationMessage;
use App\Models\NegotiationStatus;
use App\Models\User;
use App\Traits\Notifiable;
use Illuminate\Support\Facades\DB;

class NegotiationService
{
    use Notifiable;

    /**
     * Initiate a B2B negotiation for a harvest listing.
     *
     * @throws NegotiationException
     */
    public function startNegotiation(
        Harvest $harvest,
        User $initiator,
        float $offeredPrice,
        ?string $message = null,
    ): Negotiation {
        $isCoopLogistics = $initiator->role === 'logistics_partner'
            && $initiator->logisticsProfile
            && $initiator->logisticsProfile->isCooperative();

        if ($initiator->role !== 'buyer' && !$isCoopLogistics) {
            abort(403, 'Only commercial buyers or cooperative logistics partners can initiate B2B negotiations.');
        }

        if ($initiator->role === 'buyer') {
            $buyerProfile = $initiator->buyerProfile;
            if (!$buyerProfile || !$buyerProfile->is_verified) {
                throw new NegotiationException('Your buyer account must be verified before starting negotiations.');
            }
        } elseif ($isCoopLogistics) {
            if (!$initiator->logisticsProfile->is_verified) {
                throw new NegotiationException('Your logistics account must be verified before starting negotiations.');
            }
        }

        // Check harvest has pickup coordinates (before lock)
        $pickupLat = $harvest->latitude ?? $harvest->farmer?->farmerProfile?->latitude;
        $pickupLng = $harvest->longitude ?? $harvest->farmer?->farmerProfile?->longitude;

        if (is_null($pickupLat) || is_null($pickupLng)) {
            throw new NegotiationException('This product has no pickup coordinates and cannot be negotiated. The farmer must set their farm location first.');
        }

        // Backfill harvest coordinates from farmer profile if missing
        if (is_null($harvest->latitude) || is_null($harvest->longitude)) {
            $harvest->update([
                'latitude'  => $pickupLat,
                'longitude' => $pickupLng,
            ]);
        }

        // Avoid duplicate active negotiations for the same harvest
        $existing = Negotiation::where('buyer_id', $initiator->id)
            ->where('harvest_id', $harvest->id)
            ->whereIn('status', [NegotiationStatus::OPEN, NegotiationStatus::AGREED])
            ->first();

        if ($existing) {
            return $existing;
        }

        // Lock harvest row to prevent two buyers claiming it simultaneously
        $logisticsProfileId = $isCoopLogistics ? $initiator->logisticsProfile->id : null;

        $negotiation = DB::transaction(function () use ($harvest, $initiator, $isCoopLogistics, $logisticsProfileId) {
            $cooperativeId = $logisticsProfileId;
            $locked = Harvest::lockForUpdate()->find($harvest->id);

            if (!in_array($locked->status, HarvestStatus::buyerAvailable())) {
                throw new NegotiationException(
                    "This product (status: {$locked->status->value}) is no longer available for negotiation."
                );
            }

            if (!$locked->farmer?->farmerProfile) {
                throw new NegotiationException(
                    'The farmer has not completed their profile. This product cannot be negotiated.'
                );
            }

            $farmerProfile = $locked->farmer->farmerProfile;
            $farmerAffiliation = $farmerProfile->affiliation_type;
            $isVisible = in_array($locked->visibility, ['buyers_only', 'both']);

            if (!$isVisible) {
                throw new NegotiationException('This product is not visible to buyers.');
            }

            if ($isCoopLogistics) {
                if ($farmerAffiliation !== 'cooperative' || $farmerProfile->cooperative_id !== $cooperativeId) {
                    throw new NegotiationException(
                        "This farmer is not a member of your cooperative. " .
                        "(Farmer cooperative_id: {$farmerProfile->cooperative_id}, Your cooperative_id: {$cooperativeId})"
                    );
                }
            } else {
                if ($farmerAffiliation !== 'independent') {
                    throw new NegotiationException(
                        'This farmer is affiliated with a cooperative and cannot be negotiated by independent buyers.'
                    );
                }
            }

            $locked->update(['status' => 'negotiating']);

            return Negotiation::create([
                'buyer_id'          => $initiator->id,
                'farmer_id'         => $locked->user_id,
                'harvest_id'        => $locked->id,
                'negotiated_price'  => null,
                'negotiated_volume' => $locked->remaining_quantity_kg ?? $locked->quantity_kg,
                'status'            => 'OPEN',
            ]);
        });

        if (is_null($negotiation)) {
            throw new NegotiationException('This product is no longer available for negotiation.');
        }

        $negotiation->load('harvest');

        NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $initiator->id,
            'message_text'   => "Hello! I am interested in your product #{$negotiation->harvest_id} ({$negotiation->harvest->crop_type}). Let's discuss pricing and volume.",
        ]);

        $negotiation->update(['last_activity_at' => now()]);

        self::notifyNegotiationStarted(
            $negotiation->farmer_id,
            $initiator->name,
            $negotiation->id,
            $negotiation->harvest_id,
            $negotiation->harvest->crop_type
        );

        return $negotiation;
    }

    /**
     * Propose custom B2B unit price, volume, and hauling rate terms.
     *
     * @return array{message: NegotiationMessage, negotiated_price: float, negotiated_volume: float, hauling_rate_per_kg: ?float}
     */
    public function proposeTerms(Negotiation $negotiation, User $user, float $price, float $volume, ?float $haulRate = null): array
    {
        $maxVolume = $negotiation->harvest->remaining_quantity_kg ?? $negotiation->harvest->quantity_kg;

        if ($volume > (float) $maxVolume) {
            throw new NegotiationException(
                'Negotiated volume cannot exceed the remaining quantity (' . number_format((float) $maxVolume) . ' kg).'
            );
        }

        $crop = $negotiation->harvest->crop;
        if ($crop && $crop->baseline_price_per_kg) {
            $baseline = (float) $crop->baseline_price_per_kg;
            $minAllowed = $baseline * 0.10;
            $maxAllowed = $baseline * 5.00;
            if ($price < $minAllowed || $price > $maxAllowed) {
                throw new NegotiationException(
                    'Proposed price ₱' . number_format($price, 2) . '/kg is significantly outside the expected range '
                    . '(₱' . number_format($minAllowed, 2) . ' – ₱' . number_format($maxAllowed, 2) . '). Please adjust your offer.'
                );
            }
        } else {
            if ($price < 1) {
                throw new NegotiationException('Proposed price must be at least ₱1.00/kg.');
            }
        }

        if (in_array($negotiation->status, [NegotiationStatus::AGREED, NegotiationStatus::COMPLETED])) {
            throw new NegotiationException('Terms are locked. Cannot propose new terms on a ' . $negotiation->status . ' negotiation.');
        }

        $roundCount = NegotiationMessage::where('negotiation_id', $negotiation->id)
            ->where('message_text', 'LIKE', '[System Offer]%')
            ->count();

        if ($roundCount >= 10) {
            throw new NegotiationException('Maximum negotiation rounds reached (10). Accept the current offer or end the negotiation.');
        }

        $negotiation->update([
            'negotiated_price'     => $price,
            'negotiated_volume'    => $volume,
            'hauling_rate_per_kg'  => $haulRate ?? null,
            'status'               => 'OPEN',
            'last_activity_at'     => now(),
        ]);

        $formattedPrice  = number_format($price, 2);
        $formattedVolume = number_format($volume);

        $msgText = "[System Offer] Proposes terms: ₱{$formattedPrice}/kg for {$formattedVolume} kg.";
        if ($haulRate !== null) {
            $msgText .= " Hauling rate: ₱" . number_format($haulRate, 2) . "/kg.";
        }

        $msg = NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $user->id,
            'message_text'   => $msgText,
        ]);

        return [
            'message'            => $msg,
            'negotiated_price'   => $price,
            'negotiated_volume'  => $volume,
            'hauling_rate_per_kg' => $haulRate,
        ];
    }

    /**
     * Agree to the proposed terms.
     */
    public function agreeTerms(Negotiation $negotiation, User $user): void
    {
        if (is_null($negotiation->negotiated_price) || is_null($negotiation->negotiated_volume)) {
            throw new NegotiationException('Cannot agree. No terms have been proposed yet.');
        }

        if ($negotiation->status !== NegotiationStatus::OPEN) {
            throw new NegotiationException('Cannot agree. Current status is ' . $negotiation->status . '.');
        }

        $lastProposal = NegotiationMessage::where('negotiation_id', $negotiation->id)
            ->where('message_text', 'LIKE', '[System Offer]%')
            ->latest()
            ->first();

        if ($lastProposal && $lastProposal->sender_id === $user->id) {
            throw new NegotiationException('You proposed these terms. The other party must agree first.');
        }

        $negotiation->update([
            'status'           => 'AGREED',
            'last_activity_at' => now(),
        ]);

        NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $user->id,
            'message_text'   => "[System Message] Agreed to the proposed terms. Ready to finalize drop-off.",
        ]);
    }

    /**
     * Cancel a negotiation and restore harvest status.
     * Auto-detaches from pending pooling jobs.
     */
    public function cancelDeal(Negotiation $negotiation, User $user): void
    {
        if (!in_array($negotiation->status, [NegotiationStatus::OPEN, NegotiationStatus::AGREED])) {
            throw new NegotiationException('Cannot cancel this negotiation. Current status: ' . $negotiation->status);
        }

        $harvest = Harvest::find($negotiation->harvest_id);

        $activeJobs = $harvest->poolingJobs()
            ->where('pooling_jobs.status', 'in', ['confirmed', 'in_progress'])
            ->exists();

        if ($activeJobs) {
            throw new NegotiationException('Cannot cancel — harvest is assigned to an active logistics route that is already confirmed.');
        }

        DB::transaction(function () use ($negotiation, $harvest, $user) {
            $locked = Harvest::lockForUpdate()->find($harvest->id);

            $negotiation->update([
                'status'           => 'CANCELLED',
                'last_activity_at' => now(),
            ]);

            NegotiationMessage::create([
                'negotiation_id' => $negotiation->id,
                'sender_id'      => $user->id,
                'message_text'   => '[System Message] Negotiation cancelled.',
            ]);

            if ($locked->status === HarvestStatus::NEGOTIATING) {
                $hasCompletedDeals = $locked->negotiations()
                    ->where('id', '!=', $negotiation->id)
                    ->where('status', 'COMPLETED')
                    ->exists();

                $isIndependent = $locked->farmer?->farmerProfile?->affiliation_type === 'independent';

                $locked->update([
                    'status'     => $hasCompletedDeals ? 'partially_sold' : 'active',
                    'visibility' => $isIndependent ? 'buyers_only' : 'both',
                ]);
            }

            $pendingJobs = $locked->poolingJobs()->where('status', 'pending')->get();
            foreach ($pendingJobs as $job) {
                $job->harvests()->detach($locked->id);
                $job->load('harvests');
                if ($job->harvests->isEmpty()) {
                    $job->status = 'cancelled';
                    $job->save();
                    $job->truck?->update(['status' => 'available']);
                } else {
                    $job->total_kg = $job->harvests->sum('pivot.quantity_kg');
                    $job->farm_count = $job->harvests->count();
                    $job->save();
                }

                self::notifyDealCancelled(
                    $job->logisticsProfile?->user_id,
                    $locked->id,
                    $locked->crop_type,
                    $job->id
                );
            }
        });
    }

    /**
     * Get active negotiations where the user is buyer or farmer.
     */
    public function getActiveNegotiationsForUser(User $user)
    {
        $userId = $user->id;

        return Negotiation::where(function ($q) use ($userId) {
                $q->where('buyer_id', $userId)
                  ->orWhere('farmer_id', $userId);
            })
            ->with([
                'buyer:id,name,role',
                'farmer:id,name,role',
                'harvest:id,crop_type,variety,crop_id,crop_variety_id',
                'harvest.crop:id,name',
                'harvest.cropVariety:id,name',
            ])
            ->addSelect(['unread_count' => NegotiationMessage::selectRaw('COUNT(*)')
                ->whereColumn('negotiation_id', 'negotiations.id')
                ->where('sender_id', '!=', $userId)
                ->whereRaw('created_at > COALESCE(
                    CASE WHEN ? = negotiations.buyer_id THEN negotiations.buyer_last_read_at
                         ELSE negotiations.farmer_last_read_at
                    END,
                    "1970-01-01 00:00:00"
                )', [$userId])
            ])
            ->latest('last_activity_at')
            ->get();
    }
}
