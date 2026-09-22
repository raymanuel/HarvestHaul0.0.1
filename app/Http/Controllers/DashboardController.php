<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Route each role to its own workspace. Kept as a single switcher so
     * shared links can always point at /dashboard.
     */
    public function index()
    {
        $user = Auth::user();

        $route = match ($user->role) {
            'super_admin' => 'admin.dashboard',
            'coop_admin' => 'coop.dashboard',
            'field_receiving' => 'field.dashboard',
            'delivery_personnel' => 'delivery.dashboard',
            'farmer' => 'farmer.dashboard',
            'buyer' => 'buyer.dashboard',
            default => null,
        };

        if (! $route) {
            abort(403);
        }

        return redirect()->route($route);
    }
}