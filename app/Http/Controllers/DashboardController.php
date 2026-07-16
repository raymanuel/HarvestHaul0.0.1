<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\PoolingJob;
use App\Http\Controllers\BuyerController;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Initialize default counter fallback metrics
        $activeHarvestCount = 0;

        $availableHarvests = collect();
        $activeDispatchRuns = collect();
        $latestProposals = collect();

        if ($user->role === 'logistics_partner' && $logisticsProfile = $user->logisticsProfile) {

            $availableHarvests = Harvest::whereIn('status', HarvestStatus::LOGISTICS_VISIBLE)
                ->whereHas('farmer.farmerProfile', function ($query) use ($logisticsProfile) {
                    $query->where('is_verified', true);

                    if ($logisticsProfile->logistics_type === 'cooperative') {
                        $query->where('affiliation_type', 'cooperative')
                              ->where('cooperative_id', $logisticsProfile->id);
                    } elseif ($logisticsProfile->logistics_type === 'company') {
                        $query->where('affiliation_type', 'independent');
                    }
                })
                ->with(['farmer.farmerProfile', 'crop', 'cropVariety', 'destination'])
                ->latest()
                ->take(5)
                ->get();

            $activeHarvestCount = $availableHarvests->count();

            $activeDispatchRuns = PoolingJob::where('logistics_profile_id', $logisticsProfile->id)
                ->whereIn('status', ['confirmed', 'in_progress'])
                ->with(['driver', 'truck', 'harvests.crop'])
                ->latest()
                ->take(3)
                ->get();

            $latestProposals = PoolingJob::where('logistics_profile_id', $logisticsProfile->id)
                ->where('status', 'pending')
                ->with(['truck', 'harvests.crop', 'harvests.cropVariety', 'harvests.farmer.farmerProfile'])
                ->latest()
                ->take(3)
                ->get();
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
                ->take(10)
                ->get();

            $completedJobs = PoolingJob::where('driver_id', $user->id)
                ->where('status', 'completed')
                ->count();
        }

        // Farmer dashboard metrics — load once, derive counts from collections
        $activeHarvests = collect();
        $pendingProposals = collect();
        $activeShipments = collect();

        if ($user->role === 'farmer') {
            $activeHarvests = $user->harvests()->whereIn('status', [...HarvestStatus::BUYER_AVAILABLE, HarvestStatus::NEGOTIATING])->with(['crop', 'cropVariety', 'destination'])->latest()->take(3)->get();
            $pendingProposals = PoolingJob::whereHas('harvests', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('status', 'pending')->with(['logisticsProfile', 'truck', 'harvests.crop'])->latest()->take(5)->get();
            $activeShipments = PoolingJob::whereHas('harvests', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('status', 'in_progress')->with(['driver', 'truck', 'harvests.crop'])->latest()->take(5)->get();
        }

        return match($user->role) {
            'farmer' => view('farmers.farmer-view', [
                'activeHarvests' => $activeHarvests,
                'activeHarvestsCount' => $activeHarvests->count(),
                'pendingProposals' => $pendingProposals,
                'pendingProposalsCount' => $pendingProposals->count(),
                'activeShipments' => $activeShipments,
                'activeShipmentsCount' => $activeShipments->count(),
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
            ]),

            'logistics_partner' => view('logistics.logistics-view', [
                'activeHarvestCount' => $activeHarvestCount,
                'availableHarvests'  => $availableHarvests,
                'activeDispatchRuns' => $activeDispatchRuns,
                'latestProposals'    => $latestProposals,
            ]),

            'admin'  => app(AdminController::class)->index(),

            'driver' => view('driver.driver-view', [
                'jobs'          => $driverJobs,
                'completedJobs' => $completedJobs,
            ]),

            'buyer' => app(BuyerController::class)->dashboard(),

            default => abort(403),
        };
    }
}
