<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TruckController extends Controller
{
    /**
     * Truck fleet registry (3B).
     *
     * The cooperative's trucks are the capacity-bounded constraint the
     * pickup consolidation engine (§ load-bounds) consumes later. Coop
     * admins manage the fleet here, scoped to their own cooperative.
     */
    public function index()
    {
        $cooperativeId = $this->cooperativeId();

        $trucks = Truck::with(['driver'])
            ->where('cooperative_id', $cooperativeId)
            ->orderByDesc('created_at')
            ->get();

        $totals = [
            'count'     => $trucks->count(),
            'capacity'  => $trucks->sum('capacity_kg'),
            'available' => $trucks->where('status', 'available')->count(),
        ];

        $drivers = User::where('role', UserRole::DELIVERY_PERSONNEL->value)
            ->where('cooperative_id', $cooperativeId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return view('coop.trucks.index', compact('trucks', 'totals', 'drivers'));
    }

    /**
     * Persist a new truck in the cooperative's fleet.
     */
    public function store(Request $request)
    {
        $cooperativeId = $this->cooperativeId();

        $data = $request->validate([
            'plate_number'            => 'required|string|max:25',
            'truck_name'              => 'required|string|max:255',
            'vehicle_type'            => 'required|string|max:255',
            'capacity_kg'             => 'required|numeric|min:1',
            'capacity_volume_cubic_m' => 'required|numeric|min:0.01',
            'status'                  => 'required|in:available,in_use,maintenance',
            'driver_id'               => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where('cooperative_id', $cooperativeId)
                    ->where('role', UserRole::DELIVERY_PERSONNEL->value),
            ],
            'notes'                   => 'nullable|string|max:1000',
        ]);

        $truck = Truck::create(array_merge($data, [
            'cooperative_id' => $cooperativeId,
        ]));

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'create_truck',
            'target_type' => 'truck',
            'target_id'   => $truck->id,
            'notes'       => "Truck {$data['plate_number']} ({$data['truck_name']}) added to the fleet.",
        ]);

        return back()->with('success', 'Truck added to the fleet.');
    }

    /**
     * Update an existing truck.
     */
    public function update(Request $request, Truck $truck)
    {
        $this->authorizeCoop($truck);

        $data = $request->validate([
            'plate_number'            => 'required|string|max:25',
            'truck_name'              => 'required|string|max:255',
            'vehicle_type'            => 'required|string|max:255',
            'capacity_kg'             => 'required|numeric|min:1',
            'capacity_volume_cubic_m' => 'required|numeric|min:0.01',
            'status'                  => 'required|in:available,in_use,maintenance',
            'driver_id'               => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where('cooperative_id', $truck->cooperative_id)
                    ->where('role', UserRole::DELIVERY_PERSONNEL->value),
            ],
            'notes'                   => 'nullable|string|max:1000',
        ]);

        $truck->update($data);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'update_truck',
            'target_type' => 'truck',
            'target_id'   => $truck->id,
            'notes'       => "Truck {$data['plate_number']} updated.",
        ]);

        return back()->with('success', 'Truck updated.');
    }

    /**
     * Toggle a truck between available / in_use / maintenance.
     */
    public function toggleStatus(Truck $truck, string $status)
    {
        $this->authorizeCoop($truck);

        if (! in_array($status, ['available', 'in_use', 'maintenance'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Unsupported truck status.',
            ]);
        }

        $truck->update(['status' => $status]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'toggle_truck_status',
            'target_type' => 'truck',
            'target_id'   => $truck->id,
            'notes'       => "Truck set to {$status}.",
        ]);

        return back()->with('success', "Truck marked {$status}.");
    }

    /**
     * Remove a truck from the fleet registry.
     */
    public function destroy(Truck $truck)
    {
        $this->authorizeCoop($truck);

        $plate = $truck->plate_number;
        $truck->delete();

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'delete_truck',
            'target_type' => 'truck',
            'target_id'   => $truck->id,
            'notes'       => "Truck {$plate} removed from the fleet.",
        ]);

        return back()->with('success', 'Truck removed from the fleet.');
    }

    /**
     * Cooperative id for the signed-in coop admin.
     */
    private function cooperativeId(): int
    {
        $cooperativeId = Auth::user()?->cooperative_id;

        if (! $cooperativeId) {
            abort(403, 'You are not an active cooperative admin.');
        }

        return $cooperativeId;
    }

    /**
     * Ensure the truck belongs to the signed-in coop admin's cooperative.
     */
    private function authorizeCoop(Truck $truck): void
    {
        if ($truck->cooperative_id !== $this->cooperativeId()) {
            abort(403, 'This truck is not in your cooperative\'s fleet.');
        }
    }
}
