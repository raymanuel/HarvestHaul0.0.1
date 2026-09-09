<?php

namespace App\Http\Controllers;

use App\Models\InvoiceStatus;
use App\Models\PoolingJob;
use App\Traits\Notifiable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UploadReceiptRequest;
use App\Http\Requests\ConfirmQuantityRequest;

class CostLedgerController extends Controller
{
    use Notifiable;

    /**
     * List all pooling jobs for this logistics partner (ledger index).
     */
    public function index()
    {
        $user    = Auth::user();
        $profile = $user->logisticsProfile;

        if (!$profile) abort(403);

        $jobs = PoolingJob::where('logistics_profile_id', $profile->id)
            ->with(['truck', 'harvests.crop', 'harvests.farmer'])
            ->whereIn('status', ['confirmed', 'in_progress', 'awaiting_confirmation', 'completed'])
            ->latest()
            ->paginate(15);

        return view('logistics.cost-ledger-index', compact('jobs'));
    }

    /**
     * List all pooling jobs a farmer participates in (ledger index for farmers).
     */
    public function farmerIndex()
    {
        $jobs = PoolingJob::whereIn('status', ['confirmed', 'in_progress', 'completed'])
            ->whereHas('harvests', fn($q) => $q->where('user_id', Auth::id()))
            ->with(['truck', 'logisticsProfile', 'harvests' => function ($q) {
                $q->where('user_id', Auth::id())->with(['crop', 'cropVariety', 'destination']);
            }])
            ->latest()
            ->paginate(15);

        return view('farmers.farmer-cost-ledger-index', compact('jobs'));
    }

    /**
     * Show the proportional cost breakdown for a pooling job.
     * Accessible by:
     *   - The logistics partner who owns the job
     *   - A farmer whose harvest is included in the job
     */
    public function show(PoolingJob $poolingJob)
    {
        $user = Auth::user();

        // Authorization: logistics owner OR participating farmer
        $isOwner = $user->role === 'logistics_partner'
            && $user->logisticsProfile
            && $poolingJob->logistics_profile_id === $user->logisticsProfile->id;

        $isFarmer = $user->role === 'farmer'
            && $poolingJob->harvests()->where('user_id', $user->id)->exists();

        if (!$isOwner && !$isFarmer) {
            abort(403, 'Unauthorized access to cost ledger.');
        }

        // Load all harvests with pivot data + farmer info
        $poolingJob->load([
            'harvests' => function ($q) {
                $q->with(['farmer', 'crop', 'cropVariety', 'destination']);
            },
            'logisticsProfile',
            'truck',
        ]);

        // Build per-farmer cost ledger entries
        $ledgerEntries = $poolingJob->harvests->map(function ($harvest) use ($poolingJob) {
            $harvestKg    = (float) $harvest->pivot->quantity_kg;
            $totalKg      = (float) $poolingJob->total_kg;
            $basePrice    = (float) ($poolingJob->negotiated_price ?? $poolingJob->price_reference ?? 0);
            $proportion   = $totalKg > 0 ? $harvestKg / $totalKg : 0;

            // Use stored cost_share if available, otherwise compute on-the-fly
            $costShare = $harvest->pivot->cost_share !== null
                ? (float) $harvest->pivot->cost_share
                : round($basePrice * $proportion, 2);

            return [
                'harvest_id'      => $harvest->id,
                'farmer_id'       => $harvest->user_id,
                'farmer_name'   => $harvest->farmer->name ?? '—',
                'crop'          => $harvest->crop->name ?? $harvest->crop_type ?? '—',
                'variety'       => $harvest->cropVariety->name ?? '—',
                'quantity_kg'   => $harvestKg,
                'proportion'    => round($proportion * 100, 1),
                'cost_share'    => $costShare,
                'destination'   => $harvest->destination->name ?? $harvest->destination_address ?? '—',
                'pickup_order'  => $harvest->pivot->pickup_order,
                'payment_status'  => $harvest->pivot->payment_status ?? 'unpaid',
                'amount_paid'     => $harvest->pivot->amount_paid,
                'receipt_path'    => $harvest->pivot->receipt_path,
            ];
        })->sortBy('pickup_order')->values();

        $sumOfShares = $ledgerEntries->sum('cost_share');
        // Per-farmer cost shares are the authoritative total (mirrors InvoiceService);
        // job-level price fields are only the reference when no shares exist yet.
        $totalPrice = $sumOfShares > 0
            ? (float) $sumOfShares
            : (float) ($poolingJob->negotiated_price ?? $poolingJob->price_reference ?? 0);

        // Freight invoice issued at route confirmation (payment reference)
        $invoice = $poolingJob->invoices()->latest()->first();

        return view('logistics.cost-ledger', compact(
            'poolingJob', 'ledgerEntries', 'totalPrice', 'sumOfShares', 'isOwner', 'isFarmer', 'invoice'
        ));
    }

    /**
     * Upload payment receipt (Farmer action).
     */
    public function uploadReceipt(UploadReceiptRequest $request, PoolingJob $poolingJob, $harvestId)
    {
        $user = Auth::user();

        // Check if the user is the farmer for this harvest stop
        $harvest = $poolingJob->harvests()->with('crop')->findOrFail($harvestId);

        if ($harvest->user_id !== $user->id) {
            abort(403, 'Only the participating farmer can upload payment receipt.');
        }

        // Verify job is in an appropriate status for receipt upload
        $allowedStatuses = ['confirmed', 'in_progress', 'awaiting_confirmation', 'completed'];
        if (!in_array($poolingJob->status->value, $allowedStatuses)) {
            return back()->with('error', 'Cannot upload receipt. Job must be confirmed, in progress, awaiting confirmation, or completed.');
        }

        if ($request->hasFile('payment_receipt')) {
            $file = $request->file('payment_receipt');
            $path = $file->store('payment-receipts/' . $poolingJob->id, 'local');

            $poolingJob->harvests()->updateExistingPivot($harvest->id, [
                'payment_status' => 'submitted',
                'receipt_path'   => $path,
            ]);

            self::logAudit(
                $user->id,
                'farmer_payment_receipt_uploaded',
                'pooling_job_harvests',
                $poolingJob->id,
                "Farmer {$user->name} uploaded payment receipt for Harvest #{$harvest->id} on Route #{$poolingJob->id}."
            );

            if ($poolingJob->logisticsProfile && $poolingJob->logisticsProfile->user_id) {
                self::notifyReceiptSubmitted(
                    $poolingJob->logisticsProfile->user_id,
                    $user->name,
                    $poolingJob->id
                );
            }

            return back()->with('success', 'Payment receipt uploaded successfully.')
                ->with('next_steps', [
                    'title'   => 'Receipt uploaded',
                    'message' => 'Payment receipt uploaded successfully.',
                    'steps'   => [
                        'Your payment evidence is now pending verification by logistics.',
                        'Logistics will review and mark the payment as Paid.',
                        'You will be notified once your payment is confirmed.',
                    ],
                ]);
        }

        return back()->with('error', 'Please select a valid image file.');
    }

    /**
     * Mark payment as verified and Paid (Logistics coordinator action).
     */
    public function markPaid(Request $request, PoolingJob $poolingJob, $harvestId)
    {
        $user = Auth::user();

        // Check if user is the logistics partner who owns the job
        $isOwner = $user->role === 'logistics_partner'
            && $user->logisticsProfile
            && $poolingJob->logistics_profile_id === $user->logisticsProfile->id;

        if (!$isOwner) {
            abort(403, 'Only the logistics partner can mark this invoice as paid.');
        }

        $harvest = $poolingJob->harvests()->with('crop')->findOrFail($harvestId);

        // Prevent marking as paid if already paid
        if ($harvest->pivot->payment_status === 'paid') {
            return back()->with('error', 'Payment is already marked as paid. No changes made.');
        }

        // Verify receipt was uploaded before marking as paid
        if (!$harvest->pivot->receipt_path) {
            return back()->with('error', 'Cannot mark as paid. Farmer has not uploaded a payment receipt yet.');
        }

        $validated = $request->validate([
            'amount_paid' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $poolingJob->harvests()->updateExistingPivot($harvest->id, [
            'payment_status' => 'paid',
            'amount_paid'    => $validated['amount_paid'] ?? null,
        ]);

        // When every stop on the job is paid, settle the freight invoice
        $allPaid = !$poolingJob->harvests()
            ->wherePivot('payment_status', '!=', 'paid')
            ->exists();
        if ($allPaid) {
            $invoice = app(\App\Services\InvoiceService::class)->getOrCreateInvoice($poolingJob);
            if ($invoice->status !== InvoiceStatus::PAID) {
                $invoice->update(['status' => 'paid', 'paid_at' => now()]);
            }
        }

        self::logAudit(
            $user->id,
            'logistics_marked_payment_paid',
            'pooling_job_harvests',
            $poolingJob->id,
            "Logistics Partner {$user->name} marked payment as Paid for Harvest #{$harvest->id} on Route #{$poolingJob->id}."
        );

        // Notify farmer
        self::notifyPaymentVerified(
            $harvest->user_id,
            $harvest->crop->name,
            $poolingJob->id
        );

        return back()->with('success', 'Payment marked as Paid.')
            ->with('next_steps', [
                'title'   => 'Payment settled',
                'message' => 'Payment marked as Paid.',
                'steps'   => [
                    'This stop is now settled and the farmer is notified.',
                    'When every stop on the route is paid, the full freight invoice automatically settles.',
                    'You can download the invoice to keep a record.',
                ],
            ]);
    }

    /**
     * Farmer confirms the actual loaded quantity for their harvest.
     */
    public function confirmQuantity(ConfirmQuantityRequest $request, PoolingJob $poolingJob, $harvestId)
    {
        $user = Auth::user();

        $harvest = $poolingJob->harvests()->findOrFail($harvestId);

        if ($harvest->user_id !== $user->id) {
            abort(403, 'Only the farmer who owns this harvest can confirm quantity.');
        }

        // Idempotency guard — prevent progressive quantity reduction
        if ($harvest->pivot->farmer_qty_confirmed) {
            return back()->with('error', 'Quantity already confirmed for this harvest. Cannot confirm again.');
        }

        // Use pivot quantity_kg (original posted amount) as the max, not the harvest record
        $originalQuantity = (float) ($harvest->pivot->quantity_kg ?: $harvest->quantity_kg);
        $validated = $request->validated();

        if ((float) $validated['actual_quantity_kg'] > $originalQuantity) {
            return back()->with('error', 'Confirmed quantity cannot exceed the posted harvest quantity of ' . $originalQuantity . ' kg.');
        }

        $poolingJob->harvests()->updateExistingPivot($harvest->id, [
            'actual_quantity_kg'   => $validated['actual_quantity_kg'],
            'farmer_qty_confirmed' => true,
        ]);

        self::logAudit(
            $user->id,
            'farmer_confirmed_quantity',
            'pooling_job_harvests',
            $poolingJob->id,
            "Farmer {$user->name} confirmed actual quantity {$validated['actual_quantity_kg']} kg for Harvest #{$harvest->id} on Route #{$poolingJob->id}."
        );

        if ($poolingJob->logisticsProfile && $poolingJob->logisticsProfile->user_id) {
            self::notifyQuantityConfirmed(
                $poolingJob->logisticsProfile->user_id,
                $user->name,
                $validated['actual_quantity_kg'],
                $poolingJob->id
            );
        }

        return back()->with('success', 'Actual quantity confirmed successfully.')
            ->with('next_steps', [
                'title'   => 'Quantity confirmed',
                'message' => 'Your actual delivered quantity was confirmed on the cost ledger.',
                'steps'   => [
                    'The logistics partner will review the confirmed quantity to verify the route cost share.',
                    'Once your hauling receipt is uploaded and approved, your payment will be marked as paid.',
                    'Check back on the cost ledger for your payment status.',
                ],
                'cta' => ['label' => 'View Cost Ledger', 'url' => route('pooling.cost-ledger', $poolingJob)],
            ]);
    }

    /**
     * Show fleet fuel tracking ledger and revenue per vehicle analytics.
     */
    public function fleetAnalytics()
    {
        $user = Auth::user();
        if ($user->role !== 'logistics_partner') abort(403);

        $profile = $user->logisticsProfile;
        if (!$profile) abort(403);

        // Load all trucks for this logistics partner
        $trucks = $profile->trucks()->get();

        // Fuel Tracking Ledger: Get all fuel logs for these trucks
        $truckIds = $trucks->pluck('id');
        
        $fuelLogs = \App\Models\FuelLog::whereIn('truck_id', $truckIds)
            ->with(['truck', 'driver'])
            ->orderBy('created_at', 'desc')
            ->take(500)
            ->get();

        // Eager-load completed jobs per truck to avoid N+1 inside map()
        $completedJobsByTruck = PoolingJob::whereIn('truck_id', $truckIds)
            ->where('status', 'completed')
            ->take(200)
            ->get()
            ->groupBy('truck_id');

        // Calculate metrics per truck
        $truckAnalytics = $trucks->map(function ($truck) use ($fuelLogs, $completedJobsByTruck) {
            // Fuel logs for this truck (filter from already-loaded collection)
            $logs = $fuelLogs->where('truck_id', $truck->id);

            $totalFuelLiters = (float) $logs->sum('fuel_liters');
            $totalFuelCost   = (float) $logs->sum('cost');
            
            // Calculate KPL (Kilometers per Liter)
            $kpl = 0;
            if ($logs->count() > 1) {
                $minOdo = (float) $logs->last()->odometer_reading;
                $maxOdo = (float) $logs->first()->odometer_reading;
                $distance = $maxOdo - $minOdo;
                if ($totalFuelLiters > 0) {
                    $kpl = $distance / $totalFuelLiters;
                }
            }

            // Completed jobs for this truck (from pre-grouped collection)
            $completedJobs = $completedJobsByTruck->get($truck->id, collect());

            return [
                'id'                => $truck->id,
                'truck_name'        => $truck->truck_name,
                'plate_number'      => $truck->plate_number,
                'capacity_kg'       => $truck->capacity_kg,
                'total_refuels'     => $logs->count(),
                'total_fuel_liters' => $totalFuelLiters,
                'total_fuel_cost'   => $totalFuelCost,
                'kpl'               => round($kpl, 2),
                'completed_trips'   => $completedJobs->count(),
            ];
        });

        // Overall summary metrics
        $totalRefuels     = $fuelLogs->count();
        $totalFuelCost    = $fuelLogs->sum('cost');
        $totalFuelLiters  = $fuelLogs->sum('fuel_liters');

        return view('logistics.analytics', compact(
            'truckAnalytics', 'fuelLogs', 'totalRefuels',
            'totalFuelCost', 'totalFuelLiters'
        ));
    }
}
