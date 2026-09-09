<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use App\Mail\SendOtpMail;
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

            'buyer' => view('buyer.profile-buyer', [
                'user'    => $user,
                'profile' => $user->buyerProfile,
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
            'current_password' => ['required', 'string'],
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
                'cooperative_id' => ['nullable', Rule::exists('logistics_profiles', 'id')
                    ->where('logistics_type', 'cooperative')
                    ->where('is_verified', true)],
            ];

            $validated = $request->validate(array_merge($userRules, $profileRules));

            $profileData = [
                'phone'         => $validated['phone'] ?? $user->farmerProfile->phone,
                'farm_location' => $validated['farm_location'] ?? $user->farmerProfile->farm_location,
                'latitude'      => $validated['latitude'] ?? $user->farmerProfile->latitude,
                'longitude'     => $validated['longitude'] ?? $user->farmerProfile->longitude,
            ];

            // Cooperative membership is approved by administrators. Farmers may only
            // join a cooperative once (independent → cooperative) but cannot switch
            // cooperatives through self-service.
            if ($user->farmerProfile->affiliation_type === 'cooperative') {
                $submittedCoop = $validated['cooperative_id'] ?? null;
                $currentCoop   = $user->farmerProfile->cooperative_id;

                if ($submittedCoop && $currentCoop && (int) $submittedCoop !== (int) $currentCoop) {
                    return back()->with('error', 'You cannot switch cooperatives yourself. Contact your cooperative administrator to change your affiliation.');
                }

                $profileData['cooperative_id'] = $submittedCoop ?? $currentCoop;
            }

            $user->farmerProfile->update($profileData);

            // Sync users table cooperative_id with farmer_profiles
            $user->update([
                'cooperative_id' => $user->farmerProfile->cooperative_id,
            ]);

        } elseif ($user->role === 'logistics_partner' && $user->logisticsProfile) {
            $profileRules = [
                'phone'               => ['required', 'string', 'max:20'],
                'company_name'        => ['required', 'string', 'max:255'],
                'business_permit_no'  => ['nullable', 'string', 'max:100'],
                'cda_registration_no' => ['nullable', 'string', 'max:100'],
                'office_address'      => ['nullable', 'string', 'max:500'],
                'latitude'            => ['nullable', 'numeric', 'between:-90,90'],
                'longitude'           => ['nullable', 'numeric', 'between:-180,180'],
            ];

            $validated = $request->validate(array_merge($userRules, $profileRules));

            $profileData = [
                'phone'              => $validated['phone'] ?? $user->logisticsProfile->phone,
                'company_name'       => $validated['company_name'],
                'business_permit_no' => $validated['business_permit_no'] ?? $user->logisticsProfile->business_permit_no,
                'office_address'     => $validated['office_address'] ?? $user->logisticsProfile->office_address,
                'latitude'           => $validated['latitude'] ?? $user->logisticsProfile->latitude,
                'longitude'          => $validated['longitude'] ?? $user->logisticsProfile->longitude,
            ];

            // Only save CDA reg if cooperative type
            if ($user->logisticsProfile->logistics_type === 'cooperative') {
                $profileData['cda_registration_no'] = $validated['cda_registration_no'] ?? $user->logisticsProfile->cda_registration_no;
            }

            $user->logisticsProfile->update($profileData);

        } elseif ($user->role === 'buyer') {
            $profileRules = [
                'phone' => ['nullable', 'string', 'max:20'],
            ];

            $validated = $request->validate(array_merge($userRules, $profileRules));

            if ($user->buyerProfile) {
                $user->buyerProfile->update([
                    'phone' => $validated['phone'] ?? null,
                ]);
            }

        } else {
            $validated = $request->validate($userRules);
        }

        // ── Confirm identity before applying changes ──
        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.'])->withInput();
        }

        // ── Track email change before updating ──
        $emailChanged = $validated['email'] !== $user->email;

        // ── Update user table ──
        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        // ── If email changed, reset verification and send OTP ──
        if ($emailChanged) {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->forceFill([
                'email_verified_at' => null,
                'email_otp' => $otp,
                'email_otp_expires_at' => now()->addMinutes(10),
            ])->save();

            Mail::to($user->email)->send(new SendOtpMail($otp, $user->name));

            return redirect()->route('verification.notice')
                ->with('status', 'email-changed');
        }

        if (session('profile_complete')) {
            session()->forget('profile_complete');
            return redirect()->route('dashboard')->with('success', 'Profile completed! Welcome to HarvestHaul.');
        }

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

        // Invalidate the session and regenerate the CSRF token so the old
        // session cannot be replayed after the credential change.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Auth::login($user);

        return back()->with('password_success', 'Password changed successfully.');
    }

    /**
     * Save location from the location picker popup (AJAX endpoint).
     */
    public function saveLocation(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'farm_location' => ['nullable', 'string', 'max:500'],
        ]);

        if ($user->role === 'farmer' && $user->farmerProfile) {
            $user->farmerProfile->update($validated);
        } elseif ($user->role === 'logistics_partner' && $user->logisticsProfile) {
            $user->logisticsProfile->update($validated);
        } elseif ($user->role === 'buyer' && $user->buyerProfile) {
            $user->buyerProfile->update($validated);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Show the route pricing settings page for logistics partners.
     */
    public function showRoutePricing()
    {
        $user = Auth::user();

        if ($user->role !== 'logistics_partner' || !$user->logisticsProfile) {
            return redirect()->route('dashboard');
        }

        return view('logistics.route-pricing', [
            'user'    => $user,
            'profile' => $user->logisticsProfile,
        ]);
    }

    /**
     * Update the default hauling rate for a logistics partner.
     */
    public function updateRoutePricing(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'logistics_partner' || !$user->logisticsProfile) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'default_hauling_rate' => ['required', 'numeric', 'min:0.10', 'max:99.99'],
        ]);

        $user->logisticsProfile->update([
            'default_hauling_rate' => $validated['default_hauling_rate'],
        ]);

        return back()->with('success', 'Default hauling rate updated.');
    }
}
