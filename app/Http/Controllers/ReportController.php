<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PoolingJob;
use App\Models\Negotiation;
use App\Models\FuelLog;
use App\Models\Harvest;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function farmerProfitExpense(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'farmer') abort(403);

        $farmerId = $user->id;
        $dateFrom = $request->input('from', Carbon::now()->subMonths(3)->startOfMonth()->toDateString());
        $dateTo = $request->input('to', Carbon::now()->endOfMonth()->toDateString());

        // Revenue from completed negotiations
        $negotiations = Negotiation::where('farmer_id', $farmerId)
            ->where('status', 'COMPLETED')
            ->whereHas('harvest', function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            })
            ->with('harvest.crop')
            ->get();

        $totalRevenue = $negotiations->sum('negotiated_price');

        // Cost shares from pooling jobs
        $costShares = PoolingJob::where('status', 'completed')
            ->where('completed_at', '>=', $dateFrom)
            ->where('completed_at', '<=', $dateTo)
            ->whereHas('harvests', function ($q) use ($farmerId) {
                $q->where('pooling_job_harvests.harvest_id', function ($subQ) use ($farmerId) {
                    $subQ->select('id')->from('harvests')->where('user_id', $farmerId);
                });
            })
            ->with('harvests')
            ->get();

        $totalCosts = 0;
        $costBreakdown = [];
        foreach ($costShares as $job) {
            foreach ($job->harvests as $harvest) {
                if ($harvest->user_id === $farmerId && $harvest->pivot->cost_share > 0) {
                    $cost = (float) $harvest->pivot->cost_share;
                    $totalCosts += $cost;
                    $cropName = $harvest->crop->name ?? 'Unknown';
                    $costBreakdown[$cropName] = ($costBreakdown[$cropName] ?? 0) + $cost;
                }
            }
        }

        $netProfit = $totalRevenue - $totalCosts;

        // Revenue by crop
        $revenueByCrop = $negotiations->groupBy(function ($n) {
            return $n->harvest->crop->name ?? 'Unknown';
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('negotiated_price'),
                'avg_price' => $group->avg('negotiated_price'),
            ];
        })->sortByDesc('total')->toArray();

        // Monthly trend
        $monthlyTrend = $negotiations->groupBy(function ($n) {
            return Carbon::parse($n->created_at)->format('Y-m');
        })->map(function ($group) {
            return [
                'revenue' => $group->sum('negotiated_price'),
                'count' => $group->count(),
            ];
        })->sortKeys()->toArray();

        // Active harvests summary
        $activeHarvests = Harvest::where('user_id', $farmerId)
            ->whereIn('status', ['active', 'negotiating', 'partially_sold'])
            ->with('crop')
            ->get();

        return view('farmer.reports.profit-expense', compact(
            'totalRevenue', 'totalCosts', 'netProfit',
            'revenueByCrop', 'costBreakdown', 'monthlyTrend',
            'negotiations', 'activeHarvests', 'dateFrom', 'dateTo'
        ));
    }

    public function logisticsTrips(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'logistics_partner') abort(403);

        $profile = $user->logisticsProfile;
        if (!$profile) abort(403);

        $dateFrom = $request->input('from', Carbon::now()->subMonths(3)->startOfMonth()->toDateString());
        $dateTo = $request->input('to', Carbon::now()->endOfMonth()->toDateString());

        // Completed trips
        $trips = PoolingJob::where('logistics_profile_id', $profile->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $dateFrom)
            ->where('completed_at', '<=', $dateTo)
            ->with(['truck', 'driver', 'harvests'])
            ->orderBy('completed_at', 'desc')
            ->get();

        $totalTrips = $trips->count();
        $totalRevenue = $trips->sum('negotiated_price');
        $totalKg = $trips->sum('total_kg');
        $totalFarms = $trips->sum('farm_count');

        // Fuel costs
        $truckIds = $profile->trucks()->pluck('id');
        $fuelLogs = FuelLog::whereIn('truck_id', $truckIds)
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo)
            ->get();

        $totalFuelCost = $fuelLogs->sum('cost');
        $totalFuelLiters = $fuelLogs->sum('fuel_liters');
        $netIncome = $totalRevenue - $totalFuelCost;

        // Per-truck metrics
        $truckMetrics = $profile->trucks()->get()->map(function ($truck) use ($trips, $fuelLogs) {
            $truckTrips = $trips->where('truck_id', $truck->id);
            $truckFuel = $fuelLogs->where('truck_id', $truck->id);

            $revenue = $truckTrips->sum('negotiated_price');
            $fuelCost = $truckFuel->sum('cost');
            $fuelLiters = $truckFuel->sum('fuel_liters');

            // Calculate KPL
            $odometerReadings = $truckFuel->pluck('odometer_reading')->filter();
            $kpl = 0;
            if ($odometerReadings->count() >= 2) {
                $distance = $odometerReadings->max() - $odometerReadings->min();
                $kpl = $fuelLiters > 0 ? round($distance / $fuelLiters, 1) : 0;
            }

            return [
                'truck' => $truck,
                'trips' => $truckTrips->count(),
                'revenue' => $revenue,
                'fuel_cost' => $fuelCost,
                'net_income' => $revenue - $fuelCost,
                'fuel_liters' => $fuelLiters,
                'kpl' => $kpl,
                'avg_load' => $truckTrips->count() > 0
                    ? round($truckTrips->avg('total_kg'), 1)
                    : 0,
            ];
        })->toArray();

        // Monthly trend
        $monthlyTrend = $trips->groupBy(function ($t) {
            return Carbon::parse($t->completed_at)->format('Y-m');
        })->map(function ($group) {
            return [
                'trips' => $group->count(),
                'revenue' => $group->sum('negotiated_price'),
                'kg' => $group->sum('total_kg'),
            ];
        })->sortKeys()->toArray();

        return view('logistics.reports.trips', compact(
            'totalTrips', 'totalRevenue', 'totalFuelCost', 'totalFuelLiters',
            'netIncome', 'totalKg', 'totalFarms',
            'truckMetrics', 'monthlyTrend', 'trips', 'dateFrom', 'dateTo'
        ));
    }
}
