<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\Notification;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
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

    public function create(Request $request, ConsolidationEngine $engine)
    {
        $cooperativeId = $this->cooperativeId();
        $date = $request->query('date', today()->toDateString());

        $cooperative = Auth::user()->cooperative;
        $plan = $engine->planForDate($cooperative, $date);

        return view('coop.pickups.create', compact('plan', 'date'));
    }

    public function store(Request $request, ConsolidationEngine $engine)
    {
        $cooperativeId = $this->cooperativeId();
        $cooperative = Auth::user()->cooperative;

        $requestIds = array_values(array_filter((array) ($request->input('requests') ?? [])));

        if (empty($requestIds)) {
            throw ValidationException::withMessages(['requests' => 'Select at least one pickup request for the trip.']);
        }

        // Only this cooperative's own pending requests on the chosen date.
        $requests = HaulRequest::whereIn('id', $requestIds)
            ->where('cooperative_id', $cooperativeId)
            ->where('status', HaulRequest::STATUS_PENDING)
            ->whereDate('preferred_pickup_date', $request->input('date'))
            ->get();

        if ($requests->count() !== count($requestIds)) {
            throw ValidationException::withMessages([
                'requests' => 'One or more selected requests are not available to schedule for this cooperative.',
            ]);
        }

        $data = $request->validate([
            'date'                => 'required|date|after_or_equal:today',
            'truck_id'            => ['required', Rule::exists('trucks', 'id')->where('cooperative_id', $cooperativeId)],
            'delivery_personnel_id' => [
                'required',
                Rule::exists('users', 'id')
                    ->where('cooperative_id', $cooperativeId)
                    ->where('role', UserRole::DELIVERY_PERSONNEL->value),
            ],
            'sequences'           => 'array',
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

        $job = DB::transaction(function () use ($cooperativeId, $data, $truck, $requests, $seqMap, $totalWeight) {
            $haulJob = HaulJob::create([
                'haul_request_id' => null,
                'cooperative_id'  => $cooperativeId,
                'delivery_personnel_id' => $data['delivery_personnel_id'],
                'truck_id'        => $truck->id,
                'pickup_date'     => $data['date'],
                'scheduled_at'    => $data['date'].' 00:00:00',
                'status'          => HaulJob::STATUS_SCHEDULED,
            ]);

            // Capacity advisory flag stored so the UI can flag it.
            if ($totalWeight > (float) $truck->capacity_kg) {
                $haulJob->update(['status' => HaulJob::STATUS_SCHEDULED]);
            }

            $truck->update(['status' => 'in_use']);

            foreach ($requests as $i => $hr) {
                $seq = $seqMap ? $seqMap[($i + 1)] : ($i + 1);
                HaulJobStop::create([
                    'haul_job_id'    => $haulJob->id,
                    'haul_request_id'=> $hr->id,
                    'sequence_no'    => $seq,
                    'status'         => HaulJobStop::STATUS_PENDING,
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
            'message'  => "{$job->id} is scheduled for {$data['date']} with {$requests->count()} stops. See your pickup trips to start the run.",
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

    public function show(HaulJob $haulJob)
    {
        $this->authorizeCoop($haulJob);
        $haulJob->load(['haulRequest.crop', 'truck', 'deliveryPersonnel', 'stops.haulRequest.farmer']);

        $stops = $haulJob->stops()->orderBy('sequence_no')->get();

        return view('coop.pickups.show', compact('haulJob', 'stops'));
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
