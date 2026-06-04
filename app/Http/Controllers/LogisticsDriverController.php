<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DriverProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Class LogisticsDriverController
 * 
 * Handles driver onboarding and profile management for logistics partners.
 * Logistics partners create driver user accounts and link driver profiles to their company.
 */
class LogisticsDriverController extends Controller
{
    /**
     * Helper to verify if user has 'logistics_partner' role and profile is active.
     */
    private function authorizeLogistics(): void
    {
        if (!Auth::check() || Auth::user()->role !== 'logistics_partner') {
            abort(403, 'Unauthorized access.');
        }

        if (!Auth::user()->logisticsProfile?->is_verified) {
            abort(403, 'Your account is pending verification.');
        }
    }

    public function index()
    {
        $this->authorizeLogistics();

        $partnerId = Auth::user()->logisticsProfile->id;
        $drivers = DriverProfile::where('partner_id', $partnerId)->with('user')->get();

        return view('logistics.drivers.index', compact('drivers'));
    }

    public function create()
    {
        $this->authorizeLogistics();

        return view('logistics.drivers.create');
    }

    public function store(Request $request)
    {
        $this->authorizeLogistics();

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'license_number' => ['required', 'string', 'max:50', 'unique:driver_profiles,license_no'],
            'vehicle_type' => ['nullable', 'string', 'max:50'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'driver',
            'status' => 'active',
        ]);

        $user->email_verified_at = now();
        $user->save();

        DriverProfile::create([
            'user_id' => $user->id,
            'partner_id' => Auth::user()->logisticsProfile->id,
            'license_number' => $request->license_number,
            'vehicle_type' => $request->vehicle_type,
            'phone' => $request->phone,
            'status' => 'active',
        ]);

        return redirect()->route('logistics.drivers.index')->with('success', 'Driver account created successfully.');
    }
}
