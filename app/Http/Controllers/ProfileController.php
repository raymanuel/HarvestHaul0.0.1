<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
        ]);

        return back()->with('success', 'Your profile details were saved.');
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
        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_label' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        if ($user->isFarmer()) {
            $profile = $user->farmerProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'farm_location' => $data['location_label'] ?? $user->farmerProfile?->farm_location,
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
        }

        return back()->with('success', 'Your location was saved.');
    }
}