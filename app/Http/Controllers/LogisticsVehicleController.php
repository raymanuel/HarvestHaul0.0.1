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
    public function index()
    {
        $partnerId = Auth::user()->logisticsProfile->id;
        $vehicles = Truck::where('logistics_profile_id', $partnerId)->with('driver')->get();

        return view('logistics.vehicles.index', compact('vehicles'));
    }

    public function create()
    {
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
        $partnerId = Auth::user()->logisticsProfile->id;

        $request->validate([
            'truck_name'   => ['required', 'string', 'max:255'],
            'plate_number' => ['required', 'string', 'max:50', 'unique:trucks,plate_number'],
            'vehicle_type' => ['required', 'string', 'max:55'],
            'capacity_kg'  => ['required', 'numeric', 'min:0'],
            'capacity_volume_cubic_m' => ['nullable', 'numeric', 'min:0'],
            'driver_id'    => ['nullable', 'exists:users,id', function ($attribute, $value, $fail) use ($partnerId) {
                $ownsDriver = DriverProfile::where('user_id', $value)
                    ->where('partner_id', $partnerId)
                    ->exists();

                if (!$ownsDriver) {
                    $fail('The selected driver does not belong to your fleet.');
                }
            }],
            'status'       => ['required', 'in:available,in_transit,maintenance'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $truck = Truck::create([
            'logistics_profile_id' => $partnerId,
            'driver_id'            => $request->driver_id,
            'truck_name'           => $request->truck_name,
            'plate_number'         => $request->plate_number,
            'vehicle_type'         => $request->vehicle_type,
            'capacity_kg'          => $request->capacity_kg,
            'capacity_volume_cubic_m' => $request->capacity_volume_cubic_m ?? null,
            'status'               => $request->status,
            'notes'                => $request->notes,
        ]);

        \App\Models\AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'created_vehicle',
            'target_type' => 'vehicle',
            'target_id'   => $truck->id,
            'notes'       => "Logistics Partner " . Auth::user()->name . " registered vehicle {$truck->truck_name} (Plate: {$truck->plate_number}, Capacity: {$truck->capacity_kg} kg).",
        ]);

        return redirect()->route('logistics.vehicles.index')->with('success', 'Vehicle registered successfully in your fleet.');
    }
}
