<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
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
                      ->where('is_verified', true); // Protect system against unvetted listings

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
                $query->where('status', 'active');
            })
            ->with([
                'farmerProfile',
                'harvests' => fn($query) => $query->where('status', 'active')
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
        $trucks = $logisticsProfile->trucks()
            ->where('status', 'available')
            ->with('driver')
            ->get()
            ->map(fn($t) => [
                'id'          => $t->id,
                'label'       => $t->truck_name . ' — ' . $t->plate_number,
                'capacity_kg' => $t->capacity_kg,
                'driver'      => $t->driver?->name ?? 'No driver assigned',
            ]);

        return view('logistics.route-optimization', compact('farmersData', 'trucks'));
    }
}
