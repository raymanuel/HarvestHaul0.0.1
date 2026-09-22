<?php

namespace App\Http\Controllers;

use App\Mail\SendOtpMail;
use App\Models\AuditLog;
use App\Services\Geocoding\NominatimGeocodingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user()->load('cooperative', 'farmerProfile', 'driverProfile', 'buyerProfile');

        return view('profile.show', compact('user'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
        ]);

        return back()->with('success', 'Your profile details were saved.');
    }

    /**
     * Changing the email is sensitive (it's also the login identifier), so it
     * gets its own password-gated form and — same as at registration — the
     * new address must be re-verified via OTP before the rest of the app
     * unlocks again. The user keeps access to their own profile page in the
     * meantime; profile.* routes sit outside the 'verified' middleware group.
     */
    public function updateEmail(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'password' => 'required|current_password',
        ]);

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'email' => strtolower($data['email']),
            'email_verified_at' => null,
            'email_otp' => $otp,
            'email_otp_expires_at' => now()->addMinutes(10),
        ])->save();

        AuditLog::create([
            'admin_id' => $user->id,
            'action' => 'update_email',
            'target_type' => 'user',
            'target_id' => $user->id,
            'notes' => "User {$user->name} changed their email address and must re-verify it.",
        ]);

        Mail::to($user->email)->send(new SendOtpMail($otp, $user->name));

        return redirect()->route('verification.notice')
            ->with('success', 'Email updated. Enter the code we just sent to verify it.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        return back()->with('success', 'Your password was changed. Use it the next time you sign in.');
    }

    public function saveLocation(Request $request)
    {
        $user = $request->user();

        // Password confirmation is only required to CHANGE an already-saved
        // location, not to set it the first time — a brand-new farmer is
        // forced through this form right after signing up and just typed
        // their password seconds earlier.
        $hasExistingLocation = $this->hasExistingLocation($user);

        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_label' => 'nullable|string|max:255',
            'password' => ['nullable', Rule::requiredIf($hasExistingLocation), 'current_password'],
        ]);

        // A pin drop auto-fills the address, but never clobbers a label the
        // user deliberately typed themselves.
        $geocoded = $data['location_label']
            ?? app(NominatimGeocodingService::class)->reverse((float) $data['latitude'], (float) $data['longitude']);

        if ($user->isFarmer()) {
            $profile = $user->farmerProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'farm_location' => $geocoded ?? $user->farmerProfile?->farm_location,
                ],
            );

            AuditLog::create([
                'admin_id' => $user->id,
                'action' => 'update_location',
                'target_type' => 'farmer_profile',
                'target_id' => $profile->id,
                'notes' => "Farmer {$user->name} updated their pickup location — affects routing for future pickups.",
            ]);
        } elseif ($user->isCoopAdmin() && $user->cooperative) {
            $user->cooperative->update([
                'latitude' => (string) $data['latitude'],
                'longitude' => (string) $data['longitude'],
            ]);

            AuditLog::create([
                'admin_id' => $user->id,
                'action' => 'update_location',
                'target_type' => 'cooperative',
                'target_id' => $user->cooperative->id,
                'notes' => "Cooperative {$user->cooperative->name} location updated by {$user->name}.",
            ]);
        } elseif ($user->isBuyer()) {
            $profile = $user->buyerProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'location_label' => $geocoded ?? $user->buyerProfile?->location_label,
                ],
            );

            AuditLog::create([
                'admin_id' => $user->id,
                'action' => 'update_location',
                'target_type' => 'buyer_profile',
                'target_id' => $profile->id,
                'notes' => "Buyer {$user->name} updated their delivery location.",
            ]);
        }

        return back()->with('success', 'Your location was saved.');
    }

    private function hasExistingLocation($user): bool
    {
        if ($user->isFarmer()) {
            return (bool) $user->farmerProfile?->latitude && $user->farmerProfile?->longitude;
        }

        if ($user->isCoopAdmin()) {
            return (bool) $user->cooperative?->latitude && $user->cooperative?->longitude;
        }

        if ($user->isBuyer()) {
            return (bool) $user->buyerProfile?->latitude && $user->buyerProfile?->longitude;
        }

        return false;
    }
}