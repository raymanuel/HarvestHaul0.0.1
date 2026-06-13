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
            'in_progress' => 'completed',
        ];

        $currentStatus = $poolingJob->status;

        if (!isset($allowedTransitions[$currentStatus])) {
            return back()->with('error', 'This job cannot be updated further.');
        }

        $newStatus = $allowedTransitions[$currentStatus];

        $poolingJob->status = $newStatus;

        if ($newStatus === 'in_progress') {
            $poolingJob->confirmed_at = now();
        }

        if ($newStatus === 'completed') {
            $poolingJob->completed_at = now();
        }

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

        if ($newStatus === 'completed') {
            // Notify logistics partner
            if ($poolingJob->logisticsProfile && $poolingJob->logisticsProfile->user_id) {
                \App\Models\Notification::create([
                    'user_id' => $poolingJob->logisticsProfile->user_id,
                    'title' => 'Job Completed',
                    'message' => "Driver {$user->name} completed Route #{$poolingJob->id}.",
                    'link' => route('pooling.show', $poolingJob)
                ]);
            }
            // Notify farmers
            foreach ($poolingJob->harvests as $harvest) {
                \App\Models\Notification::create([
                    'user_id' => $harvest->user_id,
                    'title' => 'Harvest Shipment Delivered',
                    'message' => "Your harvest '{$harvest->crop->name}' in Route #{$poolingJob->id} has been delivered successfully.",
                    'link' => route('harvests.index')
                ]);
            }
        }

        return back()->with('success', 'Job status updated to ' . ucfirst(str_replace('_', ' ', $newStatus)) . '.');
    }
}
