<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RouteOptimizationController extends Controller
{
    public function index()
    {
        // Security check: Only let logistics partners see this page
        if (Auth::user()->role !== 'logistics_partner') {
            abort(403, 'Unauthorized action.');
        }

        // Fetch the farmers with valid coordinates
        // 1. Have valid GPS coordinates on their profile
        // 2. Have at least one active harvest listing requesting pickup
        $farmers = User::where('role', 'farmer')
            ->whereHas('farmerProfile', function($query) {
                $query->whereNotNull('latitude')
                      ->whereNotNull('longitude');
            })
            ->whereHas('harvests', function($query) {
                $query->where('status', 'active');
            })
            ->with([
                'farmerProfile',
                'harvests' => fn($query) => $query->where('status', 'active'),
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
                    'crop'     => $h->crop_type,
                    'quantity' => $h->quantity_kg,
                    'status'   => $h->status,
                ];
            })->values(),
        ];
    });

        return view('dashboards.route-optimization', compact('farmersData'));
    }
}
