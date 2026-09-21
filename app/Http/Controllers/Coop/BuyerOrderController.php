<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BuyerOrder;
use App\Models\CropAvailability;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuyerOrderController extends Controller
{
    public function index()
    {
        $cooperativeId = $this->cooperativeId();

        $orders = BuyerOrder::forCooperative($cooperativeId)
            ->whereIn('status', [BuyerOrder::STATUS_SUBMITTED, BuyerOrder::STATUS_UNDER_REVIEW])
            ->with(['buyer', 'items'])
            ->orderBy('created_at')
            ->get();

        $recent = BuyerOrder::forCooperative($cooperativeId)
            ->whereIn('status', [BuyerOrder::STATUS_ACCEPTED, BuyerOrder::STATUS_REJECTED])
            ->with(['buyer'])
            ->latest()
            ->limit(12)
            ->get();

        return view('coop.buyer-orders.index', compact('orders', 'recent'));
    }

    public function show(BuyerOrder $buyerOrder)
    {
        $this->authorizeCoop($buyerOrder);

        if ($buyerOrder->status === BuyerOrder::STATUS_SUBMITTED) {
            $buyerOrder->update(['status' => BuyerOrder::STATUS_UNDER_REVIEW]);
        }

        $buyerOrder->load(['buyer', 'items.crop', 'items.cropGrade', 'items.availability']);

        return view('coop.buyer-orders.show', compact('buyerOrder'));
    }

    public function accept(BuyerOrder $buyerOrder)
    {
        $this->authorizeCoop($buyerOrder);

        if (! in_array($buyerOrder->status, [BuyerOrder::STATUS_SUBMITTED, BuyerOrder::STATUS_UNDER_REVIEW], true)) {
            throw ValidationException::withMessages(['order' => 'This order has already been decided.']);
        }

        $buyerOrder->load('items.availability');

        foreach ($buyerOrder->items as $item) {
            $listing = $item->availability;
            if (! $listing || $listing->remaining_kg < (float) $item->quantity_kg) {
                throw ValidationException::withMessages([
                    'order' => "Insufficient remaining stock for {$item->crop?->name}. Cannot accept this order.",
                ]);
            }
        }

        DB::transaction(function () use ($buyerOrder) {
            foreach ($buyerOrder->items as $item) {
                $listing = $item->availability;
                $listing->increment('sold_kg', (float) $item->quantity_kg);
                if ($listing->fresh()->remaining_kg <= 0) {
                    $listing->update(['status' => CropAvailability::STATUS_SOLD_OUT]);
                }
            }

            $buyerOrder->update([
                'status' => BuyerOrder::STATUS_ACCEPTED,
                'accepted_at' => now(),
            ]);
        });

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'accept_buyer_order',
            'target_type' => 'buyer_order',
            'target_id'   => $buyerOrder->id,
            'notes'       => "Order {$buyerOrder->reference} accepted.",
        ]);

        Notification::create([
            'user_id'  => $buyerOrder->buyer_id,
            'title'    => 'Order accepted',
            'message'  => "Your order {$buyerOrder->reference} was accepted. The cooperative will prepare it for delivery.",
            'link'     => route('buyer.orders.show', $buyerOrder),
            'category' => 'buyer_order',
        ]);

        return redirect()->route('coop.buyer-orders.index')
            ->with('success', "Order {$buyerOrder->reference} accepted.");
    }

    public function reject(Request $request, BuyerOrder $buyerOrder)
    {
        $this->authorizeCoop($buyerOrder);

        if (! in_array($buyerOrder->status, [BuyerOrder::STATUS_SUBMITTED, BuyerOrder::STATUS_UNDER_REVIEW], true)) {
            throw ValidationException::withMessages(['order' => 'This order has already been decided.']);
        }

        $data = $request->validate(['rejection_reason' => 'required|string|max:2000']);

        $buyerOrder->update([
            'status' => BuyerOrder::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'reject_buyer_order',
            'target_type' => 'buyer_order',
            'target_id'   => $buyerOrder->id,
            'notes'       => "Order {$buyerOrder->reference} rejected: {$data['rejection_reason']}",
        ]);

        Notification::create([
            'user_id'  => $buyerOrder->buyer_id,
            'title'    => 'Order rejected',
            'message'  => "Your order {$buyerOrder->reference} was rejected: {$data['rejection_reason']}",
            'link'     => route('buyer.orders.show', $buyerOrder),
            'category' => 'buyer_order',
        ]);

        return redirect()->route('coop.buyer-orders.index')
            ->with('success', "Order {$buyerOrder->reference} rejected.");
    }

    private function cooperativeId(): int
    {
        $id = Auth::user()?->cooperative_id;
        if (! $id) {
            abort(403, 'You are not an active cooperative admin.');
        }

        return $id;
    }

    private function authorizeCoop(BuyerOrder $order): void
    {
        if ($order->cooperative_id !== Auth::user()?->cooperative_id) {
            abort(403, 'This order does not belong to your cooperative.');
        }
    }
}
