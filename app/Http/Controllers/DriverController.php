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

        if ($user->role !== 'driver') {
            abort(403);
        }

        $jobs = PoolingJob::where('driver_id', $user->id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->with(['truck', 'harvests.crop', 'harvests.farmer', 'harvests.destination'])
            ->latest()
            ->get();

        $completedJobs = PoolingJob::where('driver_id', $user->id)
            ->where('status', 'completed')
            ->count();

        return view('dashboards.driver-view', [
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

        return view('dashboards.driver-job-show', [
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

        return back()->with('success', 'Job status updated to ' . ucfirst(str_replace('_', ' ', $newStatus)) . '.');
    }
}
