<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\CropGrade;
use App\Models\CropVariety;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\Notification;
use App\Models\ReceivingRecord;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReceivingController extends Controller
{
    /**
     * Field / Receiving personnel.
     * Today's pickup trips and the pending stops that still need receiving
     * recorded against them.
     */
    public function index()
    {
        $cooperativeId = $this->cooperativeId();

        $jobs = HaulJob::with(['haulRequest.crop', 'haulRequest.farmer', 'truck', 'stops.haulRequest'])
            ->where('cooperative_id', $cooperativeId)
            ->whereDate('pickup_date', today())
            ->whereIn('status', [HaulJob::STATUS_PICKED_UP, HaulJob::STATUS_COMPLETED])
            ->orderBy('scheduled_at')
            ->get();

        return view('field.receiving.index', compact('jobs'));
    }

    public function show(HaulJob $haulJob)
    {
        $this->authorizeCoop($haulJob);
        $haulJob->load(['haulRequest.crop', 'haulRequest.farmer', 'stops.haulRequest', 'stops.receivingRecords']);

        return view('field.receiving.show', compact('haulJob'));
    }

    public function create(HaulJob $haulJob, HaulJobStop $stop)
    {
        $this->authorizeCoop($haulJob);
        $this->authorizeStopBelongsToJob($stop, $haulJob);

        // A stop is receivable once it has been picked up.
        if ($stop->status !== HaulJobStop::STATUS_PICKED_UP) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'stop' => 'Record receiving only after the crop has been picked up at this stop.',
            ]);
        }

        $crops = Crop::active()->orderBy('name')->get();
        $varieties = CropVariety::active()->get();
        $grades = CropGrade::active()->get();

        return view('field.receiving.create', compact('haulJob', 'stop', 'crops', 'varieties', 'grades'));
    }

    public function store(Request $request, HaulJob $haulJob, HaulJobStop $stop)
    {
        $this->authorizeCoop($haulJob);
        $this->authorizeStopBelongsToJob($stop, $haulJob);

        if ($stop->status !== HaulJobStop::STATUS_PICKED_UP) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'stop' => 'Record receiving only after the crop has been picked up at this stop.',
            ]);
        }

        // One receiving record per stop (the authoritative quantity comes from here).
        if (ReceivingRecord::where('haul_job_id', $haulJob->id)->where('haul_request_id', $stop->haul_request_id)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'stop' => 'Receiving has already been recorded for this stop.',
            ]);
        }

        $requestRecord = $stop->haulRequest;

        $data = $request->validate([
            'crop_variety_id'       => 'nullable|exists:crop_varieties,id',
            'crop_grade_id'         => 'required|exists:crop_grades,id',
            'actual_sacks'          => 'required|integer|min:1',
            'actual_weight_kg'      => 'required|numeric|min:0.01',
            'buying_price_per_kg'   => 'nullable|numeric|min:0',
            'remarks'               => 'nullable|string|max:1000',
        ]);

        $weight = (float) $data['actual_weight_kg'];
        $price = isset($data['buying_price_per_kg']) ? (float) $data['buying_price_per_kg'] : null;
        $total = $price !== null ? $weight * $price : null;

        $confirmer = $price !== null ? $this->fieldCoopConfirmer() : null;
        $status = match (true) {
            $price === null => ReceivingRecord::STATUS_PENDING,
            $confirmer !== null => ReceivingRecord::STATUS_CONFIRMED,
            default => ReceivingRecord::STATUS_PRICED,
        };

        $record = ReceivingRecord::create(array_merge($data, [
            'haul_job_id'      => $haulJob->id,
            'haul_request_id'  => $stop->haul_request_id,
            'cooperative_id'   => $haulJob->cooperative_id,
            'farmer_id'        => $requestRecord->farmer_id,
            'crop_id'          => $requestRecord->crop_id,
            'actual_sacks'     => $data['actual_sacks'],
            'actual_weight_kg' => $weight,
            'buying_price_per_kg' => $price,
            'total_amount'          => $total,
            'remarks'               => $data['remarks'] ?? null,
            'recorded_by'      => Auth::id(),
            'recording_role'   => 'field_receiving',
            'status'           => $status,
            'confirmed_by'     => $confirmer,
            'confirmed_at'     => $confirmer !== null ? now() : null,
        ]));

        // Crop availability (sellable inventory) is created once the
        // cooperative confirms the procurement, not at raw receiving time —
        // see Coop\ProcurementController::confirm().
        $this->notifyCoopAdmins($haulJob->cooperative_id, [
            'title'   => 'Receiving recorded',
            'message' => Auth::user()->name." recorded the pickup for {$requestRecord->farmer?->name}: {$weight} kg. Review and confirm the procurement in your procurement queue.",
            'link'    => route('coop.procurement.index'),
        ]);

        return redirect()->route('coop.procurement.index')
            ->with('success', 'Receiving recorded.')
            ->with('next_steps', [
                'title'   => 'Receiving recorded',
                'message' => 'The cooperative will confirm the procurement. The farmer will see the payout once confirmed.',
                'steps'   => ['Go to Procurement to confirm the receiving record and the agreed buying price.'],
                'cta'     => ['label' => 'Open procurement queue', 'url' => route('coop.procurement.index')],
            ]);
    }

    private function authorizeCoop(HaulJob $haulJob): void
    {
        if ($haulJob->cooperative_id !== Auth::user()?->cooperative_id) {
            abort(403, 'This trip does not belong to your cooperative.');
        }
    }

    /**
     * {stop} is bound independently of {haulJob} by Laravel's route-model
     * binding — nothing stops a request from pairing a stop ID that belongs
     * to a different job (and thus potentially a different cooperative) with
     * a {haulJob} the caller does legitimately own. authorizeCoop() alone
     * only checks the job; this closes that gap.
     */
    private function authorizeStopBelongsToJob(HaulJobStop $stop, HaulJob $haulJob): void
    {
        if ($stop->haul_job_id !== $haulJob->id) {
            abort(404);
        }
    }

    private function cooperativeId(): int
    {
        $id = Auth::user()?->cooperative_id;
        if (! $id) {
            abort(403, 'You are not part of a cooperative.');
        }

        return $id;
    }

    private function fieldCoopConfirmer(): ?int
    {
        // A field user recording both weight and price for their own coop is
        // treated as the coop confirming on the spot.
        return Auth::user()->isCoopAdmin() ? Auth::id() : null;
    }

    private function notifyCoopAdmins(int $cooperativeId, array $payload): void
    {
        $admins = \App\Models\User::where('role', \App\Models\UserRole::COOP_ADMIN->value)
            ->where('cooperative_id', $cooperativeId)->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title'   => $payload['title'],
                'message' => $payload['message'],
                'link'    => $payload['link'],
                'category' => 'haul',
            ]);
        }
    }
}
