<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\BuyerOrder;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                'arrived'   => ['pending', 'arrived', 'skipped'],
                'picked_up' => ['pending', 'arrived', 'picked_up'],
                'skipped'   => ['pending', 'arrived', 'skipped'],
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

        $stop->update([
            'status' => $status,
            'failure_reason' => $status === 'failed' ? $failureReason : $stop->failure_reason,
            'actual_arrival_at' => $stop->actual_arrival_at ?? (in_array($status, ['arrived', 'failed'], true) ? now() : null),
            'picked_up_at' => $status === 'picked_up' ? now() : $stop->picked_up_at,
            'delivered_at' => $status === 'delivered' ? now() : $stop->delivered_at,
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

        // When every stop is closed out, the trip is complete.
        if ($job->stops()->whereIn('status', ['pending', 'arrived'])->doesntExist()) {
            $this->completeTrip($job);
        }

        return back()->with('success', match ($status) {
            'arrived'   => 'Marked stop as arrived.',
            'picked_up' => 'Marked stop as picked up.',
            'delivered' => 'Marked stop as delivered.',
            'skipped'   => 'Stop marked as skipped.',
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

        return response()->json(['status' => 'ok']);
    }

    public function complete(Request $request, HaulJob $haulJob)
    {
        $this->authorizeDelivery($haulJob);

        if ($haulJob->stops()->whereIn('status', ['pending', 'arrived'])->exists()) {
            throw ValidationException::withMessages([
                'stop' => 'Close every stop before completing the trip.',
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
