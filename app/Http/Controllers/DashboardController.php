<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the central dashboard.
     * The Blade view handles the role-based UI switching.
     */
    public function index()
    {
        // We're keeping this simple for now to focus on Registration testing.
        // No data fetching yet—just loading the view.
        return view('dashboard');
    }
}
