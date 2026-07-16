<?php

namespace App\Http\Controllers;

use App\Actions\UpdateStopStatusAction;
use App\Http\Requests\UpdateStopStatusRequest;
use App\Http\Requests\StoreFuelLogRequest;
use App\Http\Requests\UploadIdentityRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
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
            ->take(20)
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
            PoolingJobStatus::CONFIRMED   => PoolingJobStatus::IN_PROGRESS,
            PoolingJobStatus::IN_PROGRESS => PoolingJobStatus::AWAITING_CONFIRMATION,
        ];

        $currentStatus = $poolingJob->status;

        if (!isset($allowedTransitions[$currentStatus])) {
            return back()->with('error', 'This job cannot be updated further.');
        }

        $newStatus = $allowedTransitions[$currentStatus];

        if ($newStatus === PoolingJobStatus::IN_PROGRESS) {
            $hasTracking = \App\Models\TrackingRecord::where('pooling_job_id', $poolingJob->id)->exists();
            if (!$hasTracking) {
                return back()->with('error', 'Cannot start trip. No GPS data received yet. Enable location tracking first.');
            }
        }

        if ($newStatus === PoolingJobStatus::AWAITING_CONFIRMATION) {
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
            'notes'       => "Driver " . Auth::user()->name . " updated route #{$poolingJob->id} status from {$currentStatus->value} to {$newStatus->value}.",
        ]);

        // Trigger Notifications
        if ($newStatus === PoolingJobStatus::IN_PROGRESS) {
            // Notify logistics partner
            if ($poolingJob->logisticsProfile && $poolingJob->logisticsProfile->user_id) {
                \App\Models\Notification::create([
                    'user_id' => $poolingJob->logisticsProfile->user_id,
                    'title' => 'Job In Transit',
                    'message' => "Driver {$user->name} has started Route #{$poolingJob->id}.",
                    'link' => route('pooling.show', $poolingJob)
                ]);
            }
            // Notify farmers in bulk
            $notifications = [];
            foreach ($poolingJob->harvests as $harvest) {
                $notifications[] = [
                    'user_id'    => $harvest->user_id,
                    'title'      => 'Harvest Shipment In Transit',
                    'message'    => "Your harvest '{$harvest->crop->name}' in Route #{$poolingJob->id} is now in transit.",
                    'link'       => route('tracking.index'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($notifications)) {
                \App\Models\Notification::insert($notifications);
            }
        }

        if ($newStatus === PoolingJobStatus::AWAITING_CONFIRMATION) {
            // Notify logistics partner
            if ($poolingJob->logisticsProfile && $poolingJob->logisticsProfile->user_id) {
                \App\Models\Notification::create([
                    'user_id' => $poolingJob->logisticsProfile->user_id,
                    'title' => 'Job Awaiting Buyer Confirmation',
                    'message' => "Driver {$user->name} finalized Route #{$poolingJob->id}. Awaiting buyer receipt confirmation.",
                    'link' => route('pooling.show', $poolingJob)
                ]);
            }
            // Notify farmers in bulk
            $notifications = [];
            foreach ($poolingJob->harvests as $harvest) {
                $notifications[] = [
                    'user_id'    => $harvest->user_id,
                    'title'      => 'Harvest Shipment Delivered',
                    'message'    => "Your harvest '{$harvest->crop->name}' in Route #{$poolingJob->id} has been delivered. Awaiting buyer confirmation.",
                    'link'       => route('harvests.index'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($notifications)) {
                \App\Models\Notification::insert($notifications);
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
    public function updateStopStatus(UpdateStopStatusRequest $request, PoolingJob $poolingJob, $harvestId)
    {
        $user = Auth::user();

        if ($poolingJob->driver_id !== $user->id) {
            abort(403);
        }

        $harvest = $poolingJob->harvests()->with('crop')->findOrFail($harvestId);

        try {
            app(UpdateStopStatusAction::class)->execute($poolingJob, $harvest, $request->validated(), $user);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Stop status updated to ' . strtoupper($request->validated()['status']) . '.');
    }

    /**
     * Store a new fuel log for the truck assigned to this job.
     */
    public function storeFuelLog(StoreFuelLogRequest $request, PoolingJob $poolingJob)
    {
        $user = Auth::user();

        if ($poolingJob->driver_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validated();

        // Prevent duplicate odometer readings for the same truck
        $duplicateOdo = \App\Models\FuelLog::where('truck_id', $poolingJob->truck_id)
            ->where('odometer_reading', $validated['odometer_reading'])
            ->exists();
        if ($duplicateOdo) {
            return back()->with('error', 'A fuel log with this odometer reading already exists for this truck.');
        }

        \App\Models\FuelLog::create([
            'driver_id'        => $user->id,
            'truck_id'         => $poolingJob->truck_id,
            'fuel_liters'      => $validated['fuel_liters'],
            'cost'             => $validated['cost'],
            'odometer_reading' => $validated['odometer_reading'],
        ]);

        \App\Models\AuditLog::create([
            'admin_id'    => $user->id,
            'action'      => 'driver_logged_fuel',
            'target_type' => 'fuel_logs',
            'target_id'   => $poolingJob->truck_id,
            'notes'       => "Driver {$user->name} logged {$validated['fuel_liters']}L of fuel (Cost: ₱{$validated['cost']}) for Truck #{$poolingJob->truck_id} at {$validated['odometer_reading']} km.",
        ]);

        return back()->with('success', 'Fuel purchase logged successfully.');
    }

    /**
     * Upload ID photo + selfie for driver identity verification.
     */
    public function uploadIdentity(UploadIdentityRequest $request)
    {
        $user = Auth::user();

        $validated = $request->validated();
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

        if ($poolingJob->status !== PoolingJobStatus::CONFIRMED) {
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
