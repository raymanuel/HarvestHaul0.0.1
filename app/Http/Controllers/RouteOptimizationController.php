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
                                                   ->with(['crop', 'cropVariety']),
            ])
            ->get();

        $farmersData = $farmers->map(function ($f) {
            return [
                'name'           => $f->name,
                'farmer_profile' => [
                    'latitude'      => $f->farmerProfile->latitude,
                    'longitude'     => $f->farmerProfile->longitude,
                    'farm_location' => $f->farmerProfile->farm_location,
                ],
                'harvests' => $f->harvests->map(function ($h) {
                    return [
                        'crop'     => $h->crop->name ?? $h->crop_type ?? '—',
                        'variety'  => $h->cropVariety->name ?? $h->variety ?? '—',
                        'quantity' => $h->quantity_kg,
                        'status'   => $h->status,
                    ];
                })->values(),
            ];
        });

        return view('dashboards.route-optimization', compact('farmersData'));
    }
}
