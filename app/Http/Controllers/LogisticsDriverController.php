<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DriverProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class LogisticsDriverController
 * 
 * Handles driver onboarding and profile management for logistics partners.
 * Logistics partners create driver user accounts and link driver profiles to their company.
 */
class LogisticsDriverController extends Controller
{
    public function index()
    {

        $partnerId = Auth::user()->logisticsProfile->id;
        $drivers = DriverProfile::where('partner_id', $partnerId)->with('user')->get();

        return view('logistics.drivers.index', compact('drivers'));
    }

    public function create()
    {
        return view('logistics.drivers.create');
    }

    public function store(Request $request)
    {
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
            'password' => $request->password,
            'role' => 'driver',
            'status' => 'active',
        ]);

        $user->email_verified_at = now();
        $user->save();

        DriverProfile::create([
            'user_id' => $user->id,
            'partner_id' => Auth::user()->logisticsProfile->id,
            'license_no' => $request->license_number,
            'vehicle_type' => $request->vehicle_type,
            'phone' => $request->phone,
            'status' => 'active',
        ]);

        \App\Models\AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'created_driver',
            'target_type' => 'driver',
            'target_id'   => $user->id,
            'notes'       => "Logistics Partner " . Auth::user()->name . " created driver account for {$user->name}.",
        ]);

        return redirect()->route('logistics.drivers.index')->with('success', 'Driver account created successfully.');
    }
}
