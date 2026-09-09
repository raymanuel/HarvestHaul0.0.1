<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Crop;
use App\Models\FarmerExpense;
use App\Services\ReportService;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    public function farmerProfitExpense(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'farmer') abort(403);

        $cropId = $request->input('crop_id');
        if ($cropId !== null && $cropId !== '' && !is_numeric($cropId)) $cropId = null;

        $data = $this->reportService->getFarmerReportData(
            $user->id,
            $request->input('from'),
            $request->input('to'),
            $cropId ? (int) $cropId : null,
            $request->input('month')
        );

        return view('farmer.reports.profit-expense', $data)
            ->with('categories', FarmerExpense::CATEGORIES)
            ->with('crops', Crop::orderBy('name')->get(['id', 'name']))
            ->with('cropId', $cropId ? (int) $cropId : null)
            ->with('month', $request->input('month'));
    }

    public function farmerProfitExpenseDownload(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'farmer') abort(403);

        $cropId = $request->input('crop_id');
        if ($cropId !== null && $cropId !== '' && !is_numeric($cropId)) $cropId = null;

        $data = $this->reportService->getFarmerReportData(
            $user->id,
            $request->input('from'),
            $request->input('to'),
            $cropId ? (int) $cropId : null,
            $request->input('month')
        );

        $month = $request->input('month');
        $stamp = $month ?: date('Y-m');
        $filename = 'crop-profit-report-' . $stamp . '.csv';

        $rows = [];
        foreach ($data['cropBreakdown'] as $row) {
            $rows[] = [
                'Crop'            => $row['crop'],
                'Quantity Sold (kg)' => $row['kg'],
                'Revenue'         => number_format($row['revenue'], 2),
                'Expense'         => number_format($row['expense'], 2),
                'Net Profit'      => number_format($row['net'], 2),
            ];
        }
        if ($data['untaggedExpense'] > 0) {
            $rows[] = [
                'Crop'            => 'General / Untagged',
                'Quantity Sold (kg)' => '0',
                'Revenue'         => '0.00',
                'Expense'         => number_format($data['untaggedExpense'], 2),
                'Net Profit'      => number_format(-1 * $data['untaggedExpense'], 2),
            ];
        }
        $rows[] = [
            'Crop'            => 'TOTAL',
            'Quantity Sold (kg)' => number_format($data['negotiations']->sum(fn($n) => (float) ($n->negotiated_volume ?? 0)), 2),
            'Revenue'         => number_format($data['totalRevenue'], 2),
            'Expense'         => number_format($data['totalCosts'], 2),
            'Net Profit'      => number_format($data['totalRevenue'] - $data['totalCosts'], 2),
        ];

        if (empty($rows)) {
            $rows = [[
                'Crop' => 'No data', 'Quantity Sold (kg)' => '0',
                'Revenue' => '0.00', 'Expense' => '0.00', 'Net Profit' => '0.00',
            ]];
        }

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function farmerSales(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'farmer') abort(403);

        $preset = $request->input('period', 'this_month');
        $year = (int) ($request->input('y', now()->year));

        switch ($preset) {
            case 'last_month':
                $dateFrom = now()->subMonthNoOverflow()->startOfMonth();
                $dateTo   = now()->subMonthNoOverflow()->endOfMonth();
                break;
            case 'this_quarter':
                $dateFrom = now()->startOfQuarter();
                $dateTo   = now()->endOfQuarter();
                break;
            case 'last_quarter':
                $prev     = now()->copy()->subQuarter();
                $dateFrom = $prev->startOfQuarter();
                $dateTo   = $prev->endOfQuarter();
                break;
            case 'year':
                $dateFrom = Carbon::create($year, 1, 1)->startOfDay();
                $dateTo   = Carbon::create($year, 12, 31)->endOfDay();
                break;
            case 'custom':
                try { $dateFrom = Carbon::parse($request->input('from'))->startOfDay(); }
                catch (\Throwable) { $dateFrom = now()->startOfMonth(); }
                try { $dateTo = Carbon::parse($request->input('to'))->endOfDay(); }
                catch (\Throwable) { $dateTo = now()->endOfMonth(); }
                if ($dateFrom->gt($dateTo)) [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
                break;
            default:
                $preset   = 'this_month';
                $dateFrom = now()->startOfMonth();
                $dateTo   = now()->endOfMonth();
        }

        $soldCrops = \App\Models\Negotiation::where('farmer_id', $user->id)
            ->where('status', 'COMPLETED')
            ->with('harvest.crop')
            ->get()
            ->map(fn($n) => $n->harvest->crop)
            ->filter()
            ->unique('id')
            ->sortBy('name');

        $dealsQuery = \App\Models\Negotiation::where('farmer_id', $user->id)
            ->where('status', 'COMPLETED')
            ->whereBetween('updated_at', [$dateFrom->copy(), $dateTo->copy()])
            ->with(['harvest.crop', 'buyer']);

        $cropId = $request->input('crop_id');
        if ($cropId && !is_numeric($cropId)) $cropId = null;
        if ($cropId) {
            $dealsQuery->whereHas('harvest', fn($q) => $q->where('crop_id', $cropId));
        }

        $deals = $dealsQuery->orderByDesc('updated_at')->get();

        $dealValue = fn($n) => (float) ($n->negotiated_price ?? 0) * (float) ($n->negotiated_volume ?? 0);
        $dealKg    = fn($n) => (float) ($n->negotiated_volume ?? 0);

        $dealCount = $deals->count();
        $kgSold    = round($deals->sum($dealKg), 2);
        $gross     = round($deals->sum($dealValue), 2);
        $avgPrice  = $kgSold > 0 ? round($gross / $kgSold, 2) : 0;

        $pricePoints = $deals
            ->groupBy(fn($n) => ($n->harvest->crop_id ?? 'x') . '|' . number_format((float) ($n->negotiated_price ?? 0), 2, '.', ''))
            ->map(function ($group) use ($dealKg, $dealValue, $kgSold) {
                $first = $group->first();
                $kg    = round($group->sum($dealKg), 2);
                return [
                    'crop'     => $first->harvest->crop->name ?? 'Unknown',
                    'price'    => (float) ($first->negotiated_price ?? 0),
                    'deals'    => $group->count(),
                    'kg'       => $kg,
                    'earnings' => round($group->sum($dealValue), 2),
                    'pct'      => $kgSold > 0 ? round(($kg / $kgSold) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('earnings')
            ->values();

        $monthlyTrend = $deals
            ->groupBy(fn($n) => Carbon::parse($n->updated_at)->format('Y-m'))
            ->map(function ($group) use ($dealKg, $dealValue) {
                return [
                    'deals' => $group->count(),
                    'kg'    => round($group->sum($dealKg), 2),
                    'gross' => round($group->sum($dealValue), 2),
                ];
            })
            ->sortKeys();

        $achievedByCrop = $deals
            ->groupBy(fn($n) => $n->harvest->crop_id ?? 0)
            ->map(function ($group) use ($dealKg, $dealValue) {
                $kg     = round($group->sum($dealKg), 2);
                $gross_ = round($group->sum($dealValue), 2);
                return [
                    'crop'     => $group->first()->harvest->crop->name ?? 'Unknown',
                    'kg'       => $kg,
                    'avg'      => $kg > 0 ? round($gross_ / $kg, 2) : 0,
                ];
            });

        $benchmark = [];

        return view('farmer.reports.sales', compact(
            'deals', 'soldCrops', 'cropId', 'preset', 'year',
            'dateFrom', 'dateTo', 'dealCount', 'kgSold', 'gross', 'avgPrice',
            'pricePoints', 'monthlyTrend', 'achievedByCrop', 'benchmark'
        ));
    }

    public function logisticsTrips(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'logistics_partner') abort(403);

        $profile = $user->logisticsProfile;
        if (!$profile) abort(403);

        $data = $this->reportService->getLogisticsReportData(
            $profile->id,
            $request->input('from'),
            $request->input('to')
        );

        return view('logistics.reports.trips', $data);
    }
}
