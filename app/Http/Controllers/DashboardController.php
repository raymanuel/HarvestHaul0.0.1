<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Harvest;
use App\Models\PoolingJob;

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

        /**
         * Driver dashboard metrics.
         * Scoped strictly to jobs assigned to the authenticated driver's user ID.
         */
        $driverJobs      = collect();
        $completedJobs   = 0;

        if ($user->role === 'driver') {
            $driverJobs = PoolingJob::where('driver_id', $user->id)
                ->whereIn('status', ['confirmed', 'in_progress'])
                ->with(['truck', 'harvests.crop', 'harvests.farmer', 'harvests.destination'])
                ->latest()
                ->get();

            $completedJobs = PoolingJob::where('driver_id', $user->id)
                ->where('status', 'completed')
                ->count();
        }

        return match($user->role) {
            'farmer' => view('farmers.farmer-view', [
                // 1. Precise count of active B2B crop inventory
                'activeHarvestsCount' => $user->harvests()->where('status', 'active')->count(),

                // 2. Count of hauls currently in transit for this specific farmer
                'activeShipmentsCount' => PoolingJob::whereHas('harvests', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->where('status', 'in_progress')->count(),

                // 3. Count of multi-party negotiation offers targeting this farmer
                'pendingProposalsCount' => PoolingJob::whereHas('harvests', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->where('status', 'pending')->count(),
            ]),

            'logistics_partner' => view('logistics.logistics-view', [
                'activeHarvestCount' => $activeHarvestCount,
            ]),

            'admin'  => app(AdminController::class)->index(),

            'driver' => view('driver.driver-view', [
                'jobs'          => $driverJobs,
                'completedJobs' => $completedJobs,
            ]),

            default => abort(403),
        };
    }
}
