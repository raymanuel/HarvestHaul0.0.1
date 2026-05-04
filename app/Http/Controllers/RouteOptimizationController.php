<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RouteOptimizationController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'logistics_partner') {
            abort(403, 'Unauthorized action.');
        }

        if (!Auth::user()->logisticsProfile?->is_verified) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Your account is pending verification. Route optimization is not available until approved by an administrator.');
        }

        $logisticsProfile = Auth::user()->logisticsProfile;

        $farmers = User::where('role', 'farmer')
            ->whereHas('farmerProfile', function ($query) {
                $query->whereNotNull('latitude')
                      ->whereNotNull('longitude');
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

        // Available trucks belonging to this logistics partner
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

        return view('dashboards.route-optimization', compact('farmersData', 'trucks'));
    }
}
