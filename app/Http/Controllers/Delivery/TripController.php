<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BuyerOrder;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\Notification;
use App\Services\Routing\HaversineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TripController extends Controller
{
    /**
     * Delivery Personnel — trip execution for both inbound pickup and
     * outbound delivery trips (Module 8 — the same HaulJob/HaulJobStop
     * system, distinguished by job_type). The driver sees the haul jobs
     * they have been assigned to, and marks each stop along the route.
     */
    public function index()
    {
        $userId = Auth::id();

        $jobs = HaulJob::with(['haulRequest.crop', 'haulRequest.farmer', 'truck', 'stops.haulRequest', 'stops.buyerOrder.buyer'])
            ->where('delivery_personnel_id', $userId)
            ->whereIn('status', [HaulJob::STATUS_SCHEDULED, HaulJob::STATUS_PICKED_UP])
            ->orderBy('scheduled_at')
            ->get();

        return view('delivery.trips.index', compact('jobs'));
    }

    public function show(HaulJob $haulJob)
    {
        $this->authorizeDelivery($haulJob);

        $haulJob->load([
            'haulRequest.crop', 'haulRequest.farmer', 'truck', 'cooperative',
            'stops.haulRequest.farmer', 'stops.haulRequest.crop',
            'stops.buyerOrder.buyer', 'stops.buyerOrder.items.crop',
        ]);

        return view('delivery.trips.show', compact('haulJob'));
    }

    public function updateStopStatus(Request $request, HaulJobStop $stop, string $status)
    {
        $this->authorizeDelivery($stop->haulJob);

        $job = $stop->haulJob;
        if (! in_array($job->status, [HaulJob::STATUS_SCHEDULED, HaulJob::STATUS_PICKED_UP], true)) {
            throw ValidationException::withMessages([
                'stop' => 'This trip is not in a status that accepts stop updates.',
            ]);
        }

        $isDelivery = $stop->isDeliveryStop();

        $transitions = $isDelivery
            ? [
                'arrived'   => ['pending', 'arrived'],
                'delivered' => ['pending', 'arrived', 'delivered'],
                'failed'    => ['pending', 'arrived'],
            ]
            : [
                // No 'skipped' transition — a pickup stop must be arrived,
                // picked up, or reported as a problem (with a reason).
                'arrived'   => ['pending', 'arrived'],
                'picked_up' => ['pending', 'arrived', 'picked_up'],
                'failed'    => ['pending', 'arrived'],
            ];

        if (! in_array($stop->status, $transitions[$status] ?? [], true)) {
            throw ValidationException::withMessages([
                'stop' => 'This stop cannot move from '.$stop->status.' to '.$status.' at this point in the trip.',
            ]);
        }

        $failureReason = null;
        if ($status === 'failed') {
            $failureReason = $request->validate([
                'reason' => 'required|string|max:500',
            ])['reason'];
        }

        // Proof of delivery: a photo is required to close out the terminal
        // success state of a stop (panel spec: "force driver to capture").
        // Not required on 'arrived' or 'failed' — only the states that
        // actually hand off crop/order to someone.
        $podPhotoPath = $stop->pod_photo_path;
        if (in_array($status, ['picked_up', 'delivered'], true)) {
            $photo = $request->validate([
                'photo' => 'required|image|max:5120',
            ])['photo'];
            $podPhotoPath = Storage::disk('local')->putFile('pod-photos', $photo);
        }

        $stop->update([
            'status' => $status,
            'failure_reason' => $status === 'failed' ? $failureReason : $stop->failure_reason,
            'actual_arrival_at' => $stop->actual_arrival_at ?? (in_array($status, ['arrived', 'failed'], true) ? now() : null),
            'picked_up_at' => $status === 'picked_up' ? now() : $stop->picked_up_at,
            'delivered_at' => $status === 'delivered' ? now() : $stop->delivered_at,
            'pod_photo_path' => $podPhotoPath,
        ]);

        if ($status === 'delivered') {
            $order = $stop->buyerOrder;
            if ($order) {
                $order->update(['status' => BuyerOrder::STATUS_DELIVERED]);
                $order->delivery?->update(['status' => \App\Models\Delivery::STATUS_DELIVERED, 'completed_at' => now()]);
                Notification::create([
                    'user_id'  => $order->buyer_id,
                    'title'    => 'Order delivered',
                    'message'  => "Your order {$order->reference} was delivered.",
                    'link'     => route('buyer.orders.show', $order),
                    'category' => 'buyer_order',
                ]);
            }
        }

        if (! $isDelivery && in_array($status, ['arrived', 'picked_up'], true) && $stop->haulRequest?->farmer_id) {
            Notification::create([
                'user_id'  => $stop->haulRequest->farmer_id,
                'title'    => $status === 'arrived' ? 'Truck has arrived' : 'Crop picked up',
                'message'  => $status === 'arrived'
                    ? 'The truck has arrived to collect your '.($stop->haulRequest->crop?->name ?? 'crop').'.'
                    : 'Your '.($stop->haulRequest->crop?->name ?? 'crop').' has been picked up.',
                'link'     => route('farmer.haul-requests.track', $stop->haulRequest),
                'category' => 'haul',
            ]);
        }

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'update_stop_status',
            'target_type' => 'haul_job_stop',
            'target_id'   => $stop->id,
            'notes'       => "Stop {$stop->sequence_no} on trip {$job->id} marked {$status}.".($status === 'failed' ? " Reason: {$failureReason}" : ''),
        ]);

        if ($status === 'failed') {
            $this->notifyCoopStaff(
                $job,
                $isDelivery ? 'Delivery problem reported' : 'Pickup problem reported',
                'Delivery personnel reported a problem at stop '.$stop->sequence_no." on trip {$job->id}: {$failureReason}. Review and update the plan as needed."
            );
        }

        // A pickup/delivery in progress pushes the job from scheduled into
        // "picked_up" (in progress) — shared status regardless of job_type.
        if (in_array($status, ['picked_up', 'delivered'], true) && $job->status !== HaulJob::STATUS_PICKED_UP) {
            $job->update(['status' => HaulJob::STATUS_PICKED_UP]);
        }

        // When every stop is closed out, the trip is complete — except a
        // pickup trip that actually collected crop, which still has one
        // shared final stop left: getting it to the co-op. That's a
        // separate photo-gated action (complete()), not automatic (panel
        // spec: "same destination"). A pickup trip where nothing was ever
        // picked up (every stop failed) has nothing to deliver, so it
        // still auto-completes exactly like before.
        if ($job->stops()->whereIn('status', ['pending', 'arrived'])->doesntExist()
            && ! $this->awaitingDepotDelivery($job)) {
            $this->completeTrip($job);
        }

        return back()->with('success', match ($status) {
            'arrived'   => 'Marked stop as arrived.',
            'picked_up' => 'Marked stop as picked up.',
            'delivered' => 'Marked stop as delivered.',
            'failed'    => 'Problem reported. Your cooperative was notified.',
        });
    }

    public function postLocation(Request $request, HaulJob $haulJob)
    {
        $this->authorizeDelivery($haulJob);

        if (! $haulJob->isActiveForTracking()) {
            throw ValidationException::withMessages([
                'location' => 'This trip is not active — location updates are not accepted.',
            ]);
        }

        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed_kmh' => 'nullable|numeric|min:0',
            'bearing' => 'nullable|numeric|between:0,360',
            'accuracy_meters' => 'nullable|numeric|min:0',
        ]);

        $haulJob->tracking()->create(array_merge($data, [
            'driver_id' => Auth::id(),
            'posted_at' => now(),
        ]));

        $this->notifyFarmerIfNear($haulJob, (float) $data['latitude'], (float) $data['longitude']);

        return response()->json(['status' => 'ok']);
    }

    /**
     * One-time "truck is near" alert to the farmer at the next open pickup
     * stop, keyed to that stop's own proximity_notified_at so each farmer
     * gets their own alert as their turn on the route comes up.
     */
    private function notifyFarmerIfNear(HaulJob $haulJob, float $lat, float $lng): void
    {
        $stop = $haulJob->stops()
            ->where('status', HaulJobStop::STATUS_PENDING)
            ->whereNull('buyer_order_id')
            ->whereNull('proximity_notified_at')
            ->orderBy('sequence_no')
            ->first();

        if (! $stop || ! $stop->haulRequest?->pickup_location_lat || ! $stop->haulRequest?->pickup_location_lng) {
            return;
        }

        $distanceKm = app(HaversineService::class)->distanceKm(
            $lat, $lng,
            (float) $stop->haulRequest->pickup_location_lat,
            (float) $stop->haulRequest->pickup_location_lng,
        );

        if ($distanceKm > (float) config('harvesthaul.logistics.proximity_radius_km')) {
            return;
        }

        $stop->update(['proximity_notified_at' => now()]);

        if ($stop->haulRequest->farmer_id) {
            Notification::create([
                'user_id'  => $stop->haulRequest->farmer_id,
                'title'    => 'Truck is nearby',
                'message'  => "Your cooperative's truck is getting close — have your ".($stop->haulRequest->crop?->name ?? 'crop').' ready.',
                'link'     => route('farmer.haul-requests.track', $stop->haulRequest),
                'category' => 'haul',
            ]);
        }
    }

    /**
     * A pickup trip that actually collected crop still needs one shared
     * "delivered to the co-op" photo before it can complete. A pickup trip
     * where every stop failed collected nothing, so there's nothing to
     * deliver — it completes the normal way. Delivery trips never wait
     * here; each of their stops already has its own real destination.
     */
    private function awaitingDepotDelivery(HaulJob $job): bool
    {
        return $job->job_type === HaulJob::JOB_TYPE_PICKUP
            && $job->stops()->where('status', HaulJobStop::STATUS_PICKED_UP)->exists();
    }

    public function complete(Request $request, HaulJob $haulJob)
    {
        $this->authorizeDelivery($haulJob);

        if ($haulJob->stops()->whereIn('status', ['pending', 'arrived'])->exists()) {
            throw ValidationException::withMessages([
                'stop' => 'Close every stop before completing the trip.',
            ]);
        }

        if ($this->awaitingDepotDelivery($haulJob)) {
            // All the farmers' crop shares one destination — the co-op —
            // so the trip needs one final photo proving it actually got
            // there, same "force driver to capture" rule as each stop.
            $photo = $request->validate([
                'photo' => 'required|image|max:5120',
            ])['photo'];

            $haulJob->update([
                'depot_delivered_at'     => now(),
                'depot_pod_photo_path'   => Storage::disk('local')->putFile('pod-photos', $photo),
            ]);
        }

        $this->completeTrip($haulJob);

        return redirect()->route('delivery.trips.index')
            ->with('success', 'Trip completed.')
            ->with('next_steps', [
                'title'   => 'Trip completed',
                'message' => 'Return to the trips list for your next assignment.',
                'steps'   => ['Return to the trips list for your next trip.'],
                'cta'     => ['label' => 'View my trips', 'url' => route('delivery.trips.index')],
            ]);
    }

    private function completeTrip(HaulJob $job): void
    {
        if ($job->status === HaulJob::STATUS_COMPLETED) {
            return;
        }

        $job->update(['status' => HaulJob::STATUS_COMPLETED, 'completed_at' => now()]);
        $job->truck?->update(['status' => 'available']);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'complete_haul_job',
            'target_type' => 'haul_job',
            'target_id'   => $job->id,
            'notes'       => ($job->isDelivery() ? 'Delivery' : 'Pickup')." trip {$job->id} completed.",
        ]);

        foreach ($job->stops as $s) {
            if ($s->isDeliveryStop()) {
                $order = $s->buyerOrder;
                if ($order && $order->status === BuyerOrder::STATUS_READY_FOR_DELIVERY) {
                    // Never delivered/failed cleanly — revert to accepted so
                    // the cooperative can re-plan it, mirroring how a pickup
                    // stop that never picked up reverts its haul request.
                    $order->update(['status' => BuyerOrder::STATUS_ACCEPTED]);
                }

                continue;
            }

            if (! $s->haulRequest || $s->haulRequest->status !== HaulRequest::STATUS_SCHEDULED) {
                continue;
            }

            $s->haulRequest->update([
                'status' => $s->status === HaulJobStop::STATUS_PICKED_UP
                    ? HaulRequest::STATUS_COMPLETED
                    : HaulRequest::STATUS_APPROVED,
            ]);
        }

        $this->notifyCoopStaff(
            $job,
            'Trip completed',
            $job->isDelivery()
                ? "Delivery trip {$job->id} is complete."
                : "Pickup trip {$job->id} is complete. Record receiving for each stop in your procurement queue.",
            $job->isDelivery() ? route('coop.outbound.show', $job) : route('coop.procurement.index')
        );
    }

    private function authorizeDelivery(HaulJob $haulJob): void
    {
        if ($haulJob->delivery_personnel_id !== Auth::id()) {
            abort(403, 'This trip is not assigned to you.');
        }
    }

    private function notifyCoopStaff(HaulJob $haulJob, string $title, string $message, ?string $link = null): void
    {
        $admins = \App\Models\User::where('role', \App\Models\UserRole::COOP_ADMIN->value)
            ->where('cooperative_id', $haulJob->cooperative_id)->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title'   => $title,
                'message' => $message,
                'link'    => $link ?? route('coop.procurement.index'),
                'category' => 'haul',
            ]);
        }
    }
}
