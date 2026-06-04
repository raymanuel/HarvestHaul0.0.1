<?php

namespace App\Http\Controllers;

use App\Models\Truck;
use App\Models\User;
use App\Models\DriverProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class LogisticsVehicleController
 * 
 * Handles vehicle (truck) onboarding and management for logistics partners.
 * Logistics partners register trucks, set capacity boundaries, and link drivers.
 */
class LogisticsVehicleController extends Controller
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
        $vehicles = Truck::where('logistics_profile_id', $partnerId)->with('driver')->get();

        return view('logistics.vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        $this->authorizeLogistics();

        $partnerId = Auth::user()->logisticsProfile->id;
        // Fetch all driver user accounts belonging to this partner
        $driverProfiles = DriverProfile::where('partner_id', $partnerId)->with('user')->get();
        $drivers = $driverProfiles->map(function ($profile) {
            return $profile->user;
        })->filter();

        return view('logistics.vehicles.create', compact('drivers'));
    }

    public function store(Request $request)
    {
        $this->authorizeLogistics();

        $request->validate([
            'truck_name'   => ['required', 'string', 'max:255'],
            'plate_number' => ['required', 'string', 'max:50', 'unique:trucks,plate_number'],
            'vehicle_type' => ['required', 'string', 'max:55'],
            'capacity_kg'  => ['required', 'numeric', 'min:0'],
            'driver_id'    => ['nullable', 'exists:users,id'],
            'status'       => ['required', 'in:available,in_transit,maintenance'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        Truck::create([
            'logistics_profile_id' => Auth::user()->logisticsProfile->id,
            'driver_id'            => $request->driver_id,
            'truck_name'           => $request->truck_name,
            'plate_number'         => $request->plate_number,
            'vehicle_type'         => $request->vehicle_type,
            'capacity_kg'          => $request->capacity_kg,
            'status'               => $request->status,
            'notes'                => $request->notes,
        ]);

        return redirect()->route('logistics.vehicles.index')->with('success', 'Vehicle registered successfully in your fleet.');
    }
}
