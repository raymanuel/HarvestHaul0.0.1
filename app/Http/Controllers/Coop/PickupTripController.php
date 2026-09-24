<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\Notification;
use App\Models\Truck;
use App\Services\Cooperative\ConsolidationEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PickupTripController extends Controller
{
    /**
     * Coop Admin — pickup planning and dispatch.
     * Consolidate the date's pending pickup requests into one HaulJob
     * with ordered HaulJobStops, assign a truck + driver, and flip the
     * selected requests to "scheduled".
     */
    public function index()
    {
        $cooperativeId = $this->cooperativeId();

        $trips = HaulJob::with(['haulRequest.crop', 'truck', 'deliveryPersonnel'])
            ->where('cooperative_id', $cooperativeId)
            ->orderByDesc('scheduled_at')
            ->get();

        return view('coop.pickups.index', compact('trips'));
    }

    /**
     * Scheduling Calendar (5.3) — day/week/list views over upcoming demand:
     * approved requests waiting to be planned, and trips already scheduled.
     * Each date links into the single-date planner (create()).
     */
    public function calendar(Request $request)
    {
        $cooperativeId = $this->cooperativeId();

        $view = in_array($request->query('view'), ['day', 'week', 'list'], true) ? $request->query('view') : 'list';
        $anchor = $request->query('date') ? \Carbon\Carbon::parse($request->query('date')) : today();

        [$rangeStart, $rangeEnd] = match ($view) {
            'day'  => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            'week' => [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()],
            default => [today(), today()->addDays(30)],
        };

        $requests = HaulRequest::where('cooperative_id', $cooperativeId)
            ->where('status', HaulRequest::STATUS_APPROVED)
            ->whereBetween('preferred_pickup_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->with(['farmer', 'crop'])
            ->orderBy('preferred_pickup_date')
            ->get()
            ->groupBy(fn ($r) => $r->preferred_pickup_date->toDateString());

        $trips = HaulJob::where('cooperative_id', $cooperativeId)
            ->whereBetween('pickup_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->with(['truck', 'deliveryPersonnel'])
            ->orderBy('pickup_date')
            ->get()
            ->groupBy(fn ($j) => $j->pickup_date->toDateString());

        return view('coop.pickups.calendar', compact('view', 'anchor', 'rangeStart', 'rangeEnd', 'requests', 'trips'));
    }

    public function create(Request $request, ConsolidationEngine $engine)
    {
        $cooperativeId = $this->cooperativeId();
        $date = $request->query('date', today()->toDateString());

        $cooperative = Auth::user()->cooperative;
        $plan = $engine->planForDate($cooperative, $date);

        return view('coop.pickups.create', compact('plan', 'date', 'cooperative'));
    }

    public function store(Request $request, ConsolidationEngine $engine)
    {
        $cooperativeId = $this->cooperativeId();
        $cooperative = Auth::user()->cooperative;

        $requestIds = array_values(array_filter((array) ($request->input('requests') ?? [])));

        if (empty($requestIds)) {
            throw ValidationException::withMessages(['requests' => 'Select at least one pickup request for the trip.']);
        }

        // Only this cooperative's own approved requests on the chosen date.
        $requests = HaulRequest::whereIn('id', $requestIds)
            ->where('cooperative_id', $cooperativeId)
            ->where('status', HaulRequest::STATUS_APPROVED)
            ->whereDate('preferred_pickup_date', $request->input('date'))
            ->get();

        if ($requests->count() !== count($requestIds)) {
            throw ValidationException::withMessages([
                'requests' => 'One or more selected requests are not available to schedule for this cooperative.',
            ]);
        }

        $availableDriverIds = $engine->availableDrivers($cooperative, $request->input('date'))->pluck('id');
        $availableFieldStaffIds = $engine->availableFieldPersonnel($cooperative)->pluck('id');

        $data = $request->validate([
            'date'                => 'required|date|after_or_equal:today',
            'truck_id'            => [
                'required',
                Rule::exists('trucks', 'id')
                    ->where('cooperative_id', $cooperativeId)
                    ->where('status', 'available'),
            ],
            'delivery_personnel_id' => [
                'required',
                Rule::in($availableDriverIds),
            ],
            'field_personnel_id' => [
                'nullable',
                Rule::in($availableFieldStaffIds),
            ],
            'sequences'           => 'array',
        ], [
            'truck_id.exists' => 'This truck is not available (already in use, in maintenance, or inactive).',
            'delivery_personnel_id.in' => 'This driver is already assigned to another trip on this date.',
            'field_personnel_id.in' => 'This field staff member is already assigned to another trip.',
        ]);

        $truck = Truck::where('id', $data['truck_id'])->where('cooperative_id', $cooperativeId)->firstOrFail();

        if (! empty($data['sequences'])) {
            // Re-order the selected requests by the coop's chosen sequence numbers.
            $byId = $requests->keyBy('id');
            $ordered = [];
            foreach ($data['sequences'] as $seq => $reqId) {
                if (isset($byId[$reqId])) {
                    $ordered[$seq] = $byId[$reqId];
                }
            }
            if (count($ordered) === $requests->count()) {
                $requests = collect(array_values($ordered));
                // Re-key sequence slots to 1..N for the stops.
                $sequences = array_keys($data['sequences']);
                sort($sequences);
                $seqMap = array_combine($sequences, range(1, count($sequences)));
            } else {
                $seqMap = null;
            }
        } else {
            $seqMap = null;
        }

        // Capacity advisory check (estimated weight vs truck capacity).
        $totalWeight = $requests->sum('estimated_weight_kg');
        $overCapacity = $totalWeight > ((float) $truck->capacity_kg);

        if ($overCapacity && ! $request->boolean('force')) {
            throw ValidationException::withMessages([
                'truck_id' => 'Estimated load ('.number_format($totalWeight, 2).' kg) exceeds the truck capacity ('.number_format($truck->capacity_kg, 2).' kg). This is advisory, but review it before confirming. Confirm to proceed anyway or choose a larger truck.',
            ]);
        }

        // Stop order is now accepted (spec 7.10) — final route geometry + arrival
        // schedule computed outside the DB transaction (both are network calls).
        $finalRoute = $engine->fetchFinalRoute($requests, $cooperative);
        $schedule = collect($engine->buildScheduleForRequests($requests, $cooperative))->keyBy('request_id');

        $job = DB::transaction(function () use ($cooperativeId, $data, $truck, $requests, $seqMap, $totalWeight, $finalRoute, $schedule) {
            $haulJob = HaulJob::create([
                'haul_request_id' => null,
                'cooperative_id'  => $cooperativeId,
                'delivery_personnel_id' => $data['delivery_personnel_id'],
                'field_personnel_id' => $data['field_personnel_id'] ?? null,
                'truck_id'        => $truck->id,
                'pickup_date'     => $data['date'],
                'scheduled_at'    => $data['date'].' 00:00:00',
                'status'          => HaulJob::STATUS_SCHEDULED,
                'route_distance_km'  => $finalRoute['distance_km'] ?? null,
                'route_duration_min' => $finalRoute['duration_min'] ?? null,
                'route_geometry'     => $finalRoute['geometry'] ?? null,
            ]);

            // Capacity advisory flag stored so the UI can flag it.
            if ($totalWeight > (float) $truck->capacity_kg) {
                $haulJob->update(['status' => HaulJob::STATUS_SCHEDULED]);
            }

            $truck->update(['status' => 'in_use']);

            foreach ($requests as $i => $hr) {
                $seq = $seqMap ? $seqMap[($i + 1)] : ($i + 1);
                $stopSchedule = $schedule->get($hr->id);
                $plannedArrival = $stopSchedule
                    ? $haulJob->pickup_date->copy()->startOfDay()->addMinutes((int) $stopSchedule['arrival_min'])
                    : null;

                HaulJobStop::create([
                    'haul_job_id'    => $haulJob->id,
                    'haul_request_id'=> $hr->id,
                    'sequence_no'    => $seq,
                    'status'         => HaulJobStop::STATUS_PENDING,
                    'planned_arrival_at' => $plannedArrival,
                ]);
                $hr->update(['status' => HaulRequest::STATUS_SCHEDULED]);
            }

            return $haulJob;
        });

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'create_pickup_trip',
            'target_type' => 'haul_job',
            'target_id'   => $job->id,
            'notes'       => "Trip {$job->id} created on {$data['date']} with {$requests->count()} stops, truck {$truck->plate_number}.",
        ]);

        // Notify each farmer their request is now scheduled.
        foreach ($requests as $hr) {
            Notification::create([
                'user_id'  => $hr->farmer_id,
                'title'    => 'Pickup scheduled',
                'message'  => "Your pickup for ~{$hr->estimated_weight_kg} kg has been scheduled for {$data['date']}. Track your pickup trip from your farmer dashboard.",
                'link'     => route('farmer.dashboard'),
                'category' => 'haul',
            ]);
        }

        // Notify the driver.
        Notification::create([
            'user_id'  => $data['delivery_personnel_id'],
            'title'    => 'New pickup trip assigned',
            'message'  => "Trip {$job->id} is scheduled for {$data['date']} with {$requests->count()} stops. See your pickup trips to start the run.",
            'link'     => route('delivery.trips.show', $job),
            'category' => 'haul',
        ]);

        return redirect()->route('coop.pickups.show', $job)
            ->with('success', 'Pickup trip created with '.e($requests->count()).' stops.')
            ->with('next_steps', [
                'title'   => 'Pickup trip created',
                'message' => "{$requests->count()} farmers are scheduled into trip {$job->id}. The truck is now in use.",
                'steps'   => [
                    'Track each stop\'s status from your pickup planning view.',
                    'The driver marks arrived/picked-up per stop on their trip screen.',
                    'Once a stop is picked up, your field team records the receiving there.',
                ],
                'cta' => ['label' => 'Open this pickup trip', 'url' => route('coop.pickups.show', $job)],
            ]);
    }

    /**
     * Live preview (planner page, no trip created yet) — recomputes load,
     * capacity flag, road order, and schedule for whatever the coop admin
     * currently has checked + selected, so the page can update without a
     * full reload as they click. Truck is optional here (store() requires
     * it) so the weight total still shows before a truck is picked.
     */
    public function preview(Request $request, ConsolidationEngine $engine)
    {
        $cooperativeId = $this->cooperativeId();
        $cooperative = Auth::user()->cooperative;

        $requestIds = array_values(array_filter((array) ($request->input('requests') ?? [])));
        $date = (string) $request->input('date');

        $data = $request->validate([
            'date' => 'required|date',
            'truck_id' => [
                'nullable',
                Rule::exists('trucks', 'id')->where('cooperative_id', $cooperativeId),
            ],
        ]);

        $requests = HaulRequest::whereIn('id', $requestIds)
            ->where('cooperative_id', $cooperativeId)
            ->where('status', HaulRequest::STATUS_APPROVED)
            ->whereDate('preferred_pickup_date', $date)
            ->get();

        if ($requests->count() !== count($requestIds)) {
            throw ValidationException::withMessages([
                'requests' => 'One or more selected requests are not available to schedule for this cooperative.',
            ]);
        }

        $truck = ! empty($data['truck_id'])
            ? Truck::where('id', $data['truck_id'])->where('cooperative_id', $cooperativeId)->first()
            : null;

        return response()->json($engine->previewGroup($cooperative, $requests, $truck, $date));
    }

    public function show(HaulJob $haulJob, ConsolidationEngine $engine)
    {
        $this->authorizeCoop($haulJob);
        $haulJob->load(['haulRequest.crop', 'truck', 'deliveryPersonnel', 'cooperative', 'stops.haulRequest.farmer']);

        $stops = $haulJob->stops()->orderBy('sequence_no')->get();

        // Sort reassignment candidates by distance from the trip's first
        // stop when we actually have a coordinate for it — an honest
        // "nearest available driver," not a fabricated one.
        $firstStop = $stops->first()?->haulRequest;
        $nearTo = $firstStop && $firstStop->pickup_location_lat && $firstStop->pickup_location_lng
            ? ['lat' => (float) $firstStop->pickup_location_lat, 'lng' => (float) $firstStop->pickup_location_lng]
            : null;

        $trucks = Truck::where('cooperative_id', $haulJob->cooperative_id)->where('status', 'available')->orderBy('truck_name')->get();
        if ($haulJob->truck && ! $trucks->contains('id', $haulJob->truck_id)) {
            // Same "keep the incumbent selectable" rule as the driver list
            // below — the trip's own truck stays in the dropdown even
            // though it's the reason it isn't 'available' right now.
            $trucks = $trucks->push($haulJob->truck)->sortBy('truck_name')->values();
        }
        $drivers = $engine->availableDrivers($haulJob->cooperative, $haulJob->pickup_date->toDateString(), $haulJob->id, $nearTo);
        if ($haulJob->deliveryPersonnel && ! $drivers->contains('id', $haulJob->delivery_personnel_id)) {
            // Already-assigned driver isn't in the "available" list (fully
            // booked elsewhere, say) — append them rather than re-sorting
            // alphabetically, which would undo the distance order above.
            $drivers = $drivers->push($haulJob->deliveryPersonnel)->values();
        }

        return view('coop.pickups.show', compact('haulJob', 'stops', 'trucks', 'drivers'));
    }

    /**
     * Reschedule (5.5) — move a trip to a different date, before it's picked up.
     */
    public function reschedule(Request $request, HaulJob $haulJob)
    {
        $this->authorizeCoop($haulJob);

        if ($haulJob->status !== HaulJob::STATUS_SCHEDULED) {
            throw ValidationException::withMessages([
                'haul_job' => 'Only trips that are still scheduled (not yet picked up) can be rescheduled.',
            ]);
        }

        $data = $request->validate([
            'date' => 'required|date|after_or_equal:today',
        ]);

        $haulJob->update([
            'pickup_date'  => $data['date'],
            'scheduled_at' => $data['date'].' 00:00:00',
        ]);

        HaulRequest::whereIn('id', $haulJob->stops()->pluck('haul_request_id'))
            ->update(['preferred_pickup_date' => $data['date']]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'reschedule_pickup_trip',
            'target_type' => 'haul_job',
            'target_id'   => $haulJob->id,
            'notes'       => "Trip {$haulJob->id} rescheduled to {$data['date']}.",
        ]);

        foreach ($haulJob->stops as $stop) {
            if ($stop->haulRequest) {
                Notification::create([
                    'user_id'  => $stop->haulRequest->farmer_id,
                    'title'    => 'Pickup rescheduled',
                    'message'  => "Your pickup has been moved to {$data['date']}.",
                    'link'     => route('farmer.dashboard'),
                    'category' => 'haul',
                ]);
            }
        }

        if ($haulJob->delivery_personnel_id) {
            Notification::create([
                'user_id'  => $haulJob->delivery_personnel_id,
                'title'    => 'Trip rescheduled',
                'message'  => "Trip {$haulJob->id} was moved to {$data['date']}.",
                'link'     => route('delivery.trips.show', $haulJob),
                'category' => 'haul',
            ]);
        }

        return redirect()->route('coop.pickups.show', $haulJob)->with('success', 'Trip rescheduled.');
    }

    /**
     * Reassign truck/personnel (5.5) — same scoped validation as store().
     */
    public function reassign(Request $request, HaulJob $haulJob, ConsolidationEngine $engine)
    {
        $this->authorizeCoop($haulJob);
        $cooperativeId = $haulJob->cooperative_id;

        if ($haulJob->status !== HaulJob::STATUS_SCHEDULED) {
            throw ValidationException::withMessages([
                'haul_job' => 'Only trips that are still scheduled (not yet picked up) can be reassigned.',
            ]);
        }

        $availableDriverIds = $engine->availableDrivers($haulJob->cooperative, $haulJob->pickup_date->toDateString(), $haulJob->id)
            ->pluck('id')
            ->push($haulJob->delivery_personnel_id)
            ->unique();

        $availableTruckIds = Truck::where('cooperative_id', $cooperativeId)
            ->where('status', 'available')
            ->pluck('id')
            ->push($haulJob->truck_id)
            ->unique();

        $data = $request->validate([
            'truck_id' => ['required', Rule::in($availableTruckIds)],
            'delivery_personnel_id' => [
                'required',
                Rule::in($availableDriverIds),
            ],
        ], [
            'truck_id.in' => 'This truck is not available (already in use, in maintenance, or inactive).',
            'delivery_personnel_id.in' => 'This driver is already assigned to another trip on this date.',
        ]);

        $previousDriverId = $haulJob->delivery_personnel_id;
        $previousTruckId = $haulJob->truck_id;

        $haulJob->update([
            'truck_id'              => $data['truck_id'],
            'delivery_personnel_id' => $data['delivery_personnel_id'],
        ]);

        if ($previousTruckId !== (int) $data['truck_id']) {
            Truck::where('id', $previousTruckId)->update(['status' => 'available']);
            Truck::where('id', $data['truck_id'])->update(['status' => 'in_use']);
        }

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'reassign_pickup_trip',
            'target_type' => 'haul_job',
            'target_id'   => $haulJob->id,
            'notes'       => "Trip {$haulJob->id} reassigned to truck {$data['truck_id']}, driver {$data['delivery_personnel_id']}.",
        ]);

        if ($previousDriverId && $previousDriverId !== (int) $data['delivery_personnel_id']) {
            Notification::create([
                'user_id'  => $previousDriverId,
                'title'    => 'Trip reassigned',
                'message'  => "Trip {$haulJob->id} was reassigned to another driver.",
                'link'     => route('delivery.trips.index'),
                'category' => 'haul',
            ]);
        }

        Notification::create([
            'user_id'  => $data['delivery_personnel_id'],
            'title'    => 'Trip assigned to you',
            'message'  => "Trip {$haulJob->id} was assigned to you.",
            'link'     => route('delivery.trips.show', $haulJob),
            'category' => 'haul',
        ]);

        return redirect()->route('coop.pickups.show', $haulJob)->with('success', 'Trip reassigned.');
    }

    /**
     * Remove from planned trip (5.5/9.5) — drop one stop; the underlying
     * request goes back to `approved` so it re-enters the planner queue.
     * The remaining stops' route/schedule are recalculated (spec 9.5:
     * "Remove stop → Recalculate route → Validate time windows → Update
     * trip"). If no stops remain, the trip itself is cancelled.
     */
    public function removeStop(HaulJobStop $stop, ConsolidationEngine $engine)
    {
        $haulJob = $stop->haulJob;
        $this->authorizeCoop($haulJob);

        if (in_array($haulJob->status, [HaulJob::STATUS_COMPLETED, HaulJob::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages([
                'stop' => 'Cannot remove a stop from a trip that is already completed or cancelled.',
            ]);
        }

        $haulRequest = $stop->haulRequest;

        $remainingRequests = HaulRequest::whereIn('id', $haulJob->stops()->where('id', '!=', $stop->id)->pluck('haul_request_id'))
            ->get();

        $finalRoute = $remainingRequests->isNotEmpty() ? $engine->fetchFinalRoute($remainingRequests, $haulJob->cooperative) : null;
        $schedule = $remainingRequests->isNotEmpty()
            ? collect($engine->buildScheduleForRequests($remainingRequests, $haulJob->cooperative))->keyBy('request_id')
            : collect();

        DB::transaction(function () use ($stop, $haulRequest, $haulJob, $remainingRequests, $finalRoute, $schedule) {
            $stop->delete();

            if ($haulRequest) {
                $haulRequest->update(['status' => HaulRequest::STATUS_APPROVED]);
            }

            if ($remainingRequests->isEmpty()) {
                $haulJob->update(['status' => HaulJob::STATUS_CANCELLED]);
                $haulJob->truck?->update(['status' => 'available']);

                return;
            }

            $haulJob->update([
                'route_distance_km'  => $finalRoute['distance_km'] ?? null,
                'route_duration_min' => $finalRoute['duration_min'] ?? null,
                'route_geometry'     => $finalRoute['geometry'] ?? null,
            ]);

            foreach ($haulJob->stops()->get() as $remainingStop) {
                $stopSchedule = $schedule->get($remainingStop->haul_request_id);
                $remainingStop->update([
                    'planned_arrival_at' => $stopSchedule
                        ? $haulJob->pickup_date->copy()->startOfDay()->addMinutes((int) $stopSchedule['arrival_min'])
                        : null,
                ]);
            }
        });

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'remove_pickup_stop',
            'target_type' => 'haul_job',
            'target_id'   => $haulJob->id,
            'notes'       => $remainingRequests->isEmpty()
                ? "Stop for request {$stop->haul_request_id} removed from trip {$haulJob->id}. No stops remained, so the trip was cancelled."
                : "Stop for request {$stop->haul_request_id} removed from trip {$haulJob->id}. Route recalculated for {$remainingRequests->count()} remaining stop(s).",
        ]);

        if ($haulRequest) {
            Notification::create([
                'user_id'  => $haulRequest->farmer_id,
                'title'    => 'Pickup removed from trip',
                'message'  => 'Your pickup was removed from its scheduled trip and is back in the queue to be rescheduled.',
                'link'     => route('farmer.dashboard'),
                'category' => 'haul',
            ]);
        }

        if ($remainingRequests->isEmpty() && $haulJob->delivery_personnel_id) {
            Notification::create([
                'user_id'  => $haulJob->delivery_personnel_id,
                'title'    => 'Trip cancelled',
                'message'  => "Trip {$haulJob->id} was cancelled because its last stop was removed.",
                'link'     => route('delivery.trips.index'),
                'category' => 'haul',
            ]);

            return redirect()->route('coop.pickups.index')->with('success', 'Stop removed. The trip had no stops left, so it was cancelled.');
        }

        return redirect()->route('coop.pickups.show', $haulJob)->with('success', 'Stop removed from the trip. Route and schedule updated.');
    }

    /**
     * Cancel the whole trip (9.5) — every non-terminal stop's request goes
     * back to `approved` so it can be replanned; the truck is freed.
     */
    public function cancel(HaulJob $haulJob)
    {
        $this->authorizeCoop($haulJob);

        if ($haulJob->status !== HaulJob::STATUS_SCHEDULED) {
            throw ValidationException::withMessages([
                'haul_job' => 'Only trips that are still scheduled (not yet picked up) can be cancelled.',
            ]);
        }

        $haulJob->load('stops.haulRequest');

        DB::transaction(function () use ($haulJob) {
            foreach ($haulJob->stops as $stop) {
                if ($stop->haulRequest) {
                    $stop->haulRequest->update(['status' => HaulRequest::STATUS_APPROVED]);
                }
            }

            $haulJob->update(['status' => HaulJob::STATUS_CANCELLED]);
            $haulJob->truck?->update(['status' => 'available']);
        });

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'cancel_pickup_trip',
            'target_type' => 'haul_job',
            'target_id'   => $haulJob->id,
            'notes'       => "Trip {$haulJob->id} cancelled. {$haulJob->stops->count()} stop(s) returned to the approved queue.",
        ]);

        foreach ($haulJob->stops as $stop) {
            if ($stop->haulRequest) {
                Notification::create([
                    'user_id'  => $stop->haulRequest->farmer_id,
                    'title'    => 'Pickup trip cancelled',
                    'message'  => 'Your cooperative cancelled the pickup trip. Your request is back in the queue to be rescheduled.',
                    'link'     => route('farmer.dashboard'),
                    'category' => 'haul',
                ]);
            }
        }

        if ($haulJob->delivery_personnel_id) {
            Notification::create([
                'user_id'  => $haulJob->delivery_personnel_id,
                'title'    => 'Trip cancelled',
                'message'  => "Trip {$haulJob->id} was cancelled by your cooperative.",
                'link'     => route('delivery.trips.index'),
                'category' => 'haul',
            ]);
        }

        return redirect()->route('coop.pickups.index')->with('success', 'Trip cancelled. Its requests are back in the approved queue.');
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
