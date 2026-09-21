<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BuyerProfile;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BuyerVerificationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', BuyerProfile::STATUS_PENDING);

        $buyers = User::query()
            ->where('role', UserRole::BUYER->value)
            ->when($status !== 'all', fn ($q) => $q->whereHas('buyerProfile', fn ($p) => $p->where('status', $status)))
            ->with('buyerProfile')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = BuyerProfile::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.buyers.index', compact('buyers', 'status', 'counts'));
    }

    public function approve(Request $request, User $user)
    {
        abort_unless($user->isBuyer(), 404);

        $user->buyerProfile()->update(['status' => BuyerProfile::STATUS_APPROVED, 'is_verified' => true]);
        $user->update(['status' => 'active']);
        $this->log($request, $user, 'approved', 'Buyer account approved.');
        $this->notify($user, 'Account approved', 'Your buyer account was approved. You can now browse crops and place orders.');

        return back()->with('success', "{$user->name} was approved. They can now place orders with cooperatives.");
    }

    public function reject(Request $request, User $user)
    {
        abort_unless($user->isBuyer(), 404);

        $data = $request->validate(['rejection_reason' => 'required|string|max:2000']);

        $user->buyerProfile()->update(['status' => BuyerProfile::STATUS_REJECTED, 'is_verified' => false]);
        $user->update(['status' => 'pending']);
        $this->log($request, $user, 'rejected', 'Buyer account rejected: '.$data['rejection_reason']);
        $this->notify($user, 'Account rejected', "Your buyer account was rejected: {$data['rejection_reason']}");

        return back()->with('success', "{$user->name} was rejected. They cannot place orders until approved.");
    }

    public function suspend(Request $request, User $user)
    {
        abort_unless($user->isBuyer(), 404);

        if ($user->buyerProfile?->status !== BuyerProfile::STATUS_APPROVED) {
            throw ValidationException::withMessages(['buyer' => 'Only an approved buyer can be suspended.']);
        }

        $user->buyerProfile()->update(['status' => BuyerProfile::STATUS_SUSPENDED, 'is_verified' => false]);
        $user->update(['status' => 'suspended']);
        $this->log($request, $user, 'suspended', 'Buyer account suspended.');
        $this->notify($user, 'Account suspended', 'Your buyer account was suspended. Contact platform support for details.');

        return back()->with('success', "{$user->name} was suspended.");
    }

    public function reactivate(Request $request, User $user)
    {
        abort_unless($user->isBuyer(), 404);

        if ($user->buyerProfile?->status !== BuyerProfile::STATUS_SUSPENDED) {
            throw ValidationException::withMessages(['buyer' => 'Only a suspended buyer can be reactivated.']);
        }

        $user->buyerProfile()->update(['status' => BuyerProfile::STATUS_APPROVED, 'is_verified' => true]);
        $user->update(['status' => 'active']);
        $this->log($request, $user, 'reactivated', 'Buyer account reactivated after suspension.');
        $this->notify($user, 'Account reactivated', 'Your buyer account was reactivated. You can place orders again.');

        return back()->with('success', "{$user->name} was reactivated.");
    }

    private function notify(User $user, string $title, string $message): void
    {
        Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'link' => route('buyer.dashboard'),
            'category' => 'account',
        ]);
    }

    private function log(Request $request, User $user, string $action, string $notes): void
    {
        AuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => $action,
            'target_type' => 'buyer',
            'target_id' => $user->id,
            'notes' => $notes,
        ]);
    }
}
