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
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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
            'message'  => 'Welcome aboard. Your cooperative has approved your membership, so you can now submit harvest pickup requests from your farmer dashboard.',
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
     * Bulk Farmer Import (3.4) — onboard many existing farmers at once from
     * a CSV, instead of the coop admin filling in one form per farmer.
     */
    public function importForm()
    {
        return view('coop.farmers.import');
    }

    public function template()
    {
        $csv = "name,email,phone,farm_location,latitude,longitude\n"
            ."Juan Dela Cruz,juan.delacruz@example.com,09171234567,Barangay Fatima,6.1164,125.1716\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="farmer-import-template.csv"',
        ]);
    }

    public function import(Request $request)
    {
        $cooperativeId = Auth::user()?->cooperative_id;
        if (! $cooperativeId) {
            abort(403, 'You are not an active cooperative admin.');
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);

            return back()->with('error', 'The uploaded file is empty or not a valid CSV.');
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

        $created = 0;
        $skipped = [];
        $seenEmails = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $row = array_slice(array_pad($row, count($header), null), 0, count($header));
            $data = array_combine($header, $row);

            $name = trim((string) ($data['name'] ?? ''));
            $email = strtolower(trim((string) ($data['email'] ?? '')));
            $phone = trim((string) ($data['phone'] ?? '')) ?: null;
            $farmLocation = trim((string) ($data['farm_location'] ?? '')) ?: null;
            $latitude = is_numeric($data['latitude'] ?? null) ? (float) $data['latitude'] : null;
            $longitude = is_numeric($data['longitude'] ?? null) ? (float) $data['longitude'] : null;

            $error = match (true) {
                $name === '' => 'Missing name.',
                $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) => 'Missing or invalid email.',
                isset($seenEmails[$email]) => 'Duplicate email within this file.',
                User::where('email', $email)->exists() => 'Email already registered.',
                $latitude !== null && ($latitude < -90 || $latitude > 90) => 'Latitude out of range.',
                $longitude !== null && ($longitude < -180 || $longitude > 180) => 'Longitude out of range.',
                default => null,
            };

            if ($error) {
                $skipped[] = ['row' => $rowNumber, 'email' => $email ?: '(none)', 'reason' => $error];

                continue;
            }

            $seenEmails[$email] = true;

            DB::transaction(function () use ($name, $email, $phone, $farmLocation, $latitude, $longitude, $cooperativeId) {
                $user = User::create([
                    'name'              => $name,
                    'email'             => $email,
                    'password'          => Hash::make(Str::random(32)),
                    'role'              => UserRole::FARMER->value,
                    'status'            => 'active',
                    'phone'             => $phone,
                    'affiliation_type'  => 'cooperative',
                    'cooperative_id'    => $cooperativeId,
                    'email_verified_at' => now(),
                ]);

                FarmerProfile::create([
                    'user_id'                 => $user->id,
                    'phone'                   => $phone,
                    'farm_location'           => $farmLocation,
                    'latitude'                => $latitude,
                    'longitude'               => $longitude,
                    'affiliation_type'        => 'cooperative',
                    'cooperative_id'          => $cooperativeId,
                    'membership_status'       => 'approved',
                    'membership_requested_at' => now(),
                    'membership_decided_at'   => now(),
                    'is_verified'             => true,
                ]);

                try {
                    Password::sendResetLink(['email' => $user->email]);
                } catch (\Throwable $e) {
                    // Non-fatal — the farmer record is still created; the coop
                    // admin can trigger "forgot password" for them manually.
                }
            });

            $created++;
        }

        fclose($handle);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'bulk_import_farmers',
            'target_type' => 'cooperative',
            'target_id'   => $cooperativeId,
            'notes'       => "Bulk-imported {$created} farmer(s)".(count($skipped) ? ', '.count($skipped).' row(s) skipped.' : '.'),
        ]);

        return redirect()->route('coop.farmers.import')
            ->with('success', "{$created} farmer(s) imported.".(count($skipped) ? ' '.count($skipped).' row(s) skipped, see details below.' : ''))
            ->with('importSkipped', $skipped);
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
