<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\Notification;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TripController extends Controller
{
    /**
     * Delivery Personnel — pickup trip execution.
     * The driver sees the haul jobs they have been assigned to for the
     * cooperative, and marks each stop along the route as they happen.
     */
    public function index()
    {
        $userId = Auth::id();

        $jobs = HaulJob::with(['haulRequest.crop', 'haulRequest.farmer', 'truck', 'stops.haulRequest'])
            ->where('delivery_personnel_id', $userId)
            ->whereIn('status', [HaulJob::STATUS_SCHEDULED, HaulJob::STATUS_PICKED_UP])
            ->orderBy('scheduled_at')
            ->get();

        return view('delivery.trips.index', compact('jobs'));
    }

    public function show(HaulJob $haulJob)
    {
        $this->authorizeDelivery($haulJob);

        $haulJob->load(['haulRequest.crop', 'haulRequest.farmer', 'truck', 'cooperative', 'stops.haulRequest.farmer', 'stops.haulRequest.crop']);

        return view('delivery.trips.show', compact('haulJob'));
    }

    public function updateStopStatus(Request $request, HaulJobStop $stop, string $status)
    {
        $this->authorizeDelivery($stop->haulJob);

        $job = $stop->haulJob;
        if (! in_array($job->status, [HaulJob::STATUS_SCHEDULED, HaulJob::STATUS_PICKED_UP], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'stop' => 'This trip is not in a status that accepts stop updates.',
            ]);
        }

        $transitions = [
            'arrived'   => ['pending', 'arrived', 'skipped'],
            'picked_up' => ['pending', 'arrived', 'picked_up'],
            'skipped'   => ['pending', 'arrived', 'skipped'],
            'failed'    => ['pending', 'arrived'],
        ];

        if (! in_array($stop->status, $transitions[$status] ?? [], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
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
        ]);

        if ($status === 'failed') {
            $this->notifyCoopStaff(
                $job,
                'Pickup problem reported',
                "Delivery personnel reported a problem at stop {$stop->sequence_no} on trip {$job->id}: {$failureReason}. Review and update the plan as needed."
            );
        }

        // A pickup pushes the job from scheduled into "picked_up" (in progress).
        if ($status === 'picked_up' && $job->status !== HaulJob::STATUS_PICKED_UP) {
            $job->update(['status' => HaulJob::STATUS_PICKED_UP]);
        }

        // When every stop is picked up, skipped, or failed, the trip is complete.
        if ($job->stops()->whereIn('status', ['pending', 'arrived'])->doesntExist()) {
            $job->update(['status' => HaulJob::STATUS_COMPLETED, 'completed_at' => now()]);
            $job->truck?->update(['status' => 'available']);

            foreach ($job->stops as $s) {
                if (! $s->haulRequest || $s->haulRequest->status !== HaulRequest::STATUS_SCHEDULED) {
                    continue;
                }

                // Only an actual pickup completes the request. A skipped or
                // failed stop never picked up the crop, so it goes back to
                // the approved queue for the coop to replan, not "completed".
                $s->haulRequest->update([
                    'status' => $s->status === HaulJobStop::STATUS_PICKED_UP
                        ? HaulRequest::STATUS_COMPLETED
                        : HaulRequest::STATUS_APPROVED,
                ]);
            }

            // Let the field/receiving side know the trip completed so they can record receiving.
            $this->notifyCoopStaff($job, 'Trip completed', "Pickup trip {$job->id} is complete. Record receiving for each stop in your procurement queue.");
        }

        return back()->with('success', match ($status) {
            'arrived'   => "Marked stop as arrived.",
            'picked_up' => "Marked stop as picked up.",
            'skipped'   => "Stop marked as skipped.",
            'failed'    => "Problem reported. Your cooperative was notified.",
        });
    }

    public function complete(Request $request, HaulJob $haulJob)
    {
        $this->authorizeDelivery($haulJob);

        if ($haulJob->stops()->whereIn('status', ['pending', 'arrived'])->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'stop' => 'Close every stop before completing the trip.',
            ]);
        }

        $haulJob->update(['status' => HaulJob::STATUS_COMPLETED, 'completed_at' => now()]);
        $haulJob->truck?->update(['status' => 'available']);

        foreach ($haulJob->stops as $stop) {
            if (! $stop->haulRequest || ! in_array($stop->haulRequest->status, ['pending', HaulRequest::STATUS_SCHEDULED], true)) {
                continue;
            }

            $stop->haulRequest->update([
                'status' => $stop->status === HaulJobStop::STATUS_PICKED_UP
                    ? HaulRequest::STATUS_COMPLETED
                    : HaulRequest::STATUS_APPROVED,
            ]);
        }

        $this->notifyCoopStaff($haulJob, 'Trip marked complete', "Pickup trip {$haulJob->id} is finished. Record the received weight, grade, and buying price for each stop.");

        return redirect()->route('delivery.trips.index')
            ->with('success', 'Pickup trip completed.')
            ->with('next_steps', [
                'title'   => 'Trip completed',
                'message' => 'Mark the next stop as picked up on your next run when the cooperative schedules it.',
                'steps'   => ['Return to the trips list for your next pickup.'],
                'cta'     => ['label' => 'View my pickup trips', 'url' => route('delivery.trips.index')],
            ]);
    }

    private function authorizeDelivery(HaulJob $haulJob): void
    {
        if ($haulJob->delivery_personnel_id !== Auth::id()) {
            abort(403, 'This trip is not assigned to you.');
        }
    }

    private function notifyCoopStaff(HaulJob $haulJob, string $title, string $message): void
    {
        $admins = \App\Models\User::where('role', \App\Models\UserRole::COOP_ADMIN->value)
            ->where('cooperative_id', $haulJob->cooperative_id)->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title'   => $title,
                'message' => $message,
                'link'    => route('coop.procurement.index'),
                'category' => 'haul',
            ]);
        }
    }
}
