<?php

namespace App\Http\Controllers;

use App\Models\PoolingJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UploadReceiptRequest;
use App\Http\Requests\ConfirmQuantityRequest;

class CostLedgerController extends Controller
{
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
            ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
            ->latest()
            ->paginate(15);

        return view('logistics.cost-ledger-index', compact('jobs'));
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
                'receipt_path'    => $harvest->pivot->receipt_path,
            ];
        })->sortBy('pickup_order')->values();

        $totalPrice  = (float) ($poolingJob->negotiated_price ?? $poolingJob->price_reference ?? 0);
        $sumOfShares = $ledgerEntries->sum('cost_share');
        $costMismatch = $totalPrice > 0 && $sumOfShares > 0 && abs($totalPrice - $sumOfShares) > 0.01;

        return view('logistics.cost-ledger', compact(
            'poolingJob', 'ledgerEntries', 'totalPrice', 'sumOfShares', 'isOwner', 'isFarmer', 'costMismatch'
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
        $allowedStatuses = ['confirmed', 'in_progress', 'awaiting_confirmation'];
        if (!in_array($poolingJob->status, $allowedStatuses)) {
            return back()->with('error', 'Cannot upload receipt. Job must be confirmed, in progress, or awaiting confirmation.');
        }

        if ($request->hasFile('payment_receipt')) {
            $file = $request->file('payment_receipt');

            // Validate minimum file size (1KB) to prevent empty files
            if ($file->getSize() < 1024) {
                return back()->with('error', 'Receipt file is too small. Please upload a valid receipt image.');
            }

            // Basic image validation: ensure it's a valid image (not a renamed .exe)
            if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])) {
                return back()->with('error', 'Invalid file type. Please upload a valid receipt (JPG, PNG, or PDF).');
            }

            $path = $file->store('payment-receipts/' . $poolingJob->id, 'public');

            $poolingJob->harvests()->updateExistingPivot($harvest->id, [
                'payment_status' => 'submitted',
                'receipt_path'   => $path,
            ]);

            \App\Models\AuditLog::create([
                'admin_id'    => $user->id,
                'action'      => 'farmer_payment_receipt_uploaded',
                'target_type' => 'pooling_job_harvests',
                'target_id'   => $poolingJob->id,
                'notes'       => "Farmer {$user->name} uploaded payment receipt for Harvest #{$harvest->id} on Route #{$poolingJob->id}.",
            ]);

            // Notify logistics partner
            if ($poolingJob->logisticsProfile && $poolingJob->logisticsProfile->user_id) {
                \App\Models\Notification::create([
                    'user_id' => $poolingJob->logisticsProfile->user_id,
                    'title' => 'Payment Receipt Submitted',
                    'message' => "Farmer {$user->name} submitted a payment receipt for Route #{$poolingJob->id}.",
                    'link' => route('pooling.cost-ledger', $poolingJob)
                ]);
            }

            return back()->with('success', 'Payment receipt uploaded successfully.');
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

        $poolingJob->harvests()->updateExistingPivot($harvest->id, [
            'payment_status' => 'paid',
        ]);

        \App\Models\AuditLog::create([
            'admin_id'    => $user->id,
            'action'      => 'logistics_marked_payment_paid',
            'target_type' => 'pooling_job_harvests',
            'target_id'   => $poolingJob->id,
            'notes'       => "Logistics Partner {$user->name} marked payment as Paid for Harvest #{$harvest->id} on Route #{$poolingJob->id}.",
        ]);

        // Notify farmer
        \App\Models\Notification::create([
            'user_id' => $harvest->user_id,
            'title' => 'Payment Received & Verified',
            'message' => "Your freight cost payment for '{$harvest->crop->name}' on Route #{$poolingJob->id} has been verified and marked as paid.",
            'link' => route('pooling.cost-ledger', $poolingJob)
        ]);

        return back()->with('success', 'Payment marked as Paid.');
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

        \App\Models\AuditLog::create([
            'admin_id'    => $user->id,
            'action'      => 'farmer_confirmed_quantity',
            'target_type' => 'pooling_job_harvests',
            'target_id'   => $poolingJob->id,
            'notes'       => "Farmer {$user->name} confirmed actual quantity {$validated['actual_quantity_kg']} kg for Harvest #{$harvest->id} on Route #{$poolingJob->id}.",
        ]);

        if ($poolingJob->logisticsProfile && $poolingJob->logisticsProfile->user_id) {
            \App\Models\Notification::create([
                'user_id' => $poolingJob->logisticsProfile->user_id,
                'title'   => 'Actual Quantity Confirmed',
                'message' => "Farmer {$user->name} confirmed actual quantity of {$validated['actual_quantity_kg']} kg for Route #{$poolingJob->id}.",
                'link'    => route('pooling.cost-ledger', $poolingJob),
            ]);
        }

        return back()->with('success', 'Actual quantity confirmed successfully.');
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

            // Completed jobs and revenue for this truck (from pre-grouped collection)
            $completedJobs = $completedJobsByTruck->get($truck->id, collect());

            $totalRevenue = (float) $completedJobs->sum(function ($job) {
                return (float) ($job->negotiated_price ?? $job->price_reference ?? 0);
            });

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
                'revenue'           => $totalRevenue,
                'net_income'        => $totalRevenue - $totalFuelCost,
            ];
        });

        // Overall summary metrics
        $totalRefuels     = $fuelLogs->count();
        $totalFuelCost    = $fuelLogs->sum('cost');
        $totalFuelLiters  = $fuelLogs->sum('fuel_liters');
        
        $totalRevenue = (float) $completedJobsByTruck->flatten()->sum(function ($job) {
            return (float) ($job->negotiated_price ?? $job->price_reference ?? 0);
        });

        return view('logistics.analytics', compact(
            'truckAnalytics', 'fuelLogs', 'totalRefuels', 'totalFuelCost', 
            'totalFuelLiters', 'totalRevenue'
        ));
    }
}
