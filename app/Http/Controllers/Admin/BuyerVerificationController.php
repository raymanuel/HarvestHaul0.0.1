<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;

class BuyerVerificationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $buyers = User::query()
            ->where('role', UserRole::BUYER->value)
            ->whereHas('buyerProfile', function ($q) use ($status) {
                if ($status === 'verified') {
                    $q->where('is_verified', true);
                } elseif ($status === 'pending') {
                    $q->where('is_verified', false);
                }
            })
            ->with('buyerProfile')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.buyers.index', compact('buyers', 'status'));
    }

    public function approve(Request $request, User $user)
    {
        abort_unless($user->isBuyer(), 404);

        $user->buyerProfile()->update(['is_verified' => true]);
        $user->update(['status' => 'active']);
        $this->log($request, $user, 'approved', 'Buyer account verified.');

        return back()->with('success', "{$user->name} was verified. They can now place orders with cooperatives.");
    }

    public function reject(Request $request, User $user)
    {
        abort_unless($user->isBuyer(), 404);

        $user->buyerProfile()->update(['is_verified' => false]);
        $user->update(['status' => 'pending']);
        $this->log($request, $user, 'rejected', 'Buyer account verification rejected.');

        return back()->with('success', "{$user->name} was not verified. They cannot place orders until you approve them.");
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