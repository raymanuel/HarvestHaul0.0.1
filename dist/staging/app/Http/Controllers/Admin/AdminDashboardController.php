<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Harvest;
use App\Models\PoolingJob;
use App\Models\Negotiation;

use App\Traits\Notifiable;

class AdminDashboardController extends Controller
{
    use Notifiable;

    public function index()
    {
        $pendingFarmersList = User::where('role', 'farmer')
            ->whereHas('farmerProfile', fn($q) => $q->where('is_verified', false))
            ->with('farmerProfile')
            ->latest()
            ->take(20)
            ->get();

        $pendingLogisticsList = User::where('role', 'logistics_partner')
            ->whereHas('logisticsProfile', fn($q) => $q->where('is_verified', false))
            ->with('logisticsProfile')
            ->latest()
            ->take(20)
            ->get();

        $pendingFarmerDocsList = \App\Models\FarmerDocument::where('status', 'pending')
            ->with('user')
            ->latest()
            ->take(20)
            ->get();

        $pendingLogisticsDocsList = \App\Models\LogisticsDocument::where('status', 'pending')
            ->with('logisticsPartner')
            ->latest()
            ->take(20)
            ->get();

        $pendingBuyersList = User::where('role', 'buyer')
            ->whereHas('buyerProfile', fn($q) => $q->where('is_verified', false))
            ->with('buyerProfile')
            ->latest()
            ->take(20)
            ->get();

        $userCounts = User::whereNot('role', 'admin')
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $harvestCounts = Harvest::whereIn('status', ['active'])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $monthlyHarvests = Harvest::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $monthlyJobs = PoolingJob::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $monthlyDeals = Negotiation::where('status', 'COMPLETED')
            ->whereMonth('last_activity_at', now()->month)
            ->whereYear('last_activity_at', now()->year)
            ->count();

        return view('admin.admin-view', [
            'totalUsers'               => $userCounts->sum(),
            'totalFarmers'             => $userCounts->get('farmer', 0),
            'totalLogistics'           => $userCounts->get('logistics_partner', 0),
            'totalDrivers'             => $userCounts->get('driver', 0),
            'totalBuyers'              => $userCounts->get('buyer', 0),
            'pendingFarmers'           => $pendingFarmersList->count(),
            'pendingLogistics'         => $pendingLogisticsList->count(),
            'pendingBuyers'            => $pendingBuyersList->count(),
            'activeHarvests'           => $harvestCounts->get('active', 0),
            'monthlyHarvests'          => $monthlyHarvests,
            'monthlyJobs'              => $monthlyJobs,
            'monthlyDeals'             => $monthlyDeals,
            'recentLogs'               => AuditLog::with('admin')->latest()->take(5)->get(),
            'pendingFarmersList'       => $pendingFarmersList,
            'pendingLogisticsList'     => $pendingLogisticsList,
            'pendingBuyersList'        => $pendingBuyersList,
            'pendingFarmerDocsList'    => $pendingFarmerDocsList,
            'pendingLogisticsDocsList' => $pendingLogisticsDocsList,
        ]);
    }

    public function analytics()
    {
        // ── Crop Pricing Trends ──
        $cropPricingTrends = \Cache::remember('admin.crop_pricing_trends', 300, function () {
            return \App\Models\Negotiation::where('negotiations.status', 'COMPLETED')
                ->whereNotNull('negotiated_price')
                ->join('harvests', 'negotiations.harvest_id', '=', 'harvests.id')
                ->join('crops', 'harvests.crop_id', '=', 'crops.id')
                ->select(
                    'crops.name as crop_name',
                    \DB::raw('ROUND(AVG(negotiations.negotiated_price), 2) as avg_price'),
                    \DB::raw('MIN(negotiations.negotiated_price) as min_price'),
                    \DB::raw('MAX(negotiations.negotiated_price) as max_price'),
                    \DB::raw('COUNT(negotiations.id) as deal_count'),
                )
                ->groupBy('crops.name')
                ->orderByDesc('deal_count')
                ->get();
        });

        // Weekly price aggregation (last 12 weeks)
        $weeklyPrices = \Cache::remember('admin.weekly_prices', 300, function () {
            return \App\Models\Negotiation::where('negotiations.status', 'COMPLETED')
                ->whereNotNull('negotiated_price')
                ->where('negotiations.created_at', '>=', now()->subWeeks(12))
                ->join('harvests', 'negotiations.harvest_id', '=', 'harvests.id')
                ->join('crops', 'harvests.crop_id', '=', 'crops.id')
                ->select(
                    'crops.name as crop_name',
                    \DB::raw('DATE_FORMAT(negotiations.created_at, "%x-%v") as week'),
                    \DB::raw('ROUND(AVG(negotiations.negotiated_price), 2) as avg_price'),
                )
                ->groupBy('crops.name', 'week')
                ->orderBy('week')
                ->get()
                ->groupBy('crop_name');
        });

        // ── Logistics Efficiency ──
        $fleetMetrics = \Cache::remember('admin.fleet_metrics', 300, function () {
            return \App\Models\PoolingJob::where('status', 'completed')
                ->select(
                    \DB::raw('COUNT(*) as total_trips'),
                    \DB::raw('ROUND(AVG(DATEDIFF(completed_at, confirmed_at)), 2) as avg_trip_days'),
                )
                ->first();
        });

        $totalFuelLogs = \Cache::remember('admin.total_fuel_logs', 300, fn() => \App\Models\FuelLog::count());
        $totalFuelCost = \Cache::remember('admin.total_fuel_cost', 300, fn() => \App\Models\FuelLog::sum('cost'));
        $totalFuelLiters = \Cache::remember('admin.total_fuel_liters', 300, fn() => \App\Models\FuelLog::sum('fuel_liters'));
        $avgKpl = $totalFuelLiters > 0
            ? round(\App\Models\FuelLog::selectRaw('(MAX(odometer_reading) - MIN(odometer_reading)) as distance')->value('distance') / $totalFuelLiters, 2)
            : 0;

        // ── Baseline Price Management ──
        $crops = \Cache::remember('admin.crops_list', 600, fn() => \App\Models\Crop::orderBy('name')->get());

        return view('admin.analytics', compact(
            'cropPricingTrends', 'weeklyPrices', 'fleetMetrics',
            'totalFuelLogs', 'totalFuelCost', 'totalFuelLiters', 'avgKpl',
            'crops'
        ));
    }
}
