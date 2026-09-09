<?php

namespace App\Http\Controllers;

use App\Models\FarmerProfile;
use App\Models\Harvest;
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

    public function leave()
    {
        $user = Auth::user();
        $profile = $user->farmerProfile;

        if (!$profile || $profile->membership_status !== 'approved' || !$profile->cooperative_id) {
            return back()->with('error', 'You are not a member of any cooperative.');
        }

        $cooperative = $profile->cooperative;

        $profile->update([
            'membership_status' => null,
            'cooperative_id' => null,
            'membership_requested_at' => null,
            'membership_decided_at' => now(),
            'affiliation_type' => 'independent',
        ]);

        // Sync users table
        $user->update([
            'cooperative_id' => null,
            'affiliation_type' => 'independent',
        ]);

        // Clear drop-off on open posts still pointing at the old coop hub
        $resetCount = Harvest::resetHubDestinationForUser($user->id, $cooperative->latitude, $cooperative->longitude);

        $adminMessage = "{$user->name} left {$cooperative->company_name}. Their harvests are now independent.";
        if ($resetCount > 0) {
            $adminMessage .= " {$resetCount} open post(s) had the drop-off reset.";
        }

        \App\Models\Notification::create([
            'user_id' => $cooperative->user_id,
            'title'   => 'Member Left Cooperative',
            'message' => $adminMessage,
            'link'    => route('logistics.members.index'),
        ]);

        $success = "You left {$cooperative->company_name}.";
        $steps   = [
            'Your harvests are back to independent farming.',
            'You can browse and join another cooperative anytime from your dashboard.',
        ];

        if ($resetCount > 0) {
            $success .= " We reset the drop-off on your {$resetCount} open post(s) that still pointed to {$cooperative->company_name}.";
            array_unshift($steps, "Open each of those {$resetCount} post(s) and pick a new drop-off so buyers can book them.");
        }

        return back()->with('success', $success)
            ->with('next_steps', [
                'title'   => 'Membership ended',
                'message' => "You left {$cooperative->company_name}.",
                'steps'   => $steps,
            ]);
    }
}
