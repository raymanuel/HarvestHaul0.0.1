<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\HarvestStatus;
use App\Models\PoolingJob;
use App\Models\Truck;
use App\Services\DriverAssignmentService;
use Illuminate\Support\Facades\Auth;

class RouteOptimizationController extends Controller
{
    public function index()
    {
        // Enforce role permission guard for logistics partners / coordinators
        if (Auth::user()->role !== 'logistics_partner') {
            abort(403, 'Unauthorized action.');
        }

        // Verify registration status before granting system mapping interface visibility
        if (!Auth::user()->logisticsProfile?->is_verified) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Your account is pending verification. Route optimization is not available until approved by an administrator.');
        }

        $logisticsProfile = Auth::user()->logisticsProfile;

        // Fetch farmers with strict local visibility parameters applied at the query backbone
        $farmers = User::where('role', 'farmer')
            ->whereHas('farmerProfile', function ($query) use ($logisticsProfile) {
                // Base geographic coordinates validation requirement
                $query->whereNotNull('latitude')
                      ->whereNotNull('longitude')
                      ->where('is_verified', true); // Protect system against unvetted posts

                /**
                 * Visibility Scoping Condition 1: Cooperative Scoping
                 * Member farmers are strictly visible only to their respective cooperative group.
                 */
                if ($logisticsProfile->logistics_type === 'cooperative') {
                    $query->where('affiliation_type', 'cooperative')
                          ->where('cooperative_id', $logisticsProfile->id);
                }
                /**
                 * Visibility Scoping Condition 2: Commercial Company Scoping
                 * Independent farmers are visible exclusively to private commercial fleet corporations.
                 */
                elseif ($logisticsProfile->logistics_type === 'company') {
                    $query->where('affiliation_type', 'independent');
                }
            })
            ->whereHas('harvests', function ($query) {
                $query->whereIn('status', HarvestStatus::logisticsVisible());
            })
            ->with([
                'farmerProfile',
                'harvests' => fn($query) => $query->whereIn('status', HarvestStatus::logisticsVisible())
                                                   ->with(['crop', 'cropVariety', 'destination']),
            ])
            ->get();

        // Map collection data structures cleanly for the interactive Leaflet mapping view
        $farmersData = $farmers->map(function ($f) {
            $firstHarvest = $f->harvests->first();
            return [
                'name'           => $f->name,
                'farmer_profile' => [
                    'latitude'      => $f->farmerProfile->latitude,
                    'longitude'     => $f->farmerProfile->longitude,
                    'farm_location' => $f->farmerProfile->farm_location,
                ],
                'harvests' => $f->harvests->map(function ($h) {
                    return [
                        'id'       => $h->id,
                        'crop'     => $h->crop->name ?? $h->crop_type ?? '—',
                        'variety'  => $h->cropVariety->name ?? $h->variety ?? '—',
                        'quantity' => $h->quantity_kg,
                        'status'   => $h->status,
                        'destination'           => $h->destination ? [
                            'name'    => $h->destination->name,
                            'address' => $h->destination->address,
                        ] : null,
                        'destination_address'   => $h->destination_address,
                        'destination_latitude'  => $h->destination_latitude,
                        'destination_longitude' => $h->destination_longitude,
                    ];
                })->values(),
                'destination'           => $firstHarvest?->destination ? [
                    'name'    => $firstHarvest->destination->name,
                    'address' => $firstHarvest->destination->address,
                ] : null,
                'destination_address'   => $firstHarvest?->destination_address,
                'destination_latitude'  => $firstHarvest?->destination_latitude,
                'destination_longitude' => $firstHarvest?->destination_longitude,
            ];
        });

        // Query available fleet assets managed by this logistics coordinator
        $allTrucks = $logisticsProfile->trucks()
            ->where('status', 'available')
            ->with('driver.driverProfile')
            ->get();

        $trucks = $allTrucks->map(fn($t) => [
            'id'          => $t->id,
            'label'       => $t->truck_name . ' — ' . $t->plate_number,
            'capacity_kg' => $t->capacity_kg,
            'driver'      => $t->driver?->name ?? 'No driver assigned',
        ]);

        // ─── Auto-suggest the best available driver+truck combo ───
        $suggestedTruckId = null;

        // Preload active job counts for all drivers in one query
        $driverIds = $allTrucks->pluck('driver_id')->filter()->unique();
        $activeJobCounts = PoolingJob::whereIn('driver_id', $driverIds)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->groupBy('driver_id')
            ->selectRaw('driver_id, COUNT(*) as total')
            ->pluck('total', 'driver_id');

        $availableTrucks = $allTrucks
            ->filter(function ($truck) {
                return $truck->driver && $truck->driver->driverProfile
                    && $truck->driver->driverProfile->employment_status === 'active';
            })
            ->sortBy(function ($truck) use ($activeJobCounts) {
                $activeJobs = $activeJobCounts->get($truck->driver_id, 0);
                $lastAssigned = $truck->driver->driverProfile->last_assigned_at;
                return [$activeJobs, $lastAssigned ?? '0000-00-00'];
            });

        $suggestedTruck = $availableTrucks->first();
        if ($suggestedTruck) {
            $suggestedTruckId = $suggestedTruck->id;
        }

        // Nearest available driver auto-suggestion
        $nearestDriver = null;
        $firstFarmer = $farmers->first();
        if ($firstFarmer && $firstFarmer->farmerProfile) {
            $assignmentService = app(DriverAssignmentService::class);
            $nearestDriver = $assignmentService->findNearestAvailableDriver(
                (float) $firstFarmer->farmerProfile->latitude,
                (float) $firstFarmer->farmerProfile->longitude,
                $logisticsProfile->id
            );
        }

        return view('logistics.route-optimization', compact(
            'farmersData', 'trucks', 'suggestedTruckId', 'nearestDriver'
        ));
    }

    /**
     * Auto-assign the nearest available driver to a truck.
     */
    public function autoAssignDriver(Request $request)
    {
        if (Auth::user()->role !== 'logistics_partner') {
            abort(403);
        }

        $logisticsProfile = Auth::user()->logisticsProfile;

        $request->validate([
            'truck_id' => 'required|integer|exists:trucks,id',
            'pickup_lat' => 'required|numeric',
            'pickup_lng' => 'required|numeric',
        ]);

        $truck = Truck::where('id', $request->truck_id)
            ->where('logistics_profile_id', $logisticsProfile->id)
            ->firstOrFail();

        $assignmentService = app(DriverAssignmentService::class);
        $result = $assignmentService->findNearestAvailableDriver(
            (float) $request->pickup_lat,
            (float) $request->pickup_lng,
            $logisticsProfile->id
        );

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'No available drivers found within range.',
            ]);
        }

        $assignmentService->assignDriver($truck, $result['driver']->id);

        return response()->json([
            'success' => true,
            'message' => "Driver {$result['driver']->name} auto-assigned (distance: {$result['distance_km']} km).",
            'driver' => [
                'id' => $result['driver']->id,
                'name' => $result['driver']->name,
                'distance_km' => $result['distance_km'],
            ],
        ]);
    }
}
