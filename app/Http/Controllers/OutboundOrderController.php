<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOutboundOrderRequest;
use App\Models\CustomerCard;
use App\Models\Negotiation;
use App\Models\NegotiationStatus;
use App\Models\OutboundOrder;
use App\Traits\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OutboundOrderController extends Controller
{
    use Notifiable;

    public function index()
    {
        $profileId = Auth::user()->logisticsProfile->id;

        $orders = OutboundOrder::forProfile($profileId)
            ->with(['customerCard'])
            ->latest()
            ->paginate(20);

        return view('outbound.index', compact('orders'));
    }

    public function create()
    {
        $profileId = Auth::user()->logisticsProfile->id;

        $customers = CustomerCard::forProfile($profileId)->latest()->get();

        // Suggestions = the coop's completed inbound deals (advisory prefill, not enforced).
        $suggestions = Negotiation::where('buyer_id', Auth::id())
            ->where('status', NegotiationStatus::COMPLETED->value)
            ->with('harvest')
            ->latest('last_activity_at')
            ->take(50)
            ->get()
            ->map(fn ($n) => [
                'crop_type' => $n->harvest?->crop_type ?? 'Crop',
                'quantity_kg' => (float) $n->negotiated_volume,
                'rate_per_kg' => (float) ($n->negotiated_price > 0 && $n->negotiated_volume > 0
                    ? $n->negotiated_price / $n->negotiated_volume
                    : 0),
            ]);

        $preselectedCustomerId = request('customer_card_id');

        return view('outbound.create', compact('customers', 'suggestions', 'preselectedCustomerId'));
    }

    public function store(StoreOutboundOrderRequest $request)
    {
        $profileId = Auth::user()->logisticsProfile->id;
        $card = CustomerCard::forProfile($profileId)->findOrFail($request->customer_card_id);

        $order = DB::transaction(function () use ($card, $request) {
            $lines = $request->validated()['lines'];
            $totalKg = array_sum(array_map('floatval', array_column($lines, 'quantity_kg')));
            $totalAmount = 0.0;
            foreach ($lines as $line) {
                $totalAmount += (float) $line['quantity_kg'] * (float) $line['rate_per_kg'];
            }

            $order = OutboundOrder::create([
                'customer_card_id'     => $card->id,
                'logistics_profile_id' => $card->logistics_profile_id,
                'status'               => 'drafted',
                'total_kg'             => $totalKg,
                'total_amount'         => round($totalAmount, 2),
                'notes'                => $request->validated('notes'),
            ]);

            foreach ($lines as $line) {
                $order->orderLines()->create([
                    'crop_type'   => $line['crop_type'],
                    'quantity_kg' => $line['quantity_kg'],
                    'rate_per_kg' => $line['rate_per_kg'],
                    'subtotal'    => round((float) $line['quantity_kg'] * (float) $line['rate_per_kg'], 2),
                ]);
            }

            return $order;
        });

        self::logAudit(Auth::id(), 'created_outbound_order', 'outbound_orders', $order->id, "Coop created outbound order #{$order->id} for {$card->name}.");

        return redirect()->route('coop.outbound.index')
            ->with('success', "Outbound order for {$card->name} saved.")
            ->with('next_steps', [
                'title'   => 'Order drafted',
                'message' => "Outbound order saved for {$card->name}.",
                'steps'   => [
                    'Choose a truck and driver, then press Dispatch to send the shipment.',
                    'Once dispatched, a tracking link is created for the customer.',
                ],
                'cta' => ['label' => 'Dispatch Order', 'url' => route('coop.outbound.show', $order)],
            ]);
    }

    public function show(OutboundOrder $outboundOrder)
    {
        $this->authorizeOwnership($outboundOrder);
        $outboundOrder->load(['customerCard', 'orderLines', 'poolingJob.truck', 'poolingJob.driver']);

        return view('outbound.show', compact('outboundOrder'));
    }

    public function cancel(OutboundOrder $outboundOrder)
    {
        $this->authorizeOwnership($outboundOrder);

        if ($outboundOrder->status !== 'drafted') {
            return back()->with('error', 'Only drafted orders can be cancelled.');
        }

        $outboundOrder->update(['status' => 'cancelled']);

        return back()->with('success', 'Outbound order cancelled.');
    }

    private function authorizeOwnership(OutboundOrder $outboundOrder): void
    {
        if ($outboundOrder->logistics_profile_id !== Auth::user()->logisticsProfile->id) {
            abort(404);
        }
    }
}