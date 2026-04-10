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

        //  Handle all other roles normally
        return match($user->role) {
            'farmer'            => view('dashboards.farmer-view', [
                'activeListings' => $user->harvests()->where('status', 'active')->get(),
                'activeCount'    => $user->harvests()->where('status', 'active')->count(),
            ]),
            'logistics_partner' => view('dashboards.logistics-view'),
            'admin'             => view('dashboards.admin-view'),
            'driver'             => view('dashboards.driver-view'),
            default             => abort(403),
        };
    }


}
