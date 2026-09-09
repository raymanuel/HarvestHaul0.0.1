<?php

namespace App\Observers;

use App\Models\Negotiation;
use App\Models\NegotiationStatus;
use App\Models\Notification;
use App\Traits\Notifiable;

class NegotiationObserver
{
    use Notifiable;

    public function updated(Negotiation $negotiation): void
    {
        if (!$negotiation->wasChanged('status')) {
            return;
        }

        match ($negotiation->status) {
            NegotiationStatus::AGREED => $this->onAgreed($negotiation),
            NegotiationStatus::COMPLETED => $this->onCompleted($negotiation),
            NegotiationStatus::CANCELLED => $this->onCancelled($negotiation),
            default => null,
        };
    }

    private function onAgreed(Negotiation $negotiation): void
    {
        $negotiation->load('harvest.crop', 'buyer.logisticsProfile', 'farmer');

        $actorId = auth()->id();

        // No authenticated actor (seeders/console) — notify both parties as before.
        if (!$actorId) {
            self::sendNotification(
                $negotiation->buyer_id,
                'Terms Agreed',
                "The farmer has agreed to your terms for '{$negotiation->harvest?->crop?->name}'. Ready to finalize.",
                route('buyer.negotiations'),
                'negotiation_agreed',
                'negotiation'
            );

            self::sendNotification(
                $negotiation->farmer_id,
                'Terms Agreed',
                "The buyer has agreed to your terms. Ready to finalize the deal.",
                route('farmer.negotiations'),
                'negotiation_agreed',
                'negotiation'
            );
            return;
        }

        if ((int) $actorId === (int) $negotiation->buyer_id) {
            // Buyer/cooperative agreed — notify only the farmer.
            $buyer = $negotiation->buyer;
            $isCoop = $buyer->role === 'logistics_partner'
                && $buyer->logisticsProfile
                && $buyer->logisticsProfile->isCooperative();
            $who = $isCoop
                ? ($buyer->logisticsProfile->company_name ?: $buyer->name)
                : $buyer->name;

            self::sendNotification(
                $negotiation->farmer_id,
                'Terms Agreed',
                "{$who} has agreed to your terms for '{$negotiation->harvest?->crop?->name}'. Ready to finalize.",
                route('farmer.negotiations'),
                'negotiation_agreed',
                'negotiation'
            );
            return;
        }

        // Farmer agreed — notify only the buyer side.
        self::sendNotification(
            $negotiation->buyer_id,
            'Terms Agreed',
            "The farmer has agreed to your terms for '{$negotiation->harvest?->crop?->name}'. Ready to finalize.",
            route('buyer.negotiations'),
            'negotiation_agreed',
            'negotiation'
        );
    }

    private function onCompleted(Negotiation $negotiation): void
    {
        $negotiation->load('harvest.crop', 'buyer', 'farmer');

        self::sendNotification(
            $negotiation->farmer_id,
            'Deal Completed',
            "The deal for '{$negotiation->harvest?->crop?->name}' has been finalized.",
            route('farmer.negotiations'),
            'deal_completed',
            'negotiation'
        );
    }

    private function onCancelled(Negotiation $negotiation): void
    {
        $negotiation->load('harvest.crop');

        // Notify the other party
        $notifyUserId = $negotiation->farmer_id;

        self::sendNotification(
            $notifyUserId,
            'Negotiation Cancelled',
            "The negotiation for product #{$negotiation->harvest_id} has been cancelled.",
            route('farmer.negotiations'),
            'negotiation_cancelled',
            'negotiation'
        );
    }
}
