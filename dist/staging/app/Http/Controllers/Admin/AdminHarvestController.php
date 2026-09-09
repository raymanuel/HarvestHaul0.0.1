<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth;
use App\Models\Harvest;
use App\Models\Crop;
use App\Http\Requests\UpdateBaselinePriceRequest;
use App\Traits\Notifiable;
use App\Http\Controllers\Controller;

class AdminHarvestController extends Controller
{
    use Notifiable;

    // -------------------------------------------------------
    // Harvest Oversight
    public function harvests()
    {
        $harvests = Harvest::with(['farmer', 'crop', 'cropVariety', 'cropCategory'])
            ->orderByRaw("FIELD(status, 'active', 'completed', 'cancelled')")
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.harvests', compact('harvests'));
    }

    public function exportHarvests()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="harvests-export-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Farmer', 'Crop', 'Variety', 'Quantity (kg)', 'Status', 'Created At']);

            Harvest::with(['farmer', 'crop'])
                ->orderBy('id')
                ->chunk(500, function ($harvests) use ($handle) {
                    foreach ($harvests as $h) {
                        fputcsv($handle, [
                            $h->id, $h->farmer->name ?? '—', $h->crop->name ?? $h->crop_type ?? '—',
                            $h->variety ?? '—', $h->quantity_kg, $h->status, $h->created_at,
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // -------------------------------------------------------
    // Update baseline crop price (Admin Override)
    public function updateBaselinePrice(UpdateBaselinePriceRequest $request, Crop $crop)
    {
        $validated = $request->validated();

        $oldPrice = $crop->baseline_price_per_kg;
        $crop->update(['baseline_price_per_kg' => $validated['baseline_price_per_kg']]);

        self::logAudit(
            Auth::id(),
            'updated_baseline_price',
            'crop',
            $crop->id,
            "Baseline price for '{$crop->name}' changed from ₱" . number_format($oldPrice ?? 0, 2) . " to ₱" . number_format($validated['baseline_price_per_kg'], 2) . "/kg."
        );

        return back()->with('success', "Baseline price for '{$crop->name}' updated to ₱" . number_format($validated['baseline_price_per_kg'], 2) . "/kg.");
    }
}
