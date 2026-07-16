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
        $negotiation->load('harvest.crop', 'buyer', 'farmer');

        // Notify buyer that farmer agreed
        self::sendNotification(
            $negotiation->buyer_id,
            'Terms Agreed',
            "The farmer has agreed to your terms for '{$negotiation->harvest?->crop?->name}'. Ready to finalize.",
            route('buyer.negotiations'),
            'negotiation_agreed',
            'negotiation'
        );

        // Notify farmer that buyer agreed
        self::sendNotification(
            $negotiation->farmer_id,
            'Terms Agreed',
            "The buyer has agreed to your terms. Ready to finalize the deal.",
            route('farmer.negotiations'),
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
