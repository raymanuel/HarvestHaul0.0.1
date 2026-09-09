<?php

namespace App\Services;

use App\Models\PoolingJob;
use App\Models\Negotiation;
use App\Models\FuelLog;
use App\Models\Harvest;
use App\Models\FarmerExpense;
use App\Models\HaulIntent;
use Carbon\Carbon;

class ReportService
{
    public function getDateRange(?string $start, ?string $end): array
    {
        $defaultFrom = Carbon::now()->subMonths(3)->startOfMonth()->toDateString();
        $defaultTo = Carbon::now()->endOfMonth()->toDateString();

        $from = $start && strtotime($start) ? $start : $defaultFrom;
        $to = $end && strtotime($end) ? $end : $defaultTo;

        return [$from, $to];
    }

    public function getDashboardStats(?string $startDate, ?string $endDate): array
    {
        [$dateFrom, $dateTo] = $this->getDateRange($startDate, $endDate);

        $completedNegotiations = Negotiation::where('status', 'COMPLETED')
            ->whereBetween('updated_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->with(['harvest.crop', 'buyer'])
            ->get();

        $totalRevenue = round($completedNegotiations->sum(
            fn($n) => (float) ($n->negotiated_price ?? 0) * (float) ($n->negotiated_volume ?? 0)
        ), 2);

        $totalDeals = $completedNegotiations->count();
        $totalKg = round($completedNegotiations->sum(fn($n) => (float) ($n->negotiated_volume ?? 0)), 2);

        $activeHarvests = Harvest::whereIn('status', ['active', 'negotiating', 'partially_sold'])->count();
        $completedPoolingJobs = PoolingJob::where('status', 'completed')
            ->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->count();

        return compact(
            'totalRevenue', 'totalDeals', 'totalKg',
            'activeHarvests', 'completedPoolingJobs',
            'dateFrom', 'dateTo'
        );
    }

    public function getFarmerReportData(int $farmerId, ?string $startDate, ?string $endDate, ?int $cropId = null, ?string $month = null): array
    {
        [$dateFrom, $dateTo] = $this->getDateRange($startDate, $endDate);

        // A selected month (Y-m) overrides the from/to range unless a custom range was passed.
        if ($month && !$startDate && !$endDate) {
            $dateFrom = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $dateTo = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
        }

        // Revenue from completed negotiations
        $negotiations = Negotiation::where('farmer_id', $farmerId)
            ->where('status', 'COMPLETED')
            ->whereBetween('updated_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->with(['harvest.crop', 'buyer'])
            ->where(fn($q) => $cropId ? $q->whereHas('harvest', fn($h) => $h->where('crop_id', $cropId)) : $q)
            ->get();

        $totalRevenue = round($negotiations->sum(
            fn($n) => (float) ($n->negotiated_price ?? 0) * (float) ($n->negotiated_volume ?? 0)
        ), 2);

        // Transport costs: pooling cost shares
        $costShares = PoolingJob::where('status', 'completed')
            ->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->whereHas('harvests', function ($q) use ($farmerId) {
                $q->whereIn('pooling_job_harvests.harvest_id', function ($subQ) use ($farmerId) {
                    $subQ->select('id')->from('harvests')->where('user_id', $farmerId);
                });
            })
            ->where(fn($q) => $cropId
                ? $q->whereHas('harvests.crop', fn($c) => $c->where('crops.id', $cropId))
                : $q)
            ->with(['harvests.crop'])
            ->get();

        $transportBreakdown = [];
        $transportBreakdownByCropId = [];
        $totalTransport = 0;
        foreach ($costShares as $job) {
            foreach ($job->harvests as $harvest) {
                if ($harvest->user_id === $farmerId && $harvest->pivot->cost_share > 0) {
                    $cost = (float) $harvest->pivot->cost_share;
                    $totalTransport += $cost;
                    $cropName = $harvest->crop->name ?? 'Unknown';
                    $cropKey  = $harvest->crop_id ?? 'uncategorized';
                    $transportBreakdown[$cropName] = ($transportBreakdown[$cropName] ?? 0) + $cost;
                    $transportBreakdownByCropId[$cropKey] = ($transportBreakdownByCropId[$cropKey] ?? 0) + $cost;
                }
            }
        }

        // Transport costs: direct haul bookings
        $haulerBooked = HaulIntent::where('status', 'accepted')
            ->whereNotNull('hauling_rate_php_per_kg')
            ->whereHas('haulRequest.harvest', fn($q) => $q->where('user_id', $farmerId))
            ->whereBetween('updated_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->with('haulRequest.harvest.crop')
            ->get();

        foreach ($haulerBooked as $intent) {
            $harvest = $intent->haulRequest->harvest ?? null;
            if (!$harvest) continue;
            if ($cropId && (int) $harvest->crop_id !== (int) $cropId) continue;
            $cost = (float) $intent->hauling_rate_php_per_kg * (float) $harvest->quantity_kg;
            $totalTransport += $cost;
            $cropName = $harvest->crop->name ?? 'Unknown';
            $cropKey  = $harvest->crop_id ?? 'uncategorized';
            $transportBreakdown[$cropName] = ($transportBreakdown[$cropName] ?? 0) + $cost;
            $transportBreakdownByCropId[$cropKey] = ($transportBreakdownByCropId[$cropKey] ?? 0) + $cost;
        }

        // Production expenses
        $loggedExpensesQuery = FarmerExpense::where('user_id', $farmerId)
            ->whereBetween('expense_date', [$dateFrom, $dateTo]);
        if ($cropId) {
            $loggedExpensesQuery->where('crop_id', $cropId);
        }
        $loggedExpenses = $loggedExpensesQuery->with('crop')->orderByDesc('expense_date')->get();

        $totalLogged = round($loggedExpenses->sum('amount'), 2);
        $loggedByCategory = [];
        foreach (FarmerExpense::CATEGORIES as $cat) {
            $loggedByCategory[$cat] = 0;
        }
        foreach ($loggedExpenses as $exp) {
            $loggedByCategory[$exp->category] += (float) $exp->amount;
        }
        arsort($loggedByCategory);
        $loggedByCategory = array_filter($loggedByCategory);

        arsort($transportBreakdown);

        $totalCosts = round($totalTransport + $totalLogged, 2);
        $netProfit = round($totalRevenue - $totalCosts, 2);

        // Revenue by crop
        $revenueByCrop = $negotiations->groupBy(fn($n) => $n->harvest->crop->name ?? 'Unknown')
            ->map(function ($group) {
                $kg = round($group->sum(fn($n) => (float) ($n->negotiated_volume ?? 0)), 2);
                $total = round($group->sum(
                    fn($n) => (float) ($n->negotiated_price ?? 0) * (float) ($n->negotiated_volume ?? 0)
                ), 2);
                return [
                    'count' => $group->count(),
                    'kg' => $kg,
                    'total' => $total,
                    'avg_price' => $kg > 0 ? round($total / $kg, 2) : 0,
                ];
            })->sortByDesc('total')->toArray();

        // Monthly trend
        $monthlyTrend = $negotiations->groupBy(fn($n) => Carbon::parse($n->updated_at)->format('Y-m'))
            ->map(function ($group) {
                return [
                    'revenue' => round($group->sum(
                        fn($n) => (float) ($n->negotiated_price ?? 0) * (float) ($n->negotiated_volume ?? 0)
                    ), 2),
                    'count' => $group->count(),
                ];
            })->sortKeys()->toArray();

        // Active harvests
        $activeHarvests = Harvest::where('user_id', $farmerId)
            ->whereIn('status', ['active', 'negotiating', 'partially_sold'])
            ->with('crop')
            ->get();

        // Per-crop profit breakdown (revenue, expense, net) keyed by crop id.
        $cropIds = $negotiations
            ->map(fn($n) => $n->harvest->crop_id)
            ->merge($loggedExpenses->pluck('crop_id'))
            ->merge(array_keys($transportBreakdownByCropId))
            ->filter()
            ->unique();
        $cropNames = \App\Models\Crop::whereIn('id', $cropIds)->pluck('name', 'id');

        $cropBreakdown = [];
        foreach ($cropIds as $cid) {
            $cropBreakdown[$cid] = [
                'id'      => $cid,
                'crop'    => $cropNames[$cid] ?? 'Unknown',
                'kg'      => 0.0,
                'revenue' => 0.0,
                'expense' => 0.0,
                'net'     => 0.0,
            ];
        }

        foreach ($negotiations as $n) {
            $cid = $n->harvest->crop_id;
            if (!$cid || !isset($cropBreakdown[$cid])) continue;
            $cropBreakdown[$cid]['kg']      += (float) ($n->negotiated_volume ?? 0);
            $cropBreakdown[$cid]['revenue'] += (float) ($n->negotiated_price ?? 0) * (float) ($n->negotiated_volume ?? 0);
        }

        foreach ($loggedExpenses as $exp) {
            $cid = $exp->crop_id;
            if ($cid) {
                if (!isset($cropBreakdown[$cid])) {
                    $cropBreakdown[$cid] = [
                        'id' => $cid, 'crop' => $exp->crop->name ?? 'Unknown',
                        'kg' => 0.0, 'revenue' => 0.0, 'expense' => 0.0, 'net' => 0.0,
                    ];
                }
                $cropBreakdown[$cid]['expense'] += (float) $exp->amount;
            }
        }

        foreach ($transportBreakdownByCropId as $cid => $cost) {
            if (!isset($cropBreakdown[$cid])) {
                $cropBreakdown[$cid] = [
                    'id' => $cid, 'crop' => $cropNames[$cid] ?? 'Unknown',
                    'kg' => 0.0, 'revenue' => 0.0, 'expense' => 0.0, 'net' => 0.0,
                ];
            }
            $cropBreakdown[$cid]['expense'] += $cost;
        }

        foreach ($cropBreakdown as $cid => &$row) {
            $row['kg']      = round($row['kg'], 2);
            $row['revenue'] = round($row['revenue'], 2);
            $row['expense'] = round($row['expense'], 2);
            $row['net']     = round($row['revenue'] - $row['expense'], 2);
        }
        unset($row);

        // "Untagged" row: logged expenses with no crop (only meaningful when no specific crop is selected).
        $untaggedExpense = $cropId
            ? 0.0
            : round($loggedExpenses->whereNull('crop_id')->sum('amount'), 2);

        // Months available across the farmer's deals and expenses (for the month dropdown).
        $availableMonths = $negotiations
            ->map(fn($n) => Carbon::parse($n->updated_at)->format('Y-m'))
            ->merge($loggedExpenses->map(fn($e) => $e->expense_date->format('Y-m')))
            ->unique()
            ->sortDesc()
            ->values();

        // Crops the farmer has ever sold (for the crop dropdown).
        $soldCrops = Negotiation::where('farmer_id', $farmerId)
            ->where('status', 'COMPLETED')
            ->with('harvest.crop')
            ->get()
            ->map(fn($n) => $n->harvest->crop)
            ->filter()
            ->unique('id')
            ->sortBy('name');

        return compact(
            'totalRevenue', 'totalTransport', 'totalLogged', 'totalCosts', 'netProfit',
            'revenueByCrop', 'transportBreakdown', 'loggedByCategory', 'monthlyTrend',
            'negotiations', 'activeHarvests', 'dateFrom', 'dateTo', 'loggedExpenses',
            'cropBreakdown', 'untaggedExpense', 'availableMonths', 'soldCrops'
        );
    }

    public function getLogisticsReportData(int $logisticsProfileId, ?string $startDate, ?string $endDate): array
    {
        [$dateFrom, $dateTo] = $this->getDateRange($startDate, $endDate);

        $trips = PoolingJob::where('logistics_profile_id', $logisticsProfileId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$dateFrom, $dateTo])
            ->with(['truck', 'driver', 'harvests'])
            ->orderBy('completed_at', 'desc')
            ->get();

        $totalTrips = $trips->count();
        $totalRevenue = $trips->sum('negotiated_price');
        $totalKg = $trips->sum('total_kg');
        $totalFarms = $trips->sum('farm_count');

        $profile = \App\Models\LogisticsProfile::find($logisticsProfileId);
        $truckIds = $profile ? $profile->trucks()->pluck('id') : collect();
        $fuelLogs = FuelLog::whereIn('truck_id', $truckIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->get();

        $totalFuelCost = $fuelLogs->sum('cost');
        $totalFuelLiters = $fuelLogs->sum('fuel_liters');
        $netIncome = $totalRevenue - $totalFuelCost;

        $trucks = $profile ? $profile->trucks()->get() : collect();
        $truckMetrics = $trucks->map(function ($truck) use ($trips, $fuelLogs) {
            $truckTrips = $trips->where('truck_id', $truck->id);
            $truckFuel = $fuelLogs->where('truck_id', $truck->id);

            $revenue = $truckTrips->sum('negotiated_price');
            $fuelCost = $truckFuel->sum('cost');
            $fuelLiters = $truckFuel->sum('fuel_liters');

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

        $monthlyTrend = $trips->groupBy(fn($t) => Carbon::parse($t->completed_at)->format('Y-m'))
            ->map(function ($group) {
                return [
                    'trips' => $group->count(),
                    'revenue' => $group->sum('negotiated_price'),
                    'kg' => $group->sum('total_kg'),
                ];
            })->sortKeys()->toArray();

        return compact(
            'totalTrips', 'totalRevenue', 'totalFuelCost', 'totalFuelLiters',
            'netIncome', 'totalKg', 'totalFarms',
            'truckMetrics', 'monthlyTrend', 'trips', 'dateFrom', 'dateTo'
        );
    }

    public function getBuyerReportData(int $buyerId, ?string $startDate, ?string $endDate): array
    {
        [$dateFrom, $dateTo] = $this->getDateRange($startDate, $endDate);

        $purchases = Negotiation::where('buyer_id', $buyerId)
            ->where('status', 'COMPLETED')
            ->whereBetween('updated_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->with(['harvest.crop', 'harvest.user'])
            ->get();

        $totalPurchases = $purchases->count();
        $totalKg = round($purchases->sum(fn($n) => (float) ($n->negotiated_volume ?? 0)), 2);
        $totalSpent = round($purchases->sum(
            fn($n) => (float) ($n->negotiated_price ?? 0) * (float) ($n->negotiated_volume ?? 0)
        ), 2);
        $avgPricePerKg = $totalKg > 0 ? round($totalSpent / $totalKg, 2) : 0;

        $spendByCrop = $purchases->groupBy(fn($n) => $n->harvest->crop->name ?? 'Unknown')
            ->map(function ($group) {
                $kg = round($group->sum(fn($n) => (float) ($n->negotiated_volume ?? 0)), 2);
                $total = round($group->sum(
                    fn($n) => (float) ($n->negotiated_price ?? 0) * (float) ($n->negotiated_volume ?? 0)
                ), 2);
                return [
                    'deals' => $group->count(),
                    'kg' => $kg,
                    'total' => $total,
                    'avg_price' => $kg > 0 ? round($total / $kg, 2) : 0,
                ];
            })->sortByDesc('total')->toArray();

        $monthlyTrend = $purchases->groupBy(fn($n) => Carbon::parse($n->updated_at)->format('Y-m'))
            ->map(function ($group) {
                return [
                    'deals' => $group->count(),
                    'kg' => round($group->sum(fn($n) => (float) ($n->negotiated_volume ?? 0)), 2),
                    'spent' => round($group->sum(
                        fn($n) => (float) ($n->negotiated_price ?? 0) * (float) ($n->negotiated_volume ?? 0)
                    ), 2),
                ];
            })->sortKeys()->toArray();

        return compact(
            'totalPurchases', 'totalKg', 'totalSpent', 'avgPricePerKg',
            'spendByCrop', 'monthlyTrend', 'purchases', 'dateFrom', 'dateTo'
        );
    }

    public function exportCsv(array $data, string $filename): void
    {
        if (empty($data)) {
            abort(404);
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, array_keys($data[0]));

            foreach ($data as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        response()->stream($callback, 200, $headers)->send();
    }
}
