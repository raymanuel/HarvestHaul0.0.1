<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Harvest;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Initialize default counter fallback metrics
        $activeHarvestCount = 0;

        /**
         * Safely isolate metrics for verified logistics coordinators.
         * Utilizes direct column tracking arrays to bypass missing model inverse relationships.
         */
        if ($user->role === 'logistics_partner' && $logisticsProfile = $user->logisticsProfile) {

            // Collect the exact primary key IDs of scoped farmers matching visibility criteria
            $farmerIds = User::where('role', 'farmer')
                ->whereHas('farmerProfile', function ($query) use ($logisticsProfile) {
                    $query->where('is_verified', true);

                    if ($logisticsProfile->logistics_type === 'cooperative') {
                        $query->where('affiliation_type', 'cooperative')
                              ->where('cooperative_id', $logisticsProfile->id);
                    } elseif ($logisticsProfile->logistics_type === 'company') {
                        $query->where('affiliation_type', 'independent');
                    }
                })
                ->pluck('id');

            // Count active harvests using native column mapping constraints directly
            $activeHarvestCount = Harvest::where('status', 'active')
                ->whereIn('user_id', $farmerIds)
                ->count();
        }

        return match($user->role) {
            'farmer' => view('dashboards.farmer-view', [
                'activeListings' => $user->harvests()->where('status', 'active')->get(),
                'activeCount'    => $user->harvests()->where('status', 'active')->count(),
            ]),

            'logistics_partner' => view('dashboards.logistics-view', [
                'activeHarvestCount' => $activeHarvestCount,
            ]),

            'admin'  => app(AdminController::class)->index(),
            'driver' => view('dashboards.driver-view'),
            default  => abort(403),
        };
    }
}
