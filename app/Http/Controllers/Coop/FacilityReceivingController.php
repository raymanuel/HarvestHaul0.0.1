<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\HaulJob;
use App\Models\ReceivingRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Facility receiving — re-verifies a ReceivingRecord's pickup-time weight
 * once the truck has returned to the cooperative, flagging a variance if the
 * facility recount differs. This is a checkpoint/audit layer on top of the
 * pickup-time receiving record; it does not gate Cooperative Procurement,
 * which already works per-ReceivingRecord independent of this step.
 */
class FacilityReceivingController extends Controller
{
    public function index()
    {
        $cooperativeId = $this->cooperativeId();

        $pending = ReceivingRecord::with(['haulJob', 'farmer', 'crop', 'cropGrade'])
            ->where('cooperative_id', $cooperativeId)
            ->whereNull('facility_verified_at')
            ->whereHas('haulJob', fn ($q) => $q->where('status', HaulJob::STATUS_COMPLETED))
            ->orderBy('created_at')
            ->get();

        $flagged = ReceivingRecord::with(['haulJob', 'farmer', 'crop'])
            ->where('cooperative_id', $cooperativeId)
            ->where('variance_status', 'flagged')
            ->orderByDesc('facility_verified_at')
            ->get();

        $verified = ReceivingRecord::with(['haulJob', 'farmer', 'crop'])
            ->where('cooperative_id', $cooperativeId)
            ->whereNotNull('facility_verified_at')
            ->where(function ($q) {
                $q->whereNull('variance_status')->orWhere('variance_status', 'resolved');
            })
            ->orderByDesc('facility_verified_at')
            ->limit(12)
            ->get();

        return view('coop.facility-receiving.index', compact('pending', 'flagged', 'verified'));
    }

    public function show(ReceivingRecord $receivingRecord)
    {
        $this->authorizeCoop($receivingRecord);
        $receivingRecord->load(['haulJob', 'farmer', 'crop', 'cropGrade', 'facilityVerifier']);

        return view('coop.facility-receiving.show', compact('receivingRecord'));
    }

    public function verify(Request $request, ReceivingRecord $receivingRecord)
    {
        $this->authorizeCoop($receivingRecord);

        if ($receivingRecord->facility_verified_at !== null) {
            throw ValidationException::withMessages([
                'receiving' => 'This record has already been facility-verified.',
            ]);
        }

        $data = $request->validate([
            'facility_received_weight_kg' => 'required|numeric|min:0',
            'variance_notes'              => 'nullable|string|max:1000',
        ]);

        $variance = round((float) $data['facility_received_weight_kg'] - (float) $receivingRecord->actual_weight_kg, 2);
        $flagged = abs($variance) > 0;

        if ($flagged && empty($data['variance_notes'])) {
            throw ValidationException::withMessages([
                'variance_notes' => 'A note is required when the facility-received weight differs from the pickup weight.',
            ]);
        }

        $receivingRecord->update([
            'facility_received_weight_kg' => $data['facility_received_weight_kg'],
            'facility_verified_at'        => now(),
            'facility_verified_by'        => Auth::id(),
            'variance_kg'                 => $variance,
            'variance_status'             => $flagged ? 'flagged' : null,
            'variance_notes'              => $data['variance_notes'] ?? null,
        ]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'facility_verify_receiving',
            'target_type' => 'receiving_record',
            'target_id'   => $receivingRecord->id,
            'notes'       => $flagged
                ? "Facility receiving verified with a {$variance} kg variance for {$receivingRecord->farmer?->name}."
                : "Facility receiving verified, no variance, for {$receivingRecord->farmer?->name}.",
        ]);

        return redirect()->route('coop.facility-receiving.index')
            ->with('success', $flagged ? 'Verified — variance flagged for review.' : 'Verified — quantities match.');
    }

    public function resolve(Request $request, ReceivingRecord $receivingRecord)
    {
        $this->authorizeCoop($receivingRecord);

        if ($receivingRecord->variance_status !== 'flagged') {
            throw ValidationException::withMessages([
                'receiving' => 'Only a flagged variance can be resolved.',
            ]);
        }

        $data = $request->validate([
            'variance_notes' => 'required|string|max:1000',
        ]);

        $receivingRecord->update([
            'variance_status' => 'resolved',
            'variance_notes'  => $data['variance_notes'],
        ]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'facility_variance_resolved',
            'target_type' => 'receiving_record',
            'target_id'   => $receivingRecord->id,
            'notes'       => "Variance resolved for {$receivingRecord->farmer?->name}: {$data['variance_notes']}",
        ]);

        return redirect()->route('coop.facility-receiving.index')
            ->with('success', 'Variance resolved.');
    }

    private function cooperativeId(): int
    {
        $id = Auth::user()?->cooperative_id;
        if (! $id) {
            abort(403, 'You are not an active cooperative admin.');
        }

        return $id;
    }

    private function authorizeCoop(ReceivingRecord $record): void
    {
        if ($record->cooperative_id !== Auth::user()?->cooperative_id) {
            abort(403, 'This receiving record does not belong to your cooperative.');
        }
    }
}
