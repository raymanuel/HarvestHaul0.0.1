<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PoolingJob;
use App\Traits\GeometryHelper;

class DriverController extends Controller
{
    use GeometryHelper;

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

        // Eager load crops for notification messages in both branches
        $poolingJob->load('harvests.crop');

        $allowedTransitions = [
            'confirmed'   => 'in_progress',
            'in_progress' => 'awaiting_confirmation',
        ];

        $currentStatus = $poolingJob->status;

        if (!isset($allowedTransitions[$currentStatus])) {
            return back()->with('error', 'This job cannot be updated further.');
        }

        $newStatus = $allowedTransitions[$currentStatus];

        if ($newStatus === 'in_progress') {
            // Verify GPS tracking is actively sending data (at least 1 ping)
            $hasTracking = \App\Models\TrackingRecord::where('pooling_job_id', $poolingJob->id)->exists();
            if (!$hasTracking) {
                return back()->with('error', 'Cannot start trip. No GPS data received yet. Enable location tracking first.');
            }
        }

        if ($newStatus === 'awaiting_confirmation') {
            // Validate end-of-trip odometer reading
            $request->validate([
                'end_odometer_reading' => 'required|numeric|min:0.01|max:9999999.99',
            ]);

            // Check if all stop statuses are delivered
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
            $poolingJob->end_odometer_reading = $request->end_odometer_reading;

            // Calculate actual distance from tracking records
            $trackingRecords = \App\Models\TrackingRecord::where('pooling_job_id', $poolingJob->id)
                ->orderBy('posted_at')
                ->get();
            $actualDistance = 0.0;
            $prevLat = null;
            $prevLng = null;
            foreach ($trackingRecords as $record) {
                $lat = (float) $record->latitude;
                $lng = (float) $record->longitude;
                if ($prevLat !== null && $prevLng !== null) {
                    $actualDistance += $this->haversine($prevLat, $prevLng, $lat, $lng);
                }
                $prevLat = $lat;
                $prevLng = $lng;
            }
            $poolingJob->actual_distance_km = round($actualDistance, 2);
        }

        $poolingJob->status = $newStatus;
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

        if ($newStatus === 'awaiting_confirmation') {
            // Notify logistics partner
            if ($poolingJob->logisticsProfile && $poolingJob->logisticsProfile->user_id) {
                \App\Models\Notification::create([
                    'user_id' => $poolingJob->logisticsProfile->user_id,
                    'title' => 'Job Awaiting Buyer Confirmation',
                    'message' => "Driver {$user->name} finalized Route #{$poolingJob->id}. Awaiting buyer receipt confirmation.",
                    'link' => route('pooling.show', $poolingJob)
                ]);
            }
            // Notify farmers
            foreach ($poolingJob->harvests as $harvest) {
                \App\Models\Notification::create([
                    'user_id' => $harvest->user_id,
                    'title' => 'Harvest Shipment Delivered',
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
        }

        return back()->with('success', 'Job status updated to ' . ucfirst(str_replace('_', ' ', $newStatus)) . '.');
    }

    /**
     * Update status of an individual harvest stop along the route.
     */
    public function updateStopStatus(Request $request, PoolingJob $poolingJob, $harvestId)
    {
        $user = Auth::user();

        if ($poolingJob->driver_id !== $user->id) {
            abort(403);
        }

        $harvest = $poolingJob->harvests()->with('crop')->findOrFail($harvestId);

        $request->validate([
            'status' => 'required|in:arrived,loaded,delivered',
            
            // Loaded inputs
            'loaded_quantity_kg'           => 'required_if:status,loaded|nullable|numeric|min:0.01|max:999999.99',
            'loaded_volume_cubic_meters'   => 'required_if:status,loaded|nullable|numeric|min:0.01|max:999999.99',
            'load_photo'                   => 'required_if:status,loaded|nullable|image|max:10240',
            'crop_confirmed'               => 'required_if:status,loaded|nullable|boolean',
            
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

        // Geofence check: verify driver is at/near the farm GPS when marking arrived
        if ($targetStatus === 'arrived') {
            $latestTracking = \App\Models\TrackingRecord::where('pooling_job_id', $poolingJob->id)
                ->latest('id')
                ->first();

            if (!$latestTracking) {
                return back()->with('error', 'No GPS tracking data available. Enable location tracking and try again.');
            }

            $driverLat = (float) $latestTracking->latitude;
            $driverLng = (float) $latestTracking->longitude;
            $farmLat = (float) $harvest->latitude;
            $farmLng = (float) $harvest->longitude;

            if ($farmLat && $farmLng) {
                $distFromFarm = $this->haversine($driverLat, $driverLng, $farmLat, $farmLng);
                if ($distFromFarm > 0.5) { // 500m geofence
                    return back()->with('error', 'You must be at the farm location (within 500m) to mark as arrived. Current distance: ' . round($distFromFarm, 2) . ' km.');
                }
            }
        }

        // Validate loaded quantity doesn't exceed harvest quantity
        if ($targetStatus === 'loaded') {
            if ((float) $request->loaded_quantity_kg > (float) $harvest->pivot->quantity_kg) {
                return back()->with('error', 'Loaded quantity (' . $request->loaded_quantity_kg . ' kg) cannot exceed job allocation (' . $harvest->pivot->quantity_kg . ' kg).');
            }

            // Driver must confirm crop matches listing
            if (!$request->boolean('crop_confirmed')) {
                return back()->with('error', 'You must confirm the crop matches the listing before marking as loaded.');
            }
        }

        // Require delivery receipt on delivered status
        if ($targetStatus === 'delivered') {
            if (!$request->hasFile('delivery_receipt')) {
                return back()->with('error', 'A delivery receipt photo is required to mark as delivered.');
            }
        }

        // Prepare updates
        $pivotUpdates = [
            'status' => $targetStatus,
        ];

        if ($targetStatus === 'arrived') {
            $pivotUpdates['arrived_at'] = now();
        }

        if ($targetStatus === 'loaded') {
            if ($request->hasFile('load_photo')) {
                $file = $request->file('load_photo');
                $path = $file->store('load-photos/' . $poolingJob->id, 'public');
                $pivotUpdates['load_photo_path'] = $path;
            }

            $pivotUpdates['loaded_quantity_kg'] = $request->loaded_quantity_kg;
            $pivotUpdates['loaded_volume_cubic_meters'] = $request->loaded_volume_cubic_meters;
            $pivotUpdates['crop_confirmed'] = $request->boolean('crop_confirmed');
            $pivotUpdates['loaded_at'] = now();

            $harvest->update(['status' => 'in_progress']);
        }

        if ($targetStatus === 'delivered') {
            if ($request->hasFile('delivery_receipt')) {
                $file = $request->file('delivery_receipt');
                $path = $file->store('delivery-receipts/' . $poolingJob->id, 'public');
                $pivotUpdates['delivery_receipt_path'] = $path;
            }

            $pivotUpdates['delivered_at'] = now();

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
                    'message' => "Your purchased product of '{$harvest->crop->name}' ({$negotiation->negotiated_volume} kg) has been delivered.",
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

        // Prevent duplicate odometer readings for the same truck
        $duplicateOdo = \App\Models\FuelLog::where('truck_id', $poolingJob->truck_id)
            ->where('odometer_reading', $request->odometer_reading)
            ->exists();
        if ($duplicateOdo) {
            return back()->with('error', 'A fuel log with this odometer reading already exists for this truck.');
        }

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

    /**
     * Upload ID photo + selfie for driver identity verification.
     */
    public function uploadIdentity(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'id_photo' => 'required|image|max:5120',
            'selfie'   => 'required|image|max:5120',
        ]);

        $profile = $user->driverProfile;
        if (!$profile) {
            return back()->with('error', 'No driver profile found.');
        }

        $idPath = $request->file('id_photo')->store('driver-ids/' . $user->id, 'public');
        $selfiePath = $request->file('selfie')->store('driver-selfies/' . $user->id, 'public');

        $profile->update([
            'id_photo_path' => $idPath,
            'selfie_path' => $selfiePath,
            'identity_verified' => false, // reset to pending review
        ]);

        \App\Models\Notification::create([
            'user_id' => $profile->partner?->user_id,
            'title' => 'Driver Identity Documents Uploaded',
            'message' => "Driver {$user->name} uploaded identity documents for verification.",
            'link' => route('logistics.drivers.index'),
        ]);

        return back()->with('success', 'Identity documents uploaded. Pending admin verification.');
    }

    public function acceptJob(PoolingJob $poolingJob)
    {
        $user = Auth::user();

        if ($poolingJob->driver_id !== $user->id) {
            abort(403);
        }

        if ($poolingJob->status !== 'confirmed') {
            return back()->with('error', 'Job is not in confirmed status.');
        }

        if ($poolingJob->accepted_at) {
            return back()->with('error', 'Job already accepted.');
        }

        $poolingJob->update(['accepted_at' => now()]);

        \App\Models\AuditLog::create([
            'admin_id'    => $user->id,
            'action'      => 'driver_accepted_job',
            'target_type' => 'pooling_job',
            'target_id'   => $poolingJob->id,
            'notes'       => "Driver {$user->name} accepted Route #{$poolingJob->id}.",
        ]);

        \App\Models\Notification::create([
            'user_id' => $poolingJob->logisticsProfile?->user_id,
            'title'   => 'Driver Accepted Job',
            'message' => "Driver {$user->name} has accepted Route #{$poolingJob->id}.",
            'link'    => route('pooling.show', $poolingJob),
        ]);

        return back()->with('success', 'Job accepted successfully.');
    }
}
