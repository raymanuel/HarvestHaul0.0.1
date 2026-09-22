<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Cooperative;
use App\Models\FarmerProfile;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CooperativeMembershipController extends Controller
{
    /**
     * A self-registered farmer has no cooperative yet. This is the request
     * side of the membership queue Coop\FarmerManagementController::index()
     * already displays and approves/rejects.
     */
    public function create()
    {
        $profile = Auth::user()->farmerProfile;

        if ($profile && in_array($profile->membership_status, ['pending', 'approved'], true)) {
            return redirect()->route('farmer.dashboard')
                ->with('error', $profile->membership_status === 'pending'
                    ? 'Your membership request is still awaiting a decision.'
                    : 'You are already a member of a cooperative.');
        }

        $cooperatives = Cooperative::where('status', Cooperative::STATUS_APPROVED)
            ->orderBy('name')
            ->get();

        return view('farmer.cooperative-membership.create', compact('cooperatives'));
    }

    public function store(Request $request)
    {
        $profile = Auth::user()->farmerProfile;

        if ($profile && in_array($profile->membership_status, ['pending', 'approved'], true)) {
            throw ValidationException::withMessages([
                'cooperative_id' => 'You already have a membership request or an active membership.',
            ]);
        }

        $data = $request->validate([
            'cooperative_id' => ['required', 'exists:cooperatives,id'],
            'phone'          => 'nullable|string|max:20',
            'farm_location'  => 'nullable|string|max:255',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
        ]);

        $cooperative = Cooperative::where('id', $data['cooperative_id'])
            ->where('status', Cooperative::STATUS_APPROVED)
            ->firstOrFail();

        $farmer = FarmerProfile::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'phone'                   => $data['phone'] ?? null,
                'farm_location'           => $data['farm_location'] ?? null,
                'latitude'                => $data['latitude'] ?? null,
                'longitude'               => $data['longitude'] ?? null,
                'affiliation_type'        => 'cooperative',
                'cooperative_id'          => $cooperative->id,
                'membership_status'       => 'pending',
                'membership_requested_at' => now(),
                'membership_decided_at'   => null,
            ]
        );

        $this->notifyCoopAdmins($cooperative->id, [
            'title'   => 'New farmer membership request',
            'message' => Auth::user()->name." requested to join {$cooperative->name}. Review it in your farmer membership queue.",
            'link'    => route('coop.farmers.index'),
        ]);

        return redirect()->route('farmer.dashboard')
            ->with('success', "Membership request sent to {$cooperative->name}. You'll be notified once they decide.");
    }

    private function notifyCoopAdmins(int $cooperativeId, array $payload): void
    {
        $admins = User::where('role', UserRole::COOP_ADMIN->value)
            ->where('cooperative_id', $cooperativeId)
            ->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id'  => $admin->id,
                'title'    => $payload['title'],
                'message'  => $payload['message'],
                'link'     => $payload['link'],
                'category' => 'membership',
            ]);
        }
    }
}
