<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CooperativeStatusMail;
use App\Models\AuditLog;
use App\Models\Cooperative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CooperativeVerificationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $cooperatives = Cooperative::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->withCount('members')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = Cooperative::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.cooperatives.index', compact('cooperatives', 'status', 'counts'));
    }

    public function show(Cooperative $cooperative)
    {
        $cooperative->load(['coopAdminUser', 'reviewer', 'members']);

        return view('admin.cooperatives.show', compact('cooperative'));
    }

    public function approve(Request $request, Cooperative $cooperative)
    {
        $cooperative->update([
            'status' => Cooperative::STATUS_APPROVED,
            'reviewer_id' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->log($request, $cooperative, 'approved', 'Cooperative approved and can now operate on the platform.');

        if ($cooperative->coopAdminUser) {
            Mail::to($cooperative->coopAdminUser->email)->send(new CooperativeStatusMail($cooperative, 'approved', null, $request->user()->name));
        }

        return back()->with('success', "{$cooperative->name} was approved. The cooperative admin can open their workspace and add people.");
    }

    public function reject(Request $request, Cooperative $cooperative)
    {
        $data = $request->validate([
            'rejection_reason' => 'required|string|max:2000',
        ]);

        $cooperative->update([
            'status' => Cooperative::STATUS_REJECTED,
            'reviewer_id' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        $this->log($request, $cooperative, 'rejected', 'Cooperative rejected. Reason: '.$data['rejection_reason']);

        if ($cooperative->coopAdminUser) {
            Mail::to($cooperative->coopAdminUser->email)->send(new CooperativeStatusMail($cooperative, 'rejected', $data['rejection_reason'], $request->user()->name));
        }

        return back()->with('success', "{$cooperative->name} was rejected. The applicant sees the reason and can register again with corrected documents.");
    }

    public function requestInfo(Request $request, Cooperative $cooperative)
    {
        $data = $request->validate([
            'admin_notes' => 'required|string|max:2000',
        ]);

        $cooperative->update([
            'status' => Cooperative::STATUS_REQUIRES_REVISION,
            'reviewer_id' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_notes' => $data['admin_notes'],
        ]);

        $this->log($request, $cooperative, 'request_info', 'Requested more information: '.$data['admin_notes']);

        if ($cooperative->coopAdminUser) {
            Mail::to($cooperative->coopAdminUser->email)->send(new CooperativeStatusMail($cooperative, 'request_info', $data['admin_notes'], $request->user()->name));
        }

        return back()->with('success', "More information was requested from {$cooperative->name}. It now shows as needing revision until they respond.");
    }

    public function reactivate(Request $request, Cooperative $cooperative)
    {
        abort_unless($cooperative->isSuspended(), 422, 'Only a suspended cooperative can be reactivated.');

        $cooperative->update([
            'status' => Cooperative::STATUS_APPROVED,
            'reviewer_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->log($request, $cooperative, 'reactivated', 'Cooperative reactivated after suspension.');

        if ($cooperative->coopAdminUser) {
            Mail::to($cooperative->coopAdminUser->email)->send(new CooperativeStatusMail($cooperative, 'reactivated', null, $request->user()->name));
        }

        return back()->with('success', "{$cooperative->name} was reactivated. Its admin and staff can operate again.");
    }

    public function suspend(Request $request, Cooperative $cooperative)
    {
        $cooperative->update([
            'status' => Cooperative::STATUS_SUSPENDED,
            'reviewer_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->log($request, $cooperative, 'suspended', 'Cooperative suspended from platform operations.');

        if ($cooperative->coopAdminUser) {
            Mail::to($cooperative->coopAdminUser->email)->send(new CooperativeStatusMail($cooperative, 'suspended', null, $request->user()->name));
        }

        return back()->with('success', "{$cooperative->name} was suspended. Its admin and staff can no longer operate until you approve it again.");
    }

    private function log(Request $request, Cooperative $cooperative, string $action, string $notes): void
    {
        AuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => $action,
            'target_type' => 'cooperative',
            'target_id' => $cooperative->id,
            'notes' => $notes,
        ]);
    }
}
