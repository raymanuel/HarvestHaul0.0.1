<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\HaulRequest;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class HaulRequestController extends Controller
{
    public function index(Request $request)
    {
        $cooperativeId = $this->cooperativeId();

        $status = $request->query('status');
        $allowed = [HaulRequest::STATUS_PENDING, HaulRequest::STATUS_APPROVED, HaulRequest::STATUS_SCHEDULED, HaulRequest::STATUS_COMPLETED, HaulRequest::STATUS_REJECTED, HaulRequest::STATUS_CANCELLED];

        $requests = HaulRequest::with(['farmer.farmerProfile', 'crop', 'cropVariety', 'packagingType'])
            ->forCooperative($cooperativeId)
            ->when($status && in_array($status, $allowed, true), fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 WHEN 'scheduled' THEN 3 WHEN 'completed' THEN 4 WHEN 'rejected' THEN 5 WHEN 'cancelled' THEN 6 ELSE 7 END")
            ->latest('created_at')
            ->get();

        $counts = [
            'pending'   => HaulRequest::forCooperative($cooperativeId)->pending()->count(),
            'scheduled' => HaulRequest::forCooperative($cooperativeId)->where('status', HaulRequest::STATUS_SCHEDULED)->count(),
            'completed' => HaulRequest::forCooperative($cooperativeId)->where('status', HaulRequest::STATUS_COMPLETED)->count(),
        ];

        $upcomingPickup = HaulRequest::forCooperative($cooperativeId)
            ->where('status', HaulRequest::STATUS_SCHEDULED)
            ->whereNotNull('preferred_pickup_date')
            ->latest('preferred_pickup_date')
            ->value('preferred_pickup_date');

        return view('coop.haul-requests.index', compact('requests', 'counts', 'status', 'upcomingPickup'));
    }

    public function show(HaulRequest $haulRequest)
    {
        $this->authorizeCoop($haulRequest);

        $haulRequest->load([
            'farmer.farmerProfile', 'crop', 'cropVariety', 'packagingType',
            'haulJob.truck', 'haulJob.deliveryPersonnel', 'haulJob.stops.haulRequest.farmer',
        ]);

        return view('coop.haul-requests.show', compact('haulRequest'));
    }

    /**
     * Approve (5.5) — reviews a pending request as fit to schedule, before it
     * becomes eligible for the consolidated pickup planner.
     */
    public function approve(HaulRequest $haulRequest)
    {
        $this->authorizeCoop($haulRequest);

        if ($haulRequest->status !== HaulRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'haul_request' => 'Only pending requests can be approved.',
            ]);
        }

        $haulRequest->update(['status' => HaulRequest::STATUS_APPROVED]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'approve_haul_request',
            'target_type' => 'haul_request',
            'target_id'   => $haulRequest->id,
            'notes'       => "Pickup request {$haulRequest->id} approved. Eligible for pickup scheduling.",
        ]);

        Notification::create([
            'user_id'  => $haulRequest->farmer_id,
            'title'    => 'Pickup request approved',
            'message'  => 'Your cooperative approved your pickup request. It will be scheduled into a pickup trip soon.',
            'link'     => route('farmer.haul-requests.index'),
            'category' => 'haul',
        ]);

        return redirect()->route('coop.haul-requests.index')
            ->with('success', 'Pickup request approved. It is now eligible for scheduling.');
    }

    public function reject(Request $request, HaulRequest $haulRequest)
    {
        $this->authorizeCoop($haulRequest);

        if ($haulRequest->status !== HaulRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'haul_request' => 'Only pending requests can be declined.',
            ]);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $haulRequest->update(['status' => HaulRequest::STATUS_REJECTED]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'reject_haul_request',
            'target_type' => 'haul_request',
            'target_id'   => $haulRequest->id,
            'notes'       => "Pickup request {$haulRequest->id} declined. Reason: {$validated['reason']}",
        ]);

        Notification::create([
            'user_id'  => $haulRequest->farmer_id,
            'title'    => 'Pickup request declined',
            'message'  => 'Your cooperative could not schedule your pickup this time. Reason: '.$validated['reason'].' You can file a new request or reach out to them for details.',
            'link'     => route('farmer.haul-requests.index'),
            'category' => 'haul',
        ]);

        return redirect()->route('coop.haul-requests.index')
            ->with('success', 'Pickup request declined. The farmer was notified.');
    }

    private function cooperativeId(): int
    {
        $cooperativeId = Auth::user()?->cooperative_id;

        if (! $cooperativeId) {
            abort(403, 'You are not an active cooperative admin.');
        }

        return $cooperativeId;
    }

    private function authorizeCoop(HaulRequest $haulRequest): void
    {
        if ($haulRequest->cooperative_id !== Auth::user()?->cooperative_id) {
            abort(403, 'This request does not belong to your cooperative.');
        }
    }
}