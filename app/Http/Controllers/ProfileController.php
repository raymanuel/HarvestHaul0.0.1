<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\LogisticsProfile;

class ProfileController extends Controller
{
    /**
     * Display the profile page for the authenticated user.
     * Routes to role-specific view with pre-populated data.
     */
    public function show()
    {
        $user = Auth::user();

        return match ($user->role) {
            'farmer' => view('farmers.profile-farmer', [
                'user' => $user,
                'profile' => $user->farmerProfile,
                'cooperatives' => LogisticsProfile::where('logistics_type', 'cooperative')
                    ->with('user')
                    ->get(),
            ]),

            'logistics_partner' => view('logistics.profile-logistics', [
                'user' => $user,
                'profile' => $user->logisticsProfile,
            ]),

            default => redirect()->route('dashboard'),
        };
    }

    /**
     * Update account details and role-specific profile fields.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // ── Shared user-table validation ──
        $userRules = [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ];

        // ── Role-specific profile rules ──
        $profileRules = [];
        $profileData  = [];

        if ($user->role === 'farmer' && $user->farmerProfile) {
            $profileRules = [
                'phone'          => ['nullable', 'string', 'max:20'],
                'farm_location'  => ['nullable', 'string', 'max:500'],
                'latitude'       => ['nullable', 'numeric', 'between:-90,90'],
                'longitude'      => ['nullable', 'numeric', 'between:-180,180'],
                'cooperative_id' => ['nullable', 'exists:logistics_profiles,id'],
            ];

            $validated = $request->validate(array_merge($userRules, $profileRules));

            $profileData = [
                'phone'         => $validated['phone'] ?? $user->farmerProfile->phone,
                'farm_location' => $validated['farm_location'] ?? $user->farmerProfile->farm_location,
                'latitude'      => $validated['latitude'] ?? $user->farmerProfile->latitude,
                'longitude'     => $validated['longitude'] ?? $user->farmerProfile->longitude,
            ];

            // Only allow cooperative_id changes if farmer is a coop member
            if ($user->farmerProfile->affiliation_type === 'cooperative') {
                $profileData['cooperative_id'] = $validated['cooperative_id'] ?? $user->farmerProfile->cooperative_id;
            }

            $user->farmerProfile->update($profileData);

        } elseif ($user->role === 'logistics_partner' && $user->logisticsProfile) {
            $profileRules = [
                'phone'               => ['nullable', 'string', 'max:20'],
                'company_name'        => ['required', 'string', 'max:255'],
                'business_permit_no'  => ['nullable', 'string', 'max:100'],
                'cda_registration_no' => ['nullable', 'string', 'max:100'],
            ];

            $validated = $request->validate(array_merge($userRules, $profileRules));

            $profileData = [
                'phone'              => $validated['phone'] ?? $user->logisticsProfile->phone,
                'company_name'       => $validated['company_name'],
                'business_permit_no' => $validated['business_permit_no'] ?? $user->logisticsProfile->business_permit_no,
            ];

            // Only save CDA reg if cooperative type
            if ($user->logisticsProfile->logistics_type === 'cooperative') {
                $profileData['cda_registration_no'] = $validated['cda_registration_no'] ?? $user->logisticsProfile->cda_registration_no;
            }

            $user->logisticsProfile->update($profileData);

        } else {
            $validated = $request->validate($userRules);
        }

        // ── Update user table ──
        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the user's password. Requires current password confirmation.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $user->update([
            'password' => $request->password,
        ]);

        return back()->with('password_success', 'Password changed successfully.');
    }
}
