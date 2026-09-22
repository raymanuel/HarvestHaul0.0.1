<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\BuyerOrder;
use App\Models\Cooperative;
use App\Models\FarmerPayment;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\ReceivingRecord;
use Barryvdh\DomPDF\Facade\Pdf;
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

    public function procurementPdf(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $records = $this->procurementQuery($from, $to)->get();

        return $this->renderPdf(
            'Procurement Report', $from, $to,
            ['Confirmed At', 'Farmer', 'Crop', 'Grade', 'Weight (kg)', 'Price/kg', 'Total'],
            $records->map(fn ($r) => [
                $r->confirmed_at?->format('M d, Y'), $r->farmer?->name, $r->crop?->name, $r->cropGrade?->name,
                number_format($r->actual_weight_kg, 2), '₱'.number_format($r->buying_price_per_kg, 2), '₱'.number_format($r->total_amount, 2),
            ])->all(),
            [
                ['Total Weight', number_format($records->sum('actual_weight_kg'), 2).' kg'],
                ['Total Spend', '₱'.number_format($records->sum('total_amount'), 2)],
            ],
            'procurement-report.pdf'
        );
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

    public function salesPdf(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $orders = $this->salesQuery($from, $to)->get();

        return $this->renderPdf(
            'Sales Report', $from, $to,
            ['Date', 'Reference', 'Buyer', 'Status', 'Weight (kg)', 'Total'],
            $orders->map(fn ($o) => [
                $o->created_at->format('M d, Y'), $o->reference, $o->buyer?->name, ucfirst(str_replace('_', ' ', $o->status)),
                number_format($o->total_kg, 2), '₱'.number_format($o->total_amount, 2),
            ])->all(),
            [
                ['Total Weight', number_format($orders->sum('total_kg'), 2).' kg'],
                ['Total Revenue', '₱'.number_format($orders->sum('total_amount'), 2)],
            ],
            'sales-report.pdf'
        );
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

    public function payoutsPdf(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $payments = $this->payoutsQuery($from, $to)->get();

        return $this->renderPdf(
            'Farmer Payouts Report', $from, $to,
            ['Paid At', 'Farmer', 'Method', 'Reference', 'Amount'],
            $payments->map(fn ($p) => [
                $p->paid_at?->format('M d, Y'), $p->receivingRecord?->farmer?->name, ucwords(str_replace('_', ' ', $p->method)),
                $p->reference ?? '—', '₱'.number_format($p->amount, 2),
            ])->all(),
            [
                ['Total Paid', '₱'.number_format($payments->sum('amount'), 2)],
                ['Payments Recorded', (string) $payments->count()],
            ],
            'farmer-payouts-report.pdf'
        );
    }

    public function deliveries(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $jobs = $this->deliveriesQuery($from, $to)->get();

        return view('coop.reports.deliveries', array_merge(
            compact('from', 'to', 'jobs'),
            $this->deliveriesSummary($jobs)
        ));
    }

    public function deliveriesCsv(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $jobs = $this->deliveriesQuery($from, $to)->get();

        return response()->streamDownload(function () use ($jobs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Type', 'Truck', 'Driver', 'Stops', 'Distance (km)', 'Duration (min)', 'Status']);
            foreach ($jobs as $j) {
                fputcsv($out, [
                    $j->pickup_date?->format('Y-m-d'), $j->job_type, $j->truck?->plate_number, $j->deliveryPersonnel?->name,
                    $j->stops->count(), $j->route_distance_km, $j->route_duration_min, $j->status,
                ]);
            }
            fclose($out);
        }, 'fleet-deliveries-report.csv');
    }

    public function deliveriesPdf(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $jobs = $this->deliveriesQuery($from, $to)->get();
        $summary = $this->deliveriesSummary($jobs);

        return $this->renderPdf(
            'Fleet & Deliveries Report', $from, $to,
            ['Date', 'Type', 'Truck', 'Driver', 'Stops', 'Distance (km)', 'Status'],
            $jobs->map(fn ($j) => [
                $j->pickup_date?->format('M d, Y'), ucfirst($j->job_type), $j->truck?->plate_number ?? '—', $j->deliveryPersonnel?->name ?? '—',
                $j->stops->count(), $j->route_distance_km ? number_format($j->route_distance_km, 1) : '—', ucfirst(str_replace('_', ' ', $j->status)),
            ])->all(),
            [
                ['Total Trips', (string) $summary['totalTrips']],
                ['Total Distance', number_format($summary['totalDistance'], 1).' km'],
                ['Total Stops', (string) $summary['totalStops']],
                ['Delivered Stops', (string) $summary['deliveredStops']],
                ['Failed Stops', (string) $summary['failedStops']],
            ],
            'fleet-deliveries-report.pdf'
        );
    }

    private function deliveriesQuery(Carbon $from, Carbon $to)
    {
        return HaulJob::with(['truck', 'deliveryPersonnel', 'stops'])
            ->where('cooperative_id', $this->cooperativeId())
            ->whereBetween('pickup_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('pickup_date');
    }

    private function deliveriesSummary($jobs): array
    {
        $stops = $jobs->flatMap->stops;

        $vehicles = $jobs->groupBy('truck_id')->map(function ($vehicleJobs) {
            $truck = $vehicleJobs->first()->truck;

            return [
                'truck' => $truck,
                'trips' => $vehicleJobs->count(),
                'distance' => $vehicleJobs->sum('route_distance_km'),
                'stops' => $vehicleJobs->sum(fn ($j) => $j->stops->count()),
                'lastActive' => $vehicleJobs->max('pickup_date'),
            ];
        })->filter(fn ($v) => $v['truck'])->values();

        return [
            'totalTrips' => $jobs->count(),
            'totalDistance' => (float) $jobs->sum('route_distance_km'),
            'totalStops' => $stops->count(),
            'deliveredStops' => $stops->whereIn('status', [HaulJobStop::STATUS_PICKED_UP, HaulJobStop::STATUS_DELIVERED])->count(),
            'failedStops' => $stops->where('status', HaulJobStop::STATUS_FAILED)->count(),
            'vehicles' => $vehicles,
        ];
    }

    private function renderPdf(string $title, Carbon $from, Carbon $to, array $columns, array $rows, array $summaryLines, string $filename)
    {
        $pdf = Pdf::loadView('coop.reports.pdf.layout', [
            'title' => $title,
            'cooperativeName' => Cooperative::find($this->cooperativeId())?->name ?? '—',
            'from' => $from,
            'to' => $to,
            'columns' => $columns,
            'rows' => $rows,
            'summaryLines' => $summaryLines,
        ]);

        return $pdf->download($filename);
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
