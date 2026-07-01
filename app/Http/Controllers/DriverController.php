<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PoolingJob;

class DriverController extends Controller
{
    /**
     * Driver Dashboard — lists only jobs assigned to the authenticated driver.
     */
    public function index()
    {
        $user = Auth::user();

        $jobs = PoolingJob::where('driver_id', $user->id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->with(['truck', 'harvests.crop', 'harvests.farmer', 'harvests.destination'])
            ->latest()
            ->get();

        $completedJobs = PoolingJob::where('driver_id', $user->id)
            ->where('status', 'completed')
            ->count();

        return view('driver.driver-view', [
            'jobs'          => $jobs,
            'completedJobs' => $completedJobs,
        ]);
    }

    /**
     * Job Detail View — sequential pickup stops + coordinator notes.
     */
    public function show(PoolingJob $poolingJob)
    {
        $user = Auth::user();

        if ($poolingJob->driver_id !== $user->id) {
            abort(403);
        }

        $poolingJob->load([
            'truck',
            'harvests' => function ($query) {
                $query->orderByPivot('pickup_order');
            },
            'harvests.crop',
            'harvests.farmer.farmerProfile',
            'harvests.destination',
            'logisticsProfile',
        ]);

        return view('driver.driver-job-show', [
            'job' => $poolingJob,
        ]);
    }

    /**
     * Status Checkpoint Update — incremental state transitions only.
     */
    public function updateStatus(Request $request, PoolingJob $poolingJob)
    {
        $user = Auth::user();

        if ($poolingJob->driver_id !== $user->id) {
            abort(403);
        }

        $allowedTransitions = [
            'confirmed'   => 'in_progress',
<<<<<<< HEAD
            'in_progress' => 'awaiting_confirmation',
=======
            'in_progress' => 'completed',
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
        ];

        $currentStatus = $poolingJob->status;

        if (!isset($allowedTransitions[$currentStatus])) {
            return back()->with('error', 'This job cannot be updated further.');
        }

        $newStatus = $allowedTransitions[$currentStatus];

<<<<<<< HEAD
        if ($newStatus === 'awaiting_confirmation') {
            // Check if all stop statuses are delivered
            $poolingJob->load('harvests');
            $allDelivered = true;
            foreach ($poolingJob->harvests as $harvest) {
                if ($harvest->pivot->status !== 'delivered') {
                    $allDelivered = false;
                    break;
                }
            }

            if (!$allDelivered) {
                return back()->with('error', 'Cannot finalize job. All crop stops must be marked as Delivered first.');
            }

            $poolingJob->completed_at = now();
        }

=======
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
        $poolingJob->status = $newStatus;

        if ($newStatus === 'in_progress') {
            $poolingJob->confirmed_at = now();
        }

<<<<<<< HEAD
=======
        if ($newStatus === 'completed') {
            $poolingJob->completed_at = now();
        }

>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
        $poolingJob->save();

        \App\Models\AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'updated_dispatch_status',
            'target_type' => 'pooling_job',
            'target_id'   => $poolingJob->id,
            'notes'       => "Driver " . Auth::user()->name . " updated route #{$poolingJob->id} status from {$currentStatus} to {$newStatus}.",
        ]);

        // Trigger Notifications
        if ($newStatus === 'in_progress') {
            // Notify logistics partner
            if ($poolingJob->logisticsProfile && $poolingJob->logisticsProfile->user_id) {
                \App\Models\Notification::create([
                    'user_id' => $poolingJob->logisticsProfile->user_id,
                    'title' => 'Job In Transit',
                    'message' => "Driver {$user->name} has started Route #{$poolingJob->id}.",
                    'link' => route('pooling.show', $poolingJob)
                ]);
            }
            // Notify farmers
            foreach ($poolingJob->harvests as $harvest) {
                \App\Models\Notification::create([
                    'user_id' => $harvest->user_id,
                    'title' => 'Harvest Shipment In Transit',
                    'message' => "Your harvest '{$harvest->crop->name}' in Route #{$poolingJob->id} is now in transit.",
                    'link' => route('tracking.index')
                ]);
            }
        }

<<<<<<< HEAD
        if ($newStatus === 'awaiting_confirmation') {
=======
        if ($newStatus === 'completed') {
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            // Notify logistics partner
            if ($poolingJob->logisticsProfile && $poolingJob->logisticsProfile->user_id) {
                \App\Models\Notification::create([
                    'user_id' => $poolingJob->logisticsProfile->user_id,
<<<<<<< HEAD
                    'title' => 'Job Awaiting Buyer Confirmation',
                    'message' => "Driver {$user->name} finalized Route #{$poolingJob->id}. Awaiting buyer receipt confirmation.",
=======
                    'title' => 'Job Completed',
                    'message' => "Driver {$user->name} completed Route #{$poolingJob->id}.",
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
                    'link' => route('pooling.show', $poolingJob)
                ]);
            }
            // Notify farmers
            foreach ($poolingJob->harvests as $harvest) {
                \App\Models\Notification::create([
                    'user_id' => $harvest->user_id,
                    'title' => 'Harvest Shipment Delivered',
<<<<<<< HEAD
                    'message' => "Your harvest '{$harvest->crop->name}' in Route #{$poolingJob->id} has been delivered. Awaiting buyer confirmation.",
                    'link' => route('harvests.index')
                ]);
            }
            // Notify buyer to confirm receipt
            if ($poolingJob->buyer_id) {
                \App\Models\Notification::create([
                    'user_id' => $poolingJob->buyer_id,
                    'title' => 'Delivery Ready — Confirm Receipt',
                    'message' => "Your order in Route #{$poolingJob->id} has been delivered. Please confirm receipt.",
                    'link' => route('buyer.tracking')
                ]);
            }
=======
                    'message' => "Your harvest '{$harvest->crop->name}' in Route #{$poolingJob->id} has been delivered successfully.",
                    'link' => route('harvests.index')
                ]);
            }
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
        }

        return back()->with('success', 'Job status updated to ' . ucfirst(str_replace('_', ' ', $newStatus)) . '.');
    }
<<<<<<< HEAD

    /**
     * Update status of an individual harvest stop along the route.
     */
    public function updateStopStatus(Request $request, PoolingJob $poolingJob, $harvestId)
    {
        $user = Auth::user();

        if ($poolingJob->driver_id !== $user->id) {
            abort(403);
        }

        $harvest = $poolingJob->harvests()->findOrFail($harvestId);

        $request->validate([
            'status' => 'required|in:arrived,loaded,delivered',
            
            // Loaded inputs
            'loaded_quantity_kg'           => 'required_if:status,loaded|nullable|numeric|min:0.01|max:999999.99',
            'loaded_volume_cubic_meters'   => 'required_if:status,loaded|nullable|numeric|min:0.01|max:999999.99',
            
            // Delivered inputs
            'delivery_receipt'             => 'required_if:status,delivered|nullable|image|max:10240', // 10MB max
        ]);

        $currentStopStatus = $harvest->pivot->status;
        $targetStatus = $request->status;

        // Validation of correct sequencing per stop
        if ($targetStatus === 'arrived' && $currentStopStatus !== 'assigned') {
            return back()->with('error', 'This stop must be in ASSIGNED status to mark it as ARRIVED.');
        }
        if ($targetStatus === 'loaded' && $currentStopStatus !== 'arrived') {
            return back()->with('error', 'This stop must be in ARRIVED status to mark it as LOADED.');
        }
        if ($targetStatus === 'delivered' && $currentStopStatus !== 'loaded') {
            return back()->with('error', 'This stop must be in LOADED status to mark it as DELIVERED.');
        }

        // Prepare updates
        $pivotUpdates = [
            'status' => $targetStatus,
        ];

        if ($targetStatus === 'loaded') {
            $pivotUpdates['loaded_quantity_kg'] = $request->loaded_quantity_kg;
            $pivotUpdates['loaded_volume_cubic_meters'] = $request->loaded_volume_cubic_meters;

            // Update associated Harvest status to in_progress
            $harvest->update(['status' => 'in_progress']);
        }

        if ($targetStatus === 'delivered') {
            if ($request->hasFile('delivery_receipt')) {
                $file = $request->file('delivery_receipt');
                $path = $file->store('delivery-receipts/' . $poolingJob->id, 'public');
                $pivotUpdates['delivery_receipt_path'] = $path;
            }

            // Update associated Harvest status to completed
            $harvest->update(['status' => 'completed']);
        }

        // Save pivot
        $poolingJob->harvests()->updateExistingPivot($harvest->id, $pivotUpdates);

        // Audit Log
        \App\Models\AuditLog::create([
            'admin_id'    => $user->id,
            'action'      => 'driver_stop_status_update',
            'target_type' => 'pooling_job_harvests',
            'target_id'   => $poolingJob->id,
            'notes'       => "Driver {$user->name} updated stop (Harvest #{$harvest->id}) status to {$targetStatus} in Route #{$poolingJob->id}.",
        ]);

        // Notifications
        if ($targetStatus === 'arrived') {
            \App\Models\Notification::create([
                'user_id' => $harvest->user_id,
                'title' => 'Driver Arrived at Pickup',
                'message' => "Driver {$user->name} has arrived at your farm to pick up '{$harvest->crop->name}'.",
                'link' => route('tracking.index')
            ]);
        }

        if ($targetStatus === 'loaded') {
            \App\Models\Notification::create([
                'user_id' => $harvest->user_id,
                'title' => 'Harvest Loaded',
                'message' => "Driver {$user->name} loaded {$request->loaded_quantity_kg} kg of '{$harvest->crop->name}' from your farm.",
                'link' => route('tracking.index')
            ]);
        }

        if ($targetStatus === 'delivered') {
            // Notify farmer
            \App\Models\Notification::create([
                'user_id' => $harvest->user_id,
                'title' => 'Harvest Delivered to Buyer',
                'message' => "Your harvest '{$harvest->crop->name}' has been delivered to the drop-off location.",
                'link' => route('harvests.index')
            ]);

            // Notify buyer if this was a B2B negotiation purchase
            $negotiation = \App\Models\Negotiation::where('harvest_id', $harvest->id)
                ->where('status', 'COMPLETED')
                ->first();

            if ($negotiation) {
                \App\Models\Notification::create([
                    'user_id' => $negotiation->buyer_id,
                    'title' => 'Purchase Delivered',
                    'message' => "Your purchased lot of '{$harvest->crop->name}' ({$negotiation->negotiated_volume} kg) has been delivered.",
                    'link' => route('buyer.negotiations')
                ]);
            }
        }

        return back()->with('success', 'Stop status updated to ' . strtoupper($targetStatus) . '.');
    }

    /**
     * Store a new fuel log for the truck assigned to this job.
     */
    public function storeFuelLog(Request $request, PoolingJob $poolingJob)
    {
        $user = Auth::user();

        if ($poolingJob->driver_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'fuel_liters'      => 'required|numeric|min:0.01|max:9999.99',
            'cost'             => 'required|numeric|min:0.01|max:999999.99',
            'odometer_reading' => 'required|numeric|min:0.01|max:9999999.99',
        ]);

        \App\Models\FuelLog::create([
            'driver_id'        => $user->id,
            'truck_id'         => $poolingJob->truck_id,
            'fuel_liters'      => $request->fuel_liters,
            'cost'             => $request->cost,
            'odometer_reading' => $request->odometer_reading,
        ]);

        \App\Models\AuditLog::create([
            'admin_id'    => $user->id,
            'action'      => 'driver_logged_fuel',
            'target_type' => 'fuel_logs',
            'target_id'   => $poolingJob->truck_id,
            'notes'       => "Driver {$user->name} logged {$request->fuel_liters}L of fuel (Cost: ₱{$request->cost}) for Truck #{$poolingJob->truck_id} at {$request->odometer_reading} km.",
        ]);

        return back()->with('success', 'Fuel purchase logged successfully.');
    }
=======
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
}
