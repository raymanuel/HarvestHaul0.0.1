<?php

namespace App\Http\Controllers;

use App\Models\FarmerProfile;
use App\Models\Harvest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CooperativeMembersController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $logisticsProfile = $user->logisticsProfile;

        if (!$logisticsProfile || !$logisticsProfile->isCooperative()) {
            return redirect()->route('dashboard')->with('error', 'Access denied.');
        }

        $pendingRequests = FarmerProfile::where('cooperative_id', $logisticsProfile->id)
            ->where('membership_status', 'pending')
            ->with('user')
            ->get();

        $members = FarmerProfile::where('cooperative_id', $logisticsProfile->id)
            ->where('membership_status', 'approved')
            ->with('user')
            ->get();

        return view('logistics.members', compact('pendingRequests', 'members'));
    }

    public function approve(FarmerProfile $farmerProfile)
    {
        $user = Auth::user();
        $logisticsProfile = $user->logisticsProfile;

        if (!$logisticsProfile || !$logisticsProfile->isCooperative()) {
            return redirect()->route('dashboard')->with('error', 'Access denied.');
        }

        if ((int) $farmerProfile->cooperative_id !== (int) $logisticsProfile->id) {
            return back()->with('error', 'This farmer is not associated with your cooperative.');
        }

        if ($farmerProfile->membership_status !== 'pending') {
            return back()->with('error', 'No pending request found.');
        }

        $farmerProfile->update([
            'membership_status' => 'approved',
            'affiliation_type' => 'cooperative',
            'membership_decided_at' => now(),
        ]);

        $farmerProfile->user->update([
            'cooperative_id' => $logisticsProfile->id,
            'affiliation_type' => 'cooperative',
        ]);

        \App\Models\Notification::create([
            'user_id' => $farmerProfile->user_id,
            'title' => 'Membership Approved',
            'message' => "Your request to join {$logisticsProfile->company_name} has been approved.",
            'link' => route('dashboard'),
        ]);

        return back()->with('success', "{$farmerProfile->user->name} has been approved as a member.")
            ->with('next_steps', [
                'title'   => 'Member approved',
                'message' => $farmerProfile->user->name . ' has been approved as a member.',
                'steps'   => [
                    'The farmer is now a cooperative member.',
                    'Their harvests will appear on your cooperative crop board and become eligible for pooled routes.',
                    'The farmer is notified and can start posting.',
                ],
            ]);
    }

    public function reject(FarmerProfile $farmerProfile)
    {
        $user = Auth::user();
        $logisticsProfile = $user->logisticsProfile;

        if (!$logisticsProfile || !$logisticsProfile->isCooperative()) {
            return redirect()->route('dashboard')->with('error', 'Access denied.');
        }

        if ((int) $farmerProfile->cooperative_id !== (int) $logisticsProfile->id) {
            return back()->with('error', 'This farmer is not associated with your cooperative.');
        }

        if ($farmerProfile->membership_status !== 'pending') {
            return back()->with('error', 'No pending request found.');
        }

        $farmerProfile->update([
            'membership_status' => 'rejected',
            'membership_decided_at' => now(),
        ]);

        // Reset farmer back to independent since request was rejected
        $farmerProfile->user->update([
            'cooperative_id' => null,
            'affiliation_type' => 'independent',
        ]);

        $farmerProfile->update([
            'cooperative_id' => null,
            'affiliation_type' => 'independent',
        ]);

        \App\Models\Notification::create([
            'user_id' => $farmerProfile->user_id,
            'title' => 'Membership Not Approved',
            'message' => "Your request to join {$logisticsProfile->company_name} was not approved at this time.",
            'link' => route('farmer.join-cooperative.index'),
        ]);

        return back()->with('success', "{$farmerProfile->user->name}'s request has been rejected.")
            ->with('next_steps', [
                'title'   => 'Request declined',
                'message' => $farmerProfile->user->name . "'s request has been rejected.",
                'steps'   => [
                    'The farmer has been reset to independent and notified.',
                    'They may submit a new membership request later if you change your mind.',
                ],
            ]);
    }

    public function remove(FarmerProfile $farmerProfile)
    {
        $user = Auth::user();
        $logisticsProfile = $user->logisticsProfile;

        if (!$logisticsProfile || !$logisticsProfile->isCooperative()) {
            return redirect()->route('dashboard')->with('error', 'Access denied.');
        }

        if ((int) $farmerProfile->cooperative_id !== (int) $logisticsProfile->id) {
            return back()->with('error', 'This farmer is not a member of your cooperative.');
        }

        if ($farmerProfile->membership_status !== 'approved') {
            return back()->with('error', 'No approved membership found for this farmer.');
        }

        $farmerProfile->update([
            'membership_status' => null,
            'cooperative_id' => null,
            'membership_requested_at' => null,
            'membership_decided_at' => now(),
            'affiliation_type' => 'independent',
        ]);

        // Reset user back to independent
        $farmerProfile->user->update([
            'cooperative_id' => null,
            'affiliation_type' => 'independent',
        ]);

        // Clear drop-off on open posts still pointing at the coop hub
        $resetCount = Harvest::resetHubDestinationForUser($farmerProfile->user_id, $logisticsProfile->latitude, $logisticsProfile->longitude);

        $farmerMessage = "Your cooperative membership with {$logisticsProfile->company_name} has ended. Your harvests are independent again.";
        if ($resetCount > 0) {
            $farmerMessage .= " {$resetCount} open post(s) that still pointed to {$logisticsProfile->company_name} as drop-off were reset.";
        }

        \App\Models\Notification::create([
            'user_id'     => $farmerProfile->user_id,
            'title'       => 'Membership Ended',
            'message'     => $farmerMessage,
            'link'        => route('dashboard'),
        ]);

        $adminSuccess = "{$farmerProfile->user->name} has been removed from the cooperative.";
        $adminSteps   = [
            'The farmer has been reset to independent and notified.',
            'They can join a cooperative again anytime from their dashboard.',
        ];

        if ($resetCount > 0) {
            $adminSuccess .= " {$resetCount} open post(s) had the drop-off reset.";
            array_unshift($adminSteps, "{$resetCount} open post(s) that still listed your hub as drop-off were reset.");
        }

        return back()->with('success', $adminSuccess)
            ->with('next_steps', [
                'title'   => 'Member removed',
                'message' => "{$farmerProfile->user->name} has been removed from the cooperative.",
                'steps'   => $adminSteps,
            ]);
    }
}
