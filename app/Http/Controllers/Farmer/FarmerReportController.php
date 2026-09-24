<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\ReceivingRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class FarmerReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $records = $this->query($from, $to)->get();

        return view('farmer.reports.index', array_merge(
            compact('from', 'to', 'records'),
            $this->summary($records)
        ));
    }

    public function csv(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $records = $this->query($from, $to)->get();

        return response()->streamDownload(function () use ($records) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Crop', 'Grade', 'Weight (kg)', 'Price/kg', 'Total', 'Paid', 'Balance']);
            foreach ($records as $r) {
                fputcsv($out, [
                    $r->confirmed_at?->format('Y-m-d'), $r->crop?->name, $r->cropGrade?->name,
                    $r->actual_weight_kg, $r->buying_price_per_kg, $r->total_amount,
                    $r->totalPaid(), $r->balanceDue(),
                ]);
            }
            fclose($out);
        }, 'my-harvest-report.csv');
    }

    public function pdf(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $records = $this->query($from, $to)->get();
        $summary = $this->summary($records);

        $pdf = Pdf::loadView('coop.reports.pdf.layout', [
            'title' => 'My Harvest Report',
            'cooperativeName' => Auth::user()->cooperative?->name ?? '—',
            'from' => $from,
            'to' => $to,
            'columns' => ['Date', 'Crop', 'Grade', 'Weight (kg)', 'Price/kg', 'Total', 'Paid', 'Balance'],
            'rows' => $records->map(fn ($r) => [
                $r->confirmed_at?->format('M d, Y'), $r->crop?->name ?? '—', $r->cropGrade?->name ?? '—',
                number_format($r->actual_weight_kg, 2), '₱'.number_format($r->buying_price_per_kg, 2),
                '₱'.number_format($r->total_amount, 2), '₱'.number_format($r->totalPaid(), 2), '₱'.number_format($r->balanceDue(), 2),
            ])->all(),
            'summaryLines' => array_merge(
                [
                    ['Total Weight', number_format($summary['totalWeight'], 2).' kg'],
                    ['Total Earnings', '₱'.number_format($summary['totalEarnings'], 2)],
                    ['Total Paid', '₱'.number_format($summary['totalPaid'], 2)],
                    ['Outstanding Balance', '₱'.number_format($summary['totalOutstanding'], 2)],
                ],
                $summary['byCrop']->map(fn ($c) => [
                    'By Crop — '.$c['crop'], number_format($c['weight'], 2).' kg / ₱'.number_format($c['earnings'], 2).' ('.$c['count'].' harvest(s))',
                ])->all()
            ),
        ]);

        return $pdf->download('my-harvest-report.pdf');
    }

    private function query(Carbon $from, Carbon $to)
    {
        return ReceivingRecord::with(['crop', 'cropGrade', 'payments'])
            ->where('farmer_id', Auth::id())
            ->where('status', ReceivingRecord::STATUS_CONFIRMED)
            ->whereBetween('confirmed_at', [$from, $to])
            ->orderByDesc('confirmed_at');
    }

    /**
     * Per-crop earnings breakdown (grouped by crop name, not id, so records
     * with no crop set are still shown as one "Unknown" bucket rather than
     * dropped).
     */
    private function summary($records): array
    {
        $byCrop = $records->groupBy(fn ($r) => $r->crop?->name ?? 'Unknown')
            ->map(fn ($group, $name) => [
                'crop'     => $name,
                'count'    => $group->count(),
                'weight'   => round((float) $group->sum('actual_weight_kg'), 2),
                'earnings' => round((float) $group->sum('total_amount'), 2),
            ])
            ->sortByDesc('earnings')
            ->values();

        return [
            'totalWeight'      => (float) $records->sum('actual_weight_kg'),
            'totalEarnings'    => (float) $records->sum('total_amount'),
            'totalPaid'        => (float) $records->sum(fn ($r) => $r->totalPaid()),
            'totalOutstanding' => (float) $records->sum(fn ($r) => $r->balanceDue()),
            'byCrop'           => $byCrop,
        ];
    }

    private function dateRange(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfMonth();

        return [$from, $to];
    }
}
