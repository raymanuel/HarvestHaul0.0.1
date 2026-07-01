<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Harvest;
use App\Models\PoolingJob;
<<<<<<< HEAD
use App\Http\Controllers\BuyerController;
=======
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Initialize default counter fallback metrics
        $activeHarvestCount = 0;

<<<<<<< HEAD
        $availableHarvests = collect();
        $activeDispatchRuns = collect();
        $latestProposals = collect();

=======
        /**
         * Safely isolate metrics for verified logistics coordinators.
         * Utilizes direct column tracking arrays to bypass missing model inverse relationships.
         */
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
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
<<<<<<< HEAD
            $activeHarvestCount = Harvest::where('status', 'sold')
                ->whereIn('user_id', $farmerIds)
                ->count();

            $availableHarvests = Harvest::where('status', 'sold')
                ->whereIn('user_id', $farmerIds)
                ->with(['farmer.farmerProfile', 'crop', 'cropVariety', 'destination'])
                ->latest()
                ->take(5)
                ->get();

            $activeDispatchRuns = PoolingJob::where('logistics_profile_id', $logisticsProfile->id)
                ->whereIn('status', ['confirmed', 'in_progress'])
                ->with(['driver', 'truck', 'harvests.crop'])
                ->latest()
                ->take(3)
                ->get();

            $latestProposals = PoolingJob::where('logistics_profile_id', $logisticsProfile->id)
                ->where('status', 'pending')
                ->with(['truck', 'harvests.crop'])
                ->latest()
                ->take(3)
                ->get();
=======
            $activeHarvestCount = Harvest::where('status', 'active')
                ->whereIn('user_id', $farmerIds)
                ->count();
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
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
<<<<<<< HEAD

                // Live lists
                'activeHarvests' => $user->harvests()->where('status', 'active')->with(['crop', 'cropVariety', 'destination'])->latest()->take(3)->get(),
                'pendingProposals' => PoolingJob::whereHas('harvests', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->where('status', 'pending')->with(['logisticsProfile', 'truck', 'harvests.crop'])->latest()->get(),
                'activeShipments' => PoolingJob::whereHas('harvests', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->where('status', 'in_progress')->with(['driver', 'truck', 'harvests.crop'])->latest()->get(),
                'guidanceCrops' => \App\Models\Crop::whereNotNull('baseline_price_per_kg')
                    ->orderBy('name')
                    ->get()
                    ->map(function ($crop) {
                        $b2b = (float) $crop->baseline_price_per_kg;
                        $broker = round($b2b * 0.67, 2);
                        return [
                            'name' => $crop->name,
                            'broker' => $broker,
                            'b2b' => $b2b,
                            'trend' => 'Stable',
                            'trend_color' => 'slate',
                        ];
                    })->whenEmpty(function () {
                        return collect([
                            ['name' => 'Potato', 'broker' => 32.0, 'b2b' => 48.0, 'trend' => 'High Demand', 'trend_color' => 'emerald'],
                            ['name' => 'Red Onion', 'broker' => 85.0, 'b2b' => 125.0, 'trend' => 'Moderate', 'trend_color' => 'amber'],
                            ['name' => 'Carrot', 'broker' => 40.0, 'b2b' => 58.0, 'trend' => 'High Demand', 'trend_color' => 'emerald'],
                            ['name' => 'Cassava', 'broker' => 14.0, 'b2b' => 22.0, 'trend' => 'Stable', 'trend_color' => 'slate'],
                            ['name' => 'Cabbage', 'broker' => 28.0, 'b2b' => 42.0, 'trend' => 'High Demand', 'trend_color' => 'emerald'],
                        ]);
                    }),
=======
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            ]),

            'logistics_partner' => view('logistics.logistics-view', [
                'activeHarvestCount' => $activeHarvestCount,
<<<<<<< HEAD
                'availableHarvests'  => $availableHarvests,
                'activeDispatchRuns' => $activeDispatchRuns,
                'latestProposals'    => $latestProposals,
=======
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            ]),

            'admin'  => app(AdminController::class)->index(),

            'driver' => view('driver.driver-view', [
                'jobs'          => $driverJobs,
                'completedJobs' => $completedJobs,
            ]),

<<<<<<< HEAD
            'buyer' => app(BuyerController::class)->dashboard(),

=======
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            default => abort(403),
        };
    }
}
