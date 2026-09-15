<?php

namespace App\Http\Controllers;

use App\Actions\OutboundDispatchAction;
use App\Http\Requests\StoreOutboundOrderRequest;
use App\Models\CustomerCard;
use App\Models\DriverProfile;
use App\Models\Negotiation;
use App\Models\NegotiationStatus;
use App\Models\OutboundOrder;
use App\Models\Truck;
use App\Models\User;
use App\Traits\Notifiable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OutboundOrderController extends Controller
{
    use Notifiable;

    public function index()
    {
        $profileId = Auth::user()->logisticsProfile->id;

        $orders = OutboundOrder::forProfile($profileId)
            ->with(['customerCard', 'poolingJob.truck', 'poolingJob.driver'])
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
            ->with('success', "Customer order for {$card->name} saved.")
            ->with('next_steps', [
                'title'   => 'Order drafted',
                'message' => "Customer order saved for {$card->name}.",
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

        $trucks = $outboundOrder->logisticsProfile->availableTrucks()->get();
        $drivers = DriverProfile::where('partner_id', $outboundOrder->logistics_profile_id)->with('user')->get();

        return view('outbound.show', compact('outboundOrder', 'trucks', 'drivers'));
    }

    public function dispatch(Request $request, OutboundOrder $outboundOrder)
    {
        $this->authorizeOwnership($outboundOrder);

        $validator = Validator::make($request->all(), [
            'truck_id'        => ['required', 'integer'],
            'driver_id'       => ['required', 'integer'],
            'start_latitude'  => ['required', 'numeric', 'between:-90,90'],
            'start_longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if ($outboundOrder->status !== 'drafted') {
            $validator->errors()->add('order', 'Only drafted orders can be dispatched.');
            return back()->withErrors($validator)->withInput();
        }

        $profileId = Auth::user()->logisticsProfile->id;

        $validated = $validator->validated();

        $truck = Truck::where('id', $validated['truck_id'])
            ->where('logistics_profile_id', $profileId)->first();
        if (!$truck) {
            $validator->errors()->add('truck_id', 'Truck does not belong to your cooperative.');
        }

        $driver = User::where('id', $validated['driver_id'])
            ->whereHas('driverProfile', function ($q) use ($profileId) {
                $q->where('partner_id', $profileId);
            })->first();
        if (!$driver) {
            $validator->errors()->add('driver_id', 'Driver does not belong to your cooperative.');
        }

        if ($validator->errors()->isNotEmpty()) {
            return back()->withErrors($validator)->withInput();
        }

        $job = DB::transaction(function () use ($outboundOrder, $request) {
            return app(OutboundDispatchAction::class)->execute(
                $outboundOrder,
                (int) $request->truck_id,
                (int) $request->driver_id,
                (float) $request->start_latitude,
                (float) $request->start_longitude
            );
        });

        self::logAudit(Auth::id(), 'dispatched_outbound_order', 'outbound_orders', $outboundOrder->id, "Coop dispatched outbound order #{$outboundOrder->id} (Route #{$job->id}).");

        $customer = $outboundOrder->customerCard;

        self::sendNotification(
            Auth::id(),
            'Customer order dispatched',
            "Customer order #{$outboundOrder->id} to {$customer->name} was dispatched on Route #{$job->id}. Copy the tracking link and send it to {$customer->name} so they can follow the truck.",
            route('coop.outbound.show', $outboundOrder)
        );

        self::sendNotification(
            $driver->id,
            'New customer delivery',
            "Route #{$job->id} delivers {$outboundOrder->total_kg} kg to {$customer->name}. Accept the job and start your trip from the Driver portal.",
            route('driver.jobs.show', $job)
        );

        return redirect()->route('coop.outbound.show', $outboundOrder)
            ->with('success', "Order dispatched to {$customer->name}.")
            ->with('next_steps', [
                'title'   => 'Shipment dispatched',
                'message' => "Route #{$job->id} is assigned to {$truck->truck_name}.",
                'steps'   => [
                    'Copy the tracking link below and send it to the customer.',
                    'The customer opens the link in their browser to follow the truck live.',
                    'When the truck arrives, the customer taps Confirm received on that page.',
                ],
                'cta' => ['label' => 'View Shipment', 'url' => route('coop.outbound.show', $outboundOrder)],
            ]);
    }

    public function cancel(OutboundOrder $outboundOrder)
    {
        $this->authorizeOwnership($outboundOrder);

        if ($outboundOrder->status !== 'drafted') {
            return back()->with('error', 'Only drafted orders can be cancelled.');
        }

        $outboundOrder->update(['status' => 'cancelled']);

        return back()->with('success', 'Customer order cancelled.');
    }

    private function authorizeOwnership(OutboundOrder $outboundOrder): void
    {
        if ($outboundOrder->logistics_profile_id !== Auth::user()->logisticsProfile->id) {
            abort(404);
        }
    }
}