<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Harvest;

class LogisticsController extends Controller
{
    private function authorizeLogistics(): void
    {
        if (Auth::user()->role !== 'logistics_partner') {
            abort(403, 'Unauthorized action.');
        }
    }

    private function isVerifiedLogistics(): bool
    {
        return (bool) Auth::user()->logisticsProfile?->is_verified;
    }

    // -------------------------------------------------------
    // dashboard — logistics partner overview
    // -------------------------------------------------------
    public function index()
    {
        $this->authorizeLogistics();

        $activeHarvestCount = Harvest::where('status', 'active')->count();

        return view('dashboards.logistics-view', compact('activeHarvestCount'));
    }
}
