<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DriverProfile;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DriverManagementController extends Controller
{
    /**
     * Delivery personnel (drivers). Unlike farmers there is no self-serve
     * request queue — an account the coop admin creates here is usable
     * immediately, same as Coop\FarmerManagementController's direct-add path.
     */
    public function index()
    {
        $cooperativeId = Auth::user()?->cooperative_id;

        if (! $cooperativeId) {
            abort(403, 'You are not an active cooperative admin.');
        }

        $drivers = User::with('driverProfile')
            ->where('role', UserRole::DELIVERY_PERSONNEL->value)
            ->where('cooperative_id', $cooperativeId)
            ->orderBy('name')
            ->get();

        return view('coop.drivers.index', compact('drivers'));
    }

    public function create()
    {
        $cooperativeId = Auth::user()?->cooperative_id;

        if (! $cooperativeId) {
            abort(403, 'You are not an active cooperative admin.');
        }

        return view('coop.drivers.create');
    }

    public function store(Request $request)
    {
        $cooperativeId = Auth::user()?->cooperative_id;

        if (! $cooperativeId) {
            abort(403, 'You are not an active cooperative admin.');
        }

        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|max:255|unique:users,email',
            'password'   => 'required|string|min:8',
            'phone'      => 'nullable|string|max:20',
            'license_no' => 'required|string|max:255|unique:driver_profiles,license_no',
        ]);

        $driver = DB::transaction(function () use ($data, $cooperativeId) {
            $driver = User::create([
                'name'              => $data['name'],
                'email'             => strtolower($data['email']),
                'password'          => Hash::make($data['password']),
                'role'              => UserRole::DELIVERY_PERSONNEL->value,
                'status'            => 'active',
                'phone'             => $data['phone'] ?? null,
                'affiliation_type'  => 'cooperative',
                'cooperative_id'    => $cooperativeId,
                'email_verified_at' => now(),
            ]);

            DriverProfile::create([
                'user_id'           => $driver->id,
                'cooperative_id'    => $cooperativeId,
                'phone'             => $data['phone'] ?? null,
                'license_no'        => $data['license_no'],
                'employment_status' => 'active',
            ]);

            return $driver;
        });

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'create_driver',
            'target_type' => 'user',
            'target_id'   => $driver->id,
            'notes'       => "Driver {$driver->name} added directly to the cooperative by coop admin.",
        ]);

        return redirect()->route('coop.drivers.index')->with('success', "{$driver->name} was added as a driver.");
    }
}
