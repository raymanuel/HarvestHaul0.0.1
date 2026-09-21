<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\BuyerOrder;
use App\Models\FarmerPayment;
use App\Models\ReceivingRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    private const BOOKED_SALE_STATUSES = [
        BuyerOrder::STATUS_ACCEPTED,
        BuyerOrder::STATUS_PREPARING,
        BuyerOrder::STATUS_READY_FOR_DELIVERY,
        BuyerOrder::STATUS_OUT_FOR_DELIVERY,
        BuyerOrder::STATUS_DELIVERED,
        BuyerOrder::STATUS_COMPLETED,
    ];

    public function index(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        return view('coop.reports.index', compact('from', 'to'));
    }

    public function procurement(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $records = $this->procurementQuery($from, $to)->get();

        return view('coop.reports.procurement', [
            'from' => $from, 'to' => $to, 'records' => $records,
            'totalWeight' => $records->sum('actual_weight_kg'),
            'totalSpend' => $records->sum('total_amount'),
        ]);
    }

    public function procurementCsv(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $records = $this->procurementQuery($from, $to)->get();

        return response()->streamDownload(function () use ($records) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Confirmed At', 'Farmer', 'Crop', 'Grade', 'Weight (kg)', 'Price/kg', 'Total']);
            foreach ($records as $r) {
                fputcsv($out, [
                    $r->confirmed_at?->format('Y-m-d H:i'), $r->farmer?->name, $r->crop?->name, $r->cropGrade?->name,
                    $r->actual_weight_kg, $r->buying_price_per_kg, $r->total_amount,
                ]);
            }
            fclose($out);
        }, 'procurement-report.csv');
    }

    public function sales(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $orders = $this->salesQuery($from, $to)->get();

        return view('coop.reports.sales', [
            'from' => $from, 'to' => $to, 'orders' => $orders,
            'totalKg' => $orders->sum('total_kg'),
            'totalRevenue' => $orders->sum('total_amount'),
        ]);
    }

    public function salesCsv(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $orders = $this->salesQuery($from, $to)->get();

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Reference', 'Buyer', 'Status', 'Weight (kg)', 'Total']);
            foreach ($orders as $o) {
                fputcsv($out, [
                    $o->created_at->format('Y-m-d H:i'), $o->reference, $o->buyer?->name, $o->status,
                    $o->total_kg, $o->total_amount,
                ]);
            }
            fclose($out);
        }, 'sales-report.csv');
    }

    public function payouts(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $payments = $this->payoutsQuery($from, $to)->get();

        return view('coop.reports.payouts', [
            'from' => $from, 'to' => $to, 'payments' => $payments,
            'totalPaid' => $payments->sum('amount'),
        ]);
    }

    public function payoutsCsv(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $payments = $this->payoutsQuery($from, $to)->get();

        return response()->streamDownload(function () use ($payments) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Paid At', 'Farmer', 'Method', 'Reference', 'Amount']);
            foreach ($payments as $p) {
                fputcsv($out, [
                    $p->paid_at?->format('Y-m-d H:i'), $p->receivingRecord?->farmer?->name, $p->method, $p->reference,
                    $p->amount,
                ]);
            }
            fclose($out);
        }, 'farmer-payouts-report.csv');
    }

    private function procurementQuery(Carbon $from, Carbon $to)
    {
        return ReceivingRecord::with(['farmer', 'crop', 'cropGrade'])
            ->where('cooperative_id', $this->cooperativeId())
            ->where('status', ReceivingRecord::STATUS_CONFIRMED)
            ->whereBetween('confirmed_at', [$from, $to])
            ->orderByDesc('confirmed_at');
    }

    private function salesQuery(Carbon $from, Carbon $to)
    {
        return BuyerOrder::with('buyer')
            ->where('cooperative_id', $this->cooperativeId())
            ->whereIn('status', self::BOOKED_SALE_STATUSES)
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at');
    }

    private function payoutsQuery(Carbon $from, Carbon $to)
    {
        return FarmerPayment::with('receivingRecord.farmer')
            ->where('cooperative_id', $this->cooperativeId())
            ->whereBetween('paid_at', [$from, $to])
            ->orderByDesc('paid_at');
    }

    private function dateRange(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfMonth();

        return [$from, $to];
    }

    private function cooperativeId(): int
    {
        $id = Auth::user()?->cooperative_id;
        if (! $id) {
            abort(403, 'You are not an active cooperative admin.');
        }

        return $id;
    }
}
