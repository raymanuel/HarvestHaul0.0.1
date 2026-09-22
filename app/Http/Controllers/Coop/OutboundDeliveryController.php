<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BuyerOrder;
use App\Models\Delivery;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\Notification;
use App\Models\Truck;
use App\Services\Cooperative\ConsolidationEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Coop Admin — outbound delivery planning (Module 14). Plans movement of
 * accepted BuyerOrders to buyers; physical execution is Module 8
 * (Delivery\TripController), same HaulJob/HaulJobStop system used for
 * inbound pickups, distinguished by job_type='delivery'.
 */
class OutboundDeliveryController extends Controller
{
    public function index()
    {
        $cooperativeId = $this->cooperativeId();

        $pendingOrders = BuyerOrder::with('buyer')
            ->where('cooperative_id', $cooperativeId)
            ->where('status', BuyerOrder::STATUS_ACCEPTED)
            ->whereDoesntHave('stop')
            ->orderBy('created_at')
            ->get();

        $trips = HaulJob::with(['truck', 'deliveryPersonnel'])
            ->where('cooperative_id', $cooperativeId)
            ->where('job_type', HaulJob::JOB_TYPE_DELIVERY)
            ->orderByDesc('scheduled_at')
            ->get();

        return view('coop.outbound.index', compact('pendingOrders', 'trips'));
    }

    public function create(Request $request, ConsolidationEngine $engine)
    {
        $this->cooperativeId();
        $date = $request->query('date', today()->toDateString());

        $cooperative = Auth::user()->cooperative;
        $plan = $engine->planDeliveriesForDate($cooperative, $date);

        return view('coop.outbound.create', compact('plan', 'date', 'cooperative'));
    }

    public function store(Request $request, ConsolidationEngine $engine)
    {
        $cooperativeId = $this->cooperativeId();
        $cooperative = Auth::user()->cooperative;

        $orderIds = array_values(array_filter((array) ($request->input('orders') ?? [])));

        if (empty($orderIds)) {
            throw ValidationException::withMessages(['orders' => 'Select at least one accepted order for the trip.']);
        }

        $orders = BuyerOrder::whereIn('id', $orderIds)
            ->where('cooperative_id', $cooperativeId)
            ->where('status', BuyerOrder::STATUS_ACCEPTED)
            ->whereDoesntHave('stop')
            ->get();

        if ($orders->count() !== count($orderIds)) {
            throw ValidationException::withMessages([
                'orders' => 'One or more selected orders are not available to schedule for this cooperative.',
            ]);
        }

        $availableDriverIds = $engine->availableDrivers($cooperative, $request->input('date'))->pluck('id');

        $data = $request->validate([
            'date'     => 'required|date|after_or_equal:today',
            'truck_id' => [
                'required',
                Rule::exists('trucks', 'id')->where('cooperative_id', $cooperativeId)->where('status', 'available'),
            ],
            'delivery_personnel_id' => ['required', Rule::in($availableDriverIds)],
            'sequences' => 'array',
        ], [
            'truck_id.exists' => 'This truck is not available (already in use, in maintenance, or inactive).',
            'delivery_personnel_id.in' => 'This driver is already assigned to another trip on this date.',
        ]);

        $truck = Truck::where('id', $data['truck_id'])->where('cooperative_id', $cooperativeId)->firstOrFail();

        if (! empty($data['sequences'])) {
            $byId = $orders->keyBy('id');
            $ordered = [];
            foreach ($data['sequences'] as $seq => $orderId) {
                if (isset($byId[$orderId])) {
                    $ordered[$seq] = $byId[$orderId];
                }
            }
            if (count($ordered) === $orders->count()) {
                $orders = collect(array_values($ordered));
            }
        }

        $totalWeight = $orders->sum('total_kg');
        if ($totalWeight > (float) $truck->capacity_kg && ! $request->boolean('force')) {
            throw ValidationException::withMessages([
                'truck_id' => 'Total order weight ('.number_format($totalWeight, 2).' kg) exceeds the truck capacity ('.number_format($truck->capacity_kg, 2).' kg). Confirm to proceed anyway or choose a larger truck.',
            ]);
        }

        $finalRoute = $engine->fetchFinalRouteForOrders($orders, $cooperative);
        $schedule = collect($engine->buildScheduleForOrders($orders, $cooperative))->keyBy('request_id');

        $job = DB::transaction(function () use ($cooperativeId, $data, $truck, $orders, $finalRoute, $schedule) {
            $haulJob = HaulJob::create([
                'haul_request_id' => null,
                'cooperative_id'  => $cooperativeId,
                'job_type'        => HaulJob::JOB_TYPE_DELIVERY,
                'delivery_personnel_id' => $data['delivery_personnel_id'],
                'truck_id'        => $truck->id,
                'pickup_date'     => $data['date'],
                'scheduled_at'    => $data['date'].' 00:00:00',
                'status'          => HaulJob::STATUS_SCHEDULED,
                'route_distance_km'  => $finalRoute['distance_km'] ?? null,
                'route_duration_min' => $finalRoute['duration_min'] ?? null,
                'route_geometry'     => $finalRoute['geometry'] ?? null,
            ]);

            $truck->update(['status' => 'in_use']);

            foreach ($orders as $i => $order) {
                $stopSchedule = $schedule->get($order->id);
                $plannedArrival = $stopSchedule
                    ? $haulJob->pickup_date->copy()->startOfDay()->addMinutes((int) $stopSchedule['arrival_min'])
                    : null;

                HaulJobStop::create([
                    'haul_job_id'   => $haulJob->id,
                    'buyer_order_id' => $order->id,
                    'sequence_no'   => $i + 1,
                    'status'        => HaulJobStop::STATUS_PENDING,
                    'planned_arrival_at' => $plannedArrival,
                ]);

                Delivery::create([
                    'buyer_order_id' => $order->id,
                    'cooperative_id' => $cooperativeId,
                    'delivery_personnel_id' => $data['delivery_personnel_id'],
                    'truck_id'       => $truck->id,
                    'delivery_date'  => $data['date'],
                    'scheduled_at'   => $data['date'].' 00:00:00',
                    'status'         => Delivery::STATUS_SCHEDULED,
                ]);

                $order->update(['status' => BuyerOrder::STATUS_READY_FOR_DELIVERY]);
            }

            return $haulJob;
        });

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'create_delivery_trip',
            'target_type' => 'haul_job',
            'target_id'   => $job->id,
            'notes'       => "Delivery trip {$job->id} created on {$data['date']} with {$orders->count()} stops, truck {$truck->plate_number}.",
        ]);

        foreach ($orders as $order) {
            Notification::create([
                'user_id'  => $order->buyer_id,
                'title'    => 'Delivery scheduled',
                'message'  => "Your order {$order->reference} has been scheduled for delivery on {$data['date']}.",
                'link'     => route('buyer.orders.show', $order),
                'category' => 'buyer_order',
            ]);
        }

        Notification::create([
            'user_id'  => $data['delivery_personnel_id'],
            'title'    => 'New delivery trip assigned',
            'message'  => "Delivery trip {$job->id} is scheduled for {$data['date']} with {$orders->count()} stops.",
            'link'     => route('delivery.trips.show', $job),
            'category' => 'haul',
        ]);

        return redirect()->route('coop.outbound.show', $job)
            ->with('success', 'Delivery trip created with '.e($orders->count()).' stops.');
    }

    public function show(HaulJob $haulJob)
    {
        $this->authorizeCoop($haulJob);
        abort_unless($haulJob->isDelivery(), 404);

        $haulJob->load(['truck', 'deliveryPersonnel', 'cooperative', 'stops.buyerOrder.buyer']);

        return view('coop.outbound.show', compact('haulJob'));
    }

    private function cooperativeId(): int
    {
        $id = Auth::user()?->cooperative_id;
        if (! $id) {
            abort(403, 'You are not an active cooperative admin.');
        }

        return $id;
    }

    private function authorizeCoop(HaulJob $haulJob): void
    {
        if ($haulJob->cooperative_id !== Auth::user()?->cooperative_id) {
            abort(403, 'This trip does not belong to your cooperative.');
        }
    }
}
