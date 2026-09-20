<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FarmerProfile;
use App\Models\HaulRequest;
use App\Models\Notification;
use App\Models\ReceivingRecord;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FarmerManagementController extends Controller
{
    /**
     * Farmer membership requests section (3A).
     *
     * The cooperative admin works a queue of farmers who requested membership
     * into their cooperative. Approve lets the farmer submit haul requests;
     * reject (with a reason) declines the request; remove severs the
     * membership of an already-approved farmer.
     */
    public function index()
    {
        $cooperativeId = Auth::user()?->cooperative_id;

        if (! $cooperativeId) {
            abort(403, 'You are not an active cooperative admin.');
        }

        $pendingFarmers = FarmerProfile::with(['user'])
            ->where('cooperative_id', $cooperativeId)
            ->where('membership_status', 'pending')
            ->orderByDesc('membership_requested_at')
            ->get();

        $approvedFarmers = FarmerProfile::with(['user'])
            ->where('cooperative_id', $cooperativeId)
            ->where('membership_status', 'approved')
            ->orderByDesc('membership_decided_at')
            ->get();

        return view('coop.farmers.index', compact('pendingFarmers', 'approvedFarmers'));
    }

    /**
     * Approve a farmer's membership request.
     */
    public function approve(User $user)
    {
        $farmer = $this->resolveFarmer($user);
        $this->authorizeCoop($farmer);
        $this->assertAwaiting($farmer);

        $farmer->update([
            'membership_status'     => 'approved',
            'membership_decided_at' => now(),
        ]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'approve_farmer_membership',
            'target_type' => 'farmer_profile',
            'target_id'   => $farmer->id,
            'notes'       => "Farmer {$user->name} approved for cooperative membership by coop admin.",
        ]);

        Notification::create([
            'user_id'  => $user->id,
            'title'    => 'Cooperative membership approved',
            'message'  => 'Welcome aboard. Your cooperative has approved your membership — you can now submit harvest pickup requests from your farmer dashboard.',
            'link'     => route('farmer.dashboard'),
            'category' => 'membership',
        ]);

        return back()->with('success', "Farmer {$user->name} approved. They can now submit haul requests.");
    }

    /**
     * Reject a farmer's membership request, with a required reason.
     */
    public function reject(Request $request, User $user)
    {
        $farmer = $this->resolveFarmer($user);
        $this->authorizeCoop($farmer);
        $this->assertAwaiting($farmer);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $farmer->update([
            'membership_status'     => 'rejected',
            'membership_decided_at' => now(),
        ]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'reject_farmer_membership',
            'target_type' => 'farmer_profile',
            'target_id'   => $farmer->id,
            'notes'       => "Farmer {$user->name}'s membership request rejected. Reason: {$validated['reason']}",
        ]);

        Notification::create([
            'user_id'  => $user->id,
            'title'    => 'Cooperative membership declined',
            'message'  => 'Your cooperative declined your membership request. Reason: ' . $validated['reason'],
            'link'     => route('farmer.dashboard'),
            'category' => 'membership',
        ]);

        return back()->with('success', "Farmer {$user->name}'s membership request rejected.");
    }

    /**
     * Remove an already-approved farmer from the cooperative (sever membership).
     */
    public function remove(User $user)
    {
        $farmer = $this->resolveFarmer($user);
        $this->authorizeCoop($farmer);

        if ($farmer->membership_status !== 'approved') {
            throw ValidationException::withMessages([
                'farmer' => 'Only approved members can be removed from the cooperative.',
            ]);
        }

        $farmer->update([
            'membership_status'     => 'removed',
            'membership_decided_at' => now(),
        ]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'remove_farmer_membership',
            'target_type' => 'farmer_profile',
            'target_id'   => $farmer->id,
            'notes'       => "Farmer {$user->name} removed from the cooperative by coop admin.",
        ]);

        Notification::create([
            'user_id'  => $user->id,
            'title'    => 'Membership ended',
            'message'  => 'Your cooperative membership has been ended by the cooperative admin. You can request to rejoin at any time.',
            'link'     => route('farmer.dashboard'),
            'category' => 'membership',
        ]);

        return back()->with('success', "Farmer {$user->name} removed from your cooperative.");
    }

    /**
     * Add Farmer (3.4) — the cooperative admin creates a farmer account
     * directly, bypassing the self-serve request/approve queue above.
     */
    public function create()
    {
        return view('coop.farmers.create');
    }

    public function store(Request $request)
    {
        $cooperativeId = Auth::user()?->cooperative_id;

        if (! $cooperativeId) {
            abort(403, 'You are not an active cooperative admin.');
        }

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|max:255|unique:users,email',
            'password'      => 'required|string|min:8',
            'phone'         => 'nullable|string|max:20',
            'farm_location' => 'nullable|string|max:255',
            'latitude'      => 'nullable|numeric|between:-90,90',
            'longitude'     => 'nullable|numeric|between:-180,180',
        ]);

        $user = DB::transaction(function () use ($data, $cooperativeId) {
            $user = User::create([
                'name'              => $data['name'],
                'email'             => strtolower($data['email']),
                'password'          => Hash::make($data['password']),
                'role'              => UserRole::FARMER->value,
                'status'            => 'active',
                'phone'             => $data['phone'] ?? null,
                'affiliation_type'  => 'cooperative',
                'cooperative_id'    => $cooperativeId,
                'email_verified_at' => now(),
            ]);

            FarmerProfile::create([
                'user_id'               => $user->id,
                'phone'                 => $data['phone'] ?? null,
                'farm_location'         => $data['farm_location'] ?? null,
                'latitude'              => $data['latitude'] ?? null,
                'longitude'             => $data['longitude'] ?? null,
                'affiliation_type'      => 'cooperative',
                'cooperative_id'        => $cooperativeId,
                'membership_status'     => 'approved',
                'membership_requested_at' => now(),
                'membership_decided_at'   => now(),
                'is_verified'           => true,
            ]);

            return $user;
        });

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'create_farmer',
            'target_type' => 'user',
            'target_id'   => $user->id,
            'notes'       => "Farmer {$user->name} added directly to the cooperative by coop admin.",
        ]);

        return redirect()->route('coop.farmers.show', $user)->with('success', "{$user->name} was added to your cooperative.");
    }

    /**
     * Farmer Details (3.4) — profile plus haul and procurement history,
     * scoped to this cooperative.
     */
    public function show(User $user)
    {
        $farmer = $this->resolveFarmer($user);
        $this->authorizeCoop($farmer);

        $cooperativeId = $farmer->cooperative_id;

        $haulRequests = HaulRequest::forCooperative($cooperativeId)
            ->where('farmer_id', $user->id)
            ->with(['crop', 'cropVariety', 'packagingType'])
            ->latest('created_at')
            ->get();

        $receivingRecords = ReceivingRecord::where('cooperative_id', $cooperativeId)
            ->where('farmer_id', $user->id)
            ->where('status', ReceivingRecord::STATUS_CONFIRMED)
            ->with(['crop', 'cropGrade'])
            ->latest('confirmed_at')
            ->get();

        return view('coop.farmers.show', compact('user', 'farmer', 'haulRequests', 'receivingRecords'));
    }

    /**
     * Edit Farmer (3.4).
     */
    public function edit(User $user)
    {
        $farmer = $this->resolveFarmer($user);
        $this->authorizeCoop($farmer);

        return view('coop.farmers.edit', compact('user', 'farmer'));
    }

    public function update(Request $request, User $user)
    {
        $farmer = $this->resolveFarmer($user);
        $this->authorizeCoop($farmer);

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'         => 'nullable|string|max:20',
            'farm_location' => 'nullable|string|max:255',
            'latitude'      => 'nullable|numeric|between:-90,90',
            'longitude'     => 'nullable|numeric|between:-180,180',
        ]);

        $user->update([
            'name'  => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
        ]);

        $farmer->update([
            'phone'         => $data['phone'] ?? null,
            'farm_location' => $data['farm_location'] ?? $farmer->farm_location,
            'latitude'      => $data['latitude'] ?? $farmer->latitude,
            'longitude'     => $data['longitude'] ?? $farmer->longitude,
        ]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'update_farmer',
            'target_type' => 'user',
            'target_id'   => $user->id,
            'notes'       => "Farmer {$user->name}'s details updated by coop admin.",
        ]);

        return redirect()->route('coop.farmers.show', $user)->with('success', "{$user->name}'s details were updated.");
    }

    /**
     * Pull the farmer profile for a user, tied to a cooperative.
     */
    private function resolveFarmer(User $user): FarmerProfile
    {
        return FarmerProfile::where('user_id', $user->id)
            ->whereNotNull('cooperative_id')
            ->firstOrFail();
    }

    /**
     * Ensure the farmer belongs to the signed-in coop admin's cooperative.
     */
    private function authorizeCoop(FarmerProfile $farmer): void
    {
        if ($farmer->cooperative_id !== Auth::user()?->cooperative_id) {
            abort(403, 'This farmer is not a member of your cooperative.');
        }
    }

    /**
     * Guard: only pending membership requests may be decided.
     */
    private function assertAwaiting(FarmerProfile $farmer): void
    {
        if ($farmer->membership_status !== 'pending') {
            throw ValidationException::withMessages([
                'farmer' => 'This farmer is not awaiting a membership decision.',
            ]);
        }
    }
}
