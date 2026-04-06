<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class DashboardController extends Controller
{

    public function index()
    {
        $user = Auth::user();

        // 1. Intercept the Logistics Partner to inject map data
        if ($user->role === 'logistics_partner') {

            // Fetch only farmers who have valid map coordinates
            $farmers = User::where('role', 'farmer')
                ->whereHas('farmerProfile', function($query) {
                    $query->whereNotNull('latitude')
                          ->whereNotNull('longitude');
                })
                ->with('farmerProfile')
                ->get();

            return view('dashboards.logistics-view', compact('farmers'));
        }

        // 2. Handle all other roles normally
        return match($user->role) {
            'farmer'            => view('dashboards.farmer-view'),
            'logistics_partner' => view('dashboards.logistics-view'),
            'admin'             => view('dashboards.admin-view'),
            'driver'             => view('dashboards.driver-view'),
            default             => abort(403),
        };
    }
}
