<?php

namespace App\Http\Controllers;

use App\Models\FarmerProfile;
use App\Models\LogisticsProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JoinCooperativeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $cooperatives = LogisticsProfile::where('logistics_type', 'cooperative')
            ->where('is_verified', true)
            ->with('user')
            ->get();
        $currentRequest = $user->farmerProfile;

        return view('farmers.join-cooperative', compact('cooperatives', 'currentRequest'));
    }

    public function request(LogisticsProfile $cooperative)
    {
        $user = Auth::user();
        $profile = $user->farmerProfile;

        if (!$profile || !$profile->is_verified) {
            return back()->with('error', 'Your profile must be verified first.');
        }

        if ($cooperative->logistics_type !== 'cooperative' || !$cooperative->is_verified) {
            return back()->with('error', 'This cooperative is not available.');
        }

        // Already an approved member
        if ($profile->affiliation_type === 'cooperative' && $profile->membership_status === 'approved') {
            return back()->with('error', 'You are already a member of a cooperative.');
        }

        // Already have a pending request
        if ($profile->membership_status === 'pending') {
            return back()->with('error', 'You already have a pending membership request.');
        }

        $profile->update([
            'membership_status' => 'pending',
            'cooperative_id' => $cooperative->id,
            'membership_requested_at' => now(),
            'affiliation_type' => 'cooperative',
        ]);

        // Sync users table
        $user->update([
            'cooperative_id' => $cooperative->id,
            'affiliation_type' => 'cooperative',
        ]);

        // Notify cooperative admin
        \App\Models\Notification::create([
            'user_id' => $cooperative->user_id,
            'title' => 'New Membership Request',
            'message' => "{$user->name} requests to join {$cooperative->company_name}.",
            'link' => route('logistics.members.index'),
        ]);

        return back()->with('success', "Membership request sent to {$cooperative->company_name}.");
    }

    public function cancel(LogisticsProfile $cooperative)
    {
        $user = Auth::user();
        $profile = $user->farmerProfile;

        if ($profile->membership_status !== 'pending' || (int) $profile->cooperative_id !== (int) $cooperative->id) {
            return back()->with('error', 'No pending request found for this cooperative.');
        }

        $profile->update([
            'membership_status' => null,
            'cooperative_id' => null,
            'membership_requested_at' => null,
            'affiliation_type' => 'independent',
        ]);

        $user->update([
            'cooperative_id' => null,
            'affiliation_type' => 'independent',
        ]);

        return back()->with('success', 'Membership request cancelled.');
    }
}
