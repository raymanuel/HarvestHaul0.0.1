<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Services\NotificationPreferenceService;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

trait Notifiable
{
    protected static function sendNotification(int $userId, string $title, string $message, string $link = null, string $type = null, string $category = null): void
    {
        try {
            if ($category && !app(NotificationPreferenceService::class)->isEnabled($userId, $category)) {
                return;
            }

            Notification::create(array_filter([
                'user_id'  => $userId,
                'title'    => $title,
                'message'  => $message,
                'link'     => $link,
                'type'     => $type,
                'category' => $category,
            ], fn($v) => $v !== null));
        } catch (\Exception $e) {
            Log::warning("Failed to send notification to user {$userId}: " . $e->getMessage());
        }
    }

    protected static function sendBulkNotifications(array $notifications): void
    {
        try {
            $now = now();
            $rows = array_map(fn($n) => array_filter([
                'user_id'    => $n['user_id'],
                'title'      => $n['title'],
                'message'    => $n['message'],
                'link'       => $n['link'] ?? null,
                'type'       => $n['type'] ?? null,
                'category'   => $n['category'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ], fn($v) => $v !== null), $notifications);

            Notification::insert($rows);
        } catch (\Exception $e) {
            Log::warning("Failed to send bulk notifications: " . $e->getMessage());
        }
    }

    protected static function logAudit(?int $adminId, string $action, string $targetType, int $targetId, string $notes): void
    {
        AuditLog::create([
            'admin_id'    => $adminId,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'notes'       => $notes,
        ]);
    }

    // ──────────────────────────────────────────────
    // Profile verification notifications
    // ──────────────────────────────────────────────

    protected static function notifyProfileVerified(User $user): void
    {
        $label = str_replace('_', ' ', $user->role);
        static::sendNotification(
            $user->id,
            'Profile Verified',
            "Your {$label} profile has been verified by the administrator.",
            route('dashboard')
        );
    }

    protected static function notifyProfileRejected(User $user): void
    {
        $label = str_replace('_', ' ', $user->role);
        static::sendNotification(
            $user->id,
            'Profile Verification Rejected',
            "Your {$label} profile verification was rejected by the administrator.",
            route('dashboard')
        );
    }

    protected static function notifyIdentityVerified(User $user): void
    {
        static::sendNotification(
            $user->id,
            'Identity Verified',
            'Your identity has been verified by the administrator.',
            route('dashboard')
        );
    }

    protected static function notifyIdentityRejected(User $user): void
    {
        static::sendNotification(
            $user->id,
            'Identity Verification Rejected',
            'Your identity verification was rejected. Please upload a clearer ID photo.',
            route('dashboard')
        );
    }

    // ──────────────────────────────────────────────
    // Document notifications
    // ──────────────────────────────────────────────

    protected static function notifyDocumentApproved(User $user, string $filename, string $route): void
    {
        static::sendNotification(
            $user->id,
            'Document Approved',
            "Your uploaded document '{$filename}' has been approved.",
            $route
        );
    }

    protected static function notifyDocumentRejected(User $user, string $filename, string $reason, string $route): void
    {
        static::sendNotification(
            $user->id,
            'Document Rejected',
            "Your uploaded document '{$filename}' was rejected. Reason: {$reason}",
            $route
        );
    }

    // ──────────────────────────────────────────────
    // Haul request / intent notifications
    // ──────────────────────────────────────────────

    protected static function notifyHaulIntentExpressed(int $userId, string $companyName, ?float $rate): void
    {
        $msg = "{$companyName} expressed intent to haul your harvest";
        if ($rate) {
            $msg .= " at ₱" . number_format($rate, 2) . '/kg.';
        } else {
            $msg .= '.';
        }

        static::sendNotification($userId, 'New Haul Intent', $msg, route('farmer.haul-requests'), 'haul_intent', 'haul');
    }

    protected static function notifyHaulBookingConfirmed(int $userId, int $harvestId, ?float $rate = null): void
    {
        $msg = "Your haul intent for harvest #{$harvestId} was accepted. Harvest is now booked for pickup.";
        if ($rate) {
            $msg = "Your haul for harvest #{$harvestId} at ₱" . number_format($rate, 2) . "/kg has been booked for pickup.";
        }

        static::sendNotification($userId, 'Haul Booking Confirmed', $msg, route('logistics.haul-negotiations'), 'haul_booked', 'haul');
    }

    protected static function notifyHaulIntentDeclined(int $userId, int $harvestId): void
    {
        static::sendNotification(
            $userId,
            'Haul Intent Declined',
            "Your haul intent for harvest #{$harvestId} was declined.",
            route('logistics.haul-negotiations'),
            'haul_intent_declined',
            'haul'
        );
    }

    protected static function notifyHaulRateOffer(int $userId, string $companyName, float $rate): void
    {
        static::sendNotification(
            $userId,
            'New Hauling Rate Offer',
            "{$companyName} proposed ₱" . number_format($rate, 2) . "/kg to haul your harvest.",
            route('farmer.haul-requests'),
            'haul_rate_offer',
            'haul'
        );
    }

    protected static function notifyHaulCounterRate(int $userId, float $rate): void
    {
        static::sendNotification(
            $userId,
            'Counter Rate Received',
            "Farmer countered your hauling rate at ₱" . number_format($rate, 2) . "/kg.",
            route('logistics.haul-negotiations'),
            'haul_counter',
            'haul'
        );
    }

    protected static function notifyHaulBookedViaAgreement(int $userId, float $rate): void
    {
        static::sendNotification(
            $userId,
            'Haul Booking Confirmed',
            "Farmer booked your haul at ₱" . number_format($rate, 2) . "/kg. Harvest is booked for pickup.",
            route('logistics.haul-negotiations'),
            'haul_booked',
            'haul'
        );
    }

    protected static function notifyHaulDealAgreed(int $userId, string $companyName, float $rate): void
    {
        static::sendNotification(
            $userId,
            'Haul Deal Agreed',
            "{$companyName} agreed to haul your harvest at ₱" . number_format($rate, 2) . "/kg. Confirm to book.",
            route('farmer.haul-requests'),
            'haul_agreed',
            'haul'
        );
    }

    // ──────────────────────────────────────────────
    // Pooling job notifications
    // ──────────────────────────────────────────────

    protected static function notifyJobInTransit(int $logisticsUserId, string $driverName, int $jobId, $harvests, ?string $legType = null): void
    {
        if ($legType === 'outbound') {
            $order = \App\Models\PoolingJob::find($jobId)?->outboundOrder;
            if ($order) {
                static::sendNotification(
                    $logisticsUserId,
                    'Outbound Delivery In Transit',
                    "Driver {$driverName} has started outbound delivery #{$order->id} (Route #{$jobId}). The truck is now heading to {$order->customerCard?->name}.",
                    route('coop.outbound.show', $order)
                );
                return;
            }
        }

        static::sendNotification(
            $logisticsUserId,
            'Job In Transit',
            "Driver {$driverName} has started Route #{$jobId}.",
            route('pooling.show', $jobId)
        );

        static::sendBulkNotifications(
            $harvests->map(fn($h) => [
                'user_id' => $h->user_id,
                'title'   => 'Harvest Shipment In Transit',
                'message' => "Your harvest '{$h->crop->name}' in Route #{$jobId} is now in transit.",
                'link'    => route('tracking.index'),
            ])->toArray()
        );
    }

    protected static function notifyJobAwaitingConfirmation(int $logisticsUserId, string $driverName, int $jobId, $harvests, ?int $buyerId, ?string $legType = null): void
    {
        if ($legType === 'outbound') {
            $order = \App\Models\PoolingJob::find($jobId)?->outboundOrder;
            if ($order) {
                static::sendNotification(
                    $logisticsUserId,
                    'Outbound Delivery Awaiting Confirmation',
                    "Driver {$driverName} finalized outbound delivery #{$order->id} (Route #{$jobId}). Awaiting {$order->customerCard?->name}'s receipt confirmation — monitor it from Outbound Orders.",
                    route('coop.outbound.show', $order)
                );
                return;
            }
        }

        static::sendNotification(
            $logisticsUserId,
            'Job Awaiting Buyer Confirmation',
            "Driver {$driverName} finalized Route #{$jobId}. Awaiting buyer receipt confirmation — monitor the outcome from your Proposal Inbox.",
            route('pooling.show', $jobId)
        );

        static::sendBulkNotifications(
            $harvests->map(fn($h) => [
                'user_id' => $h->user_id,
                'title'   => 'Harvest Shipment Delivered',
                'message' => "Your harvest '{$h->crop->name}' in Route #{$jobId} has been delivered. Confirm your delivered quantity and upload your payment receipt under Cost Ledger.",
                'link'    => route('pooling.cost-ledger', $jobId),
            ])->toArray()
        );

        if ($buyerId) {
            static::sendNotification(
                $buyerId,
                'Delivery Ready — Confirm Receipt',
                "Your order in Route #{$jobId} has been delivered. Go to Deliveries to confirm receipt and complete your order.",
                route('buyer.tracking')
            );
        }
    }

    protected static function notifyHaulingCostShare(int $userId, int $jobId, float $share): void
    {
        static::sendNotification(
            $userId,
            'Your Hauling Cost Share',
            "Route #{$jobId} accepted. Your hauling share is ₱" . number_format($share, 2) . ", weighted by your cargo volume and haul distance.",
            route('farmer.proposals'),
            'haul_share',
            'hauling'
        );
    }

    protected static function notifyRouteConfirmed($poolingJob): void
    {
        $notifications = [];

        if ($poolingJob->driver_id) {
            $notifications[] = [
                'user_id' => $poolingJob->driver_id,
                'title'   => 'New Route Confirmed',
                'message' => "All farmers accepted. Route #{$poolingJob->id} has been dispatched to you.",
                'link'    => route('driver.dashboard'),
            ];
        }

        $logisticsUser = $poolingJob->logisticsProfile->user ?? null;
        if ($logisticsUser) {
            $logisticsMessage = $poolingJob->driver_id
                ? "All farmers accepted Proposal #{$poolingJob->id}. Route is now confirmed and your assigned driver is ready. The driver is dispatched to begin pickup — track the run live from your Proposal Inbox."
                : "All farmers accepted Proposal #{$poolingJob->id}. Route is now confirmed. Next: assign a driver from your Proposal Inbox, then the delivery run can begin.";

            $notifications[] = [
                'user_id' => $logisticsUser->id,
                'title'   => 'Proposal Confirmed',
                'message' => $logisticsMessage,
                'link'    => route('pooling.index'),
            ];
        }

        // Notify each participating farmer that the route is confirmed.
        if ($poolingJob->relationLoaded('harvests')) {
            $notifiedFarmers = [];
            foreach ($poolingJob->harvests as $h) {
                $farmerId = $h->user_id;
                if (isset($notifiedFarmers[$farmerId])) {
                    continue;
                }
                $notifiedFarmers[$farmerId] = true;

                $farmerMessage = $poolingJob->driver_id
                    ? "Route #{$poolingJob->id} is confirmed for your crop and has been sent to your assigned driver for pickup. Next, expect: pickup at your farm → in-transit tracking → delivery confirmation. You can also confirm your quantity and upload your payment receipt under Cost Ledger."
                    : "Route #{$poolingJob->id} is confirmed for your crop. A driver will be assigned shortly, then pickup and delivery will proceed. Next, expect: pickup at your farm → in-transit tracking → delivery confirmation. You can also confirm your quantity and upload your payment receipt under Cost Ledger.";

                $notifications[] = [
                    'user_id' => $farmerId,
                    'title'   => 'Route Confirmed',
                    'message' => $farmerMessage,
                    'link'    => route('pooling.cost-ledger', $poolingJob->id),
                ];
            }
        }

        if ($notifications) {
            static::sendBulkNotifications($notifications);
        }
    }

    protected static function notifyProposalPartiallyRejected(int $logisticsUserId, int $jobId): void
    {
        static::sendNotification(
            $logisticsUserId,
            'Proposal Partially Rejected',
            "Some farmers rejected Proposal #{$jobId}. Review and adjust.",
            route('pooling.index')
        );
    }

    protected static function notifyReceiptSubmitted(int $logisticsUserId, string $farmerName, int $jobId): void
    {
        static::sendNotification(
            $logisticsUserId,
            'Hauling Payment Receipt Submitted',
            "Farmer {$farmerName} submitted their hauling payment receipt for Route #{$jobId}.",
            route('pooling.cost-ledger', $jobId)
        );
    }

    protected static function notifyPaymentVerified(int $userId, string $cropName, int $jobId): void
    {
        static::sendNotification(
            $userId,
            'Hauling Payment Verified',
            "Your freight cost payment for '{$cropName}' on Route #{$jobId} has been verified and marked as paid.",
            route('pooling.cost-ledger', $jobId)
        );
    }

    protected static function notifyQuantityConfirmed(int $logisticsUserId, string $farmerName, float $kg, int $jobId): void
    {
        static::sendNotification(
            $logisticsUserId,
            'Actual Quantity Confirmed',
            "Farmer {$farmerName} confirmed actual quantity of {$kg} kg for Route #{$jobId}.",
            route('pooling.cost-ledger', $jobId)
        );
    }

    protected static function notifyBuyerConfirmedReceipt(int $logisticsUserId, string $buyerName, int $jobId): void
    {
        static::sendNotification(
            $logisticsUserId,
            'Buyer Confirmed Receipt',
            "Buyer {$buyerName} confirmed receipt for Route #{$jobId}.",
            route('pooling.cost-ledger', $jobId)
        );
    }

    // ──────────────────────────────────────────────
    // B2B negotiation notifications
    // ──────────────────────────────────────────────

    protected static function notifyNegotiationStarted(int $farmerId, string $buyerName, int $negotiationId, int $harvestId, string $cropType): void
    {
        static::sendNotification(
            $farmerId,
            'New B2B Negotiation',
            "{$buyerName} is interested in your product #{$harvestId} ({$cropType}).",
            route('farmer.deal-room', $negotiationId),
            'negotiation_started',
            'negotiation'
        );
    }

    protected static function notifyDealCancelled(int $logisticsUserId, int $harvestId, string $cropType, int $jobId): void
    {
        static::sendNotification(
            $logisticsUserId,
            'Deal Cancelled — Harvest Removed from Route',
            "A deal for harvest #{$harvestId} ({$cropType}) was cancelled. Route #{$jobId} has been updated.",
            route('pooling.index'),
            'deal_cancelled',
            'negotiation'
        );
    }

    // ──────────────────────────────────────────────
    // Harvest notifications
    // ──────────────────────────────────────────────

    protected static function notifyListingSoldElsewhere(int $userId, string $cropName): void
    {
        static::sendNotification(
            $userId,
            'Listing Sold Elsewhere',
            "Your open inquiry for '{$cropName}' was cancelled because the harvest was sold outside the platform.",
            route('buyer.negotiations')
        );
    }

    protected static function notifyHarvestPosted(int $userId, int $harvestId, string $cropType, ?int $coopLogisticsUserId): void
    {
        static::sendNotification(
            $userId,
            'Harvest Posted',
            "Your harvest ({$cropType}) was posted and is now live on the crop board. Go to Your Harvests to view your post.",
            route('harvests.index'),
            'harvest_posted',
            'system'
        );

        if ($coopLogisticsUserId) {
            static::sendNotification(
                $coopLogisticsUserId,
                'New Crop Posted by Member',
                "A cooperative member posted crop #{$harvestId} ({$cropType}). View it on the crop board to start a conversation.",
                route('buyer.crop-board'),
                'harvest_posted',
                'logistics'
            );
        }
    }

    protected static function notifyDealFinalizedToCoop(int $coopLogisticsUserId, int $harvestId, string $cropType): void
    {
        static::sendNotification(
            $coopLogisticsUserId,
            'Deal Finalized',
            "Deal finalized for crop #{$harvestId} ({$cropType}). Proceed to Route Planning.",
            route('route.optimization'),
            'deal_finalized',
            'negotiation'
        );
    }

    protected static function notifyDealFinalizedToFarmer(int $farmerId, int $harvestId, string $cropType): void
    {
        static::sendNotification(
            $farmerId,
            'Deal Finalized',
            "Deal finalized for crop #{$harvestId} ({$cropType}). Awaiting your cooperative's route offer.",
            route('farmer.proposals'),
            'deal_finalized',
            'negotiation'
        );
    }

    protected static function notifyNewRouteProposal(int $farmerId, string $cropType, int $jobId): void
    {
        static::sendNotification(
            $farmerId,
            'New Route Offer',
            "Your crop ({$cropType}) has a new route offer (Route #{$jobId}). Review your cost share and accept.",
            route('farmer.proposals'),
            'route_proposal',
            'logistics'
        );
    }

    protected static function notifyFarmerAcceptedProposal(int $coopLogisticsUserId, string $farmerName, int $jobId, int $acceptedCount, int $totalFarmers): void
    {
        static::sendNotification(
            $coopLogisticsUserId,
            'Farmer Accepted Route',
            "Farmer {$farmerName} accepted Route #{$jobId} ({$acceptedCount}/{$totalFarmers} farmers).",
            route('pooling.index'),
            'farmer_accepted',
            'logistics'
        );
    }
}
