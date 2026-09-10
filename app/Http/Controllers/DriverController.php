<?php

namespace App\Http\Controllers;

use App\Actions\UpdateStopStatusAction;
use App\Http\Requests\UpdateStopStatusRequest;
use App\Http\Requests\StoreFuelLogRequest;
use App\Http\Requests\UploadIdentityRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
use App\Services\WeatherService;
use App\Traits\GeometryHelper;
use App\Traits\Notifiable;

class DriverController extends Controller
{
    use GeometryHelper, Notifiable;

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

        // Attach each job's latest recorded weather so the dashboard can show
        // a compact condition/severity line on the job cards.
        $weatherByJob = \App\Models\WeatherLog::query()
            ->whereIn('pooling_job_id', $jobs->pluck('id'))
            ->orderByDesc('checked_at')
            ->get()
            ->groupBy('pooling_job_id');

        foreach ($jobs as $job) {
            $job->setAttribute('weather', $weatherByJob->get($job->id)?->first());
        }

        $completedJobs = PoolingJob::where('driver_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $weekStart = now()->startOfWeek();
        $fuelSummary = \App\Models\FuelLog::where('driver_id', $user->id)
            ->where('created_at', '>=', $weekStart)
            ->selectRaw('COALESCE(SUM(fuel_liters), 0) as liters, COALESCE(SUM(cost), 0) as cost')
            ->first();

        return view('driver.driver-view', [
            'jobs'               => $jobs,
            'completedToday'     => $completedJobs,
            'fuelThisWeekLiters' => (float) ($fuelSummary->liters ?? 0),
            'fuelThisWeekCost'   => (float) ($fuelSummary->cost ?? 0),
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
                $query->orderByPivot('pickup_order')
                      ->wherePivot('status', '!=', 'rejected');
            },
            'harvests.crop',
            'harvests.farmer.farmerProfile',
            'harvests.destination',
            'logisticsProfile',
        ]);

        // Latest weather recorded for this route (weather card).
        $weatherLog = \App\Models\WeatherLog::where('pooling_job_id', $poolingJob->id)
            ->orderByDesc('checked_at')
            ->first();

        // Weather-adjusted ETA for the driver.
        $eta = app(\App\Services\ETAService::class)->getETAForJob($poolingJob);

        return view('driver.driver-job-show', [
            'job'        => $poolingJob,
            'weatherLog' => $weatherLog,
            'eta'        => $eta,
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
            PoolingJobStatus::CONFIRMED->value   => PoolingJobStatus::IN_PROGRESS,
            PoolingJobStatus::IN_PROGRESS->value => PoolingJobStatus::AWAITING_CONFIRMATION,
        ];

        $currentStatus = $poolingJob->status->value;

        if (!isset($allowedTransitions[$currentStatus])) {
            return back()->with('error', 'This job cannot be updated further.');
        }

        $newStatus = $allowedTransitions[$currentStatus];

        if ($newStatus === PoolingJobStatus::IN_PROGRESS) {
            if (!$poolingJob->accepted_at) {
                return back()->with('error', 'Accept the job before starting the trip.');
            }

            // Reset all non-delivered, non-rejected stop pivots to 'assigned'
            // so the driver's stop chain (assigned → arrived → loaded → delivered) can start.
            // Rejected stops are not part of the delivery chain.
            foreach ($poolingJob->harvests as $harvest) {
                if ($harvest->pivot->status !== 'delivered' && $harvest->pivot->status !== 'rejected') {
                    $poolingJob->harvests()->updateExistingPivot($harvest->id, ['status' => 'assigned']);
                }
            }
        }

        if ($newStatus === PoolingJobStatus::AWAITING_CONFIRMATION) {
            // Validate end-of-trip odometer reading
            $request->validate([
                'end_odometer_reading' => 'required|numeric|min:0.01|max:9999999.99',
            ]);

            // Check if all non-rejected stop statuses are delivered
            $allDelivered = true;
            foreach ($poolingJob->harvests as $harvest) {
                if ($harvest->pivot->status === 'rejected') {
                    continue;
                }
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

            // Trip physically done: release the truck for the next route
            if ($poolingJob->truck) {
                $poolingJob->truck->update(['status' => 'available']);
            }

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

        self::logAudit(
            Auth::id(),
            'updated_dispatch_status',
            'pooling_job',
            $poolingJob->id,
            "Driver " . Auth::user()->name . " updated route #{$poolingJob->id} status from {$currentStatus} to {$newStatus->value}."
        );

        // Trigger Notifications
        if ($newStatus === PoolingJobStatus::IN_PROGRESS) {
            $this->fetchAndPersistStartWeather($poolingJob);

            if ($poolingJob->logisticsProfile && $poolingJob->logisticsProfile->user_id) {
                self::notifyJobInTransit(
                    $poolingJob->logisticsProfile->user_id,
                    $user->name,
                    $poolingJob->id,
                    $poolingJob->harvests
                );
            }
        }

        if ($newStatus === PoolingJobStatus::AWAITING_CONFIRMATION) {
            if ($poolingJob->logisticsProfile && $poolingJob->logisticsProfile->user_id) {
                self::notifyJobAwaitingConfirmation(
                    $poolingJob->logisticsProfile->user_id,
                    $user->name,
                    $poolingJob->id,
                    $poolingJob->harvests,
                    $poolingJob->buyer_id
                );
            }
        }

        $statusLabel = ucfirst(str_replace('_', ' ', $newStatus->value));

        $nextSteps = match ($newStatus) {
            PoolingJobStatus::IN_PROGRESS => [
                'title'   => 'Trip started',
                'message' => 'Job status updated to In Transit.',
                'steps'   => [
                    'You are now actively hauling this route.',
                    'Work through each stop in order: Mark Arrived, Confirm Load, then Mark Delivered.',
                    'GPS telemetry streams throughout the trip.',
                ],
            ],
            PoolingJobStatus::AWAITING_CONFIRMATION => [
                'title'   => 'Route complete',
                'message' => 'Job status updated to Awaiting Confirmation.',
                'steps'   => [
                    'Your physical trip is done and the truck has been released.',
                    'The route now awaits the buyer confirming their receipt.',
                    'Once confirmed, the route is completed and drops off your active jobs.',
                ],
            ],
            default => null,
        };

        $response = back()->with('success', 'Job status updated to ' . $statusLabel . '.');
        if ($nextSteps) {
            $response->with('next_steps', $nextSteps);
        }
        return $response;
    }

    /**
     * Fetch and persist weather at trip start so the driver immediately sees
     * conditions and the ETA can be weather-adjusted. Uses the job's start
     * coordinates (GPS telemetry may not exist yet at this instant). Never
     * throws — a weather failure must not block or fail the trip start.
     */
    private function fetchAndPersistStartWeather(PoolingJob $poolingJob): void
    {
        try {
            if (!$poolingJob->start_latitude || !$poolingJob->start_longitude) {
                return;
            }

            $weather = app(WeatherService::class)->getWeather(
                (float) $poolingJob->start_latitude,
                (float) $poolingJob->start_longitude,
                $poolingJob->id
            );

            if (!$weather) {
                return;
            }

            $poolingJob->forceFill([
                'weather_condition'    => $weather['condition'] ?? null,
                'weather_temperature'  => $weather['temperature'] ?? null,
                'weather_wind_speed'   => $weather['wind_speed'] ?? null,
                'weather_icon'         => $weather['icon'] ?? null,
                'weather_checked_at'   => now(),
                'weather_advisory'     => $weather['advisory'] ?? null,
            ])->save();

            $this->notifyDriverSevereWeather($poolingJob, $weather);
        } catch (\Throwable $e) {
            Log::warning('Could not fetch weather at trip start.', [
                'pooling_job_id' => $poolingJob->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    /**
     * Alert the driver when the weather at trip start is severe, following the
     * format: what happened + what to expect / what to do.
     */
    private function notifyDriverSevereWeather(PoolingJob $poolingJob, array $weather): void
    {
        if (empty($weather['is_severe'])) {
            return;
        }

        \App\Models\Notification::create([
            'user_id' => $poolingJob->driver_id,
            'title'   => 'Severe weather on Route #'.$poolingJob->id,
            'message' => 'Severe weather was detected at your start point: '.($weather['advisory'] ?? $weather['condition'] ?? 'check conditions').' Drive with extra caution and stay updated via the weather card on this route.',
            'link'    => route('driver.jobs.show', $poolingJob),
            'type'    => 'weather_alert',
        ]);
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

        $newStatus = strtoupper($request->validated()['status']);

        $response = back()->with('success', 'Stop status updated to ' . $newStatus . '.');

        if ($newStatus === 'DELIVERED') {
            $response->with('next_steps', [
                'title'   => 'Stop delivered',
                'message' => 'Stop status updated to DELIVERED.',
                'steps'   => [
                    'This harvest has been delivered and the crop is now marked completed.',
                    'Continue to the next stop on the route.',
                    'When all stops are delivered, use Finalize Job to complete the route.',
                ],
            ]);
        }

        return $response;
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

        self::logAudit(
            $user->id,
            'driver_logged_fuel',
            'fuel_logs',
            $poolingJob->truck_id,
            "Driver {$user->name} logged {$validated['fuel_liters']}L of fuel (Cost: ₱{$validated['cost']}) for Truck #{$poolingJob->truck_id} at {$validated['odometer_reading']} km."
        );

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

        $idPath = $request->file('id_photo')->store('driver-ids/' . $user->id, 'local');
        $selfiePath = $request->file('selfie')->store('driver-selfies/' . $user->id, 'local');

        $profile->update([
            'id_photo_path' => $idPath,
            'selfie_path' => $selfiePath,
            'identity_verified' => false, // reset to pending review
        ]);

        self::sendNotification(
            $profile->partner?->user_id,
            'Driver Identity Documents Uploaded',
            "Driver {$user->name} uploaded identity documents for verification.",
            route('logistics.drivers.index')
        );

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

        self::logAudit(
            $user->id,
            'driver_accepted_job',
            'pooling_job',
            $poolingJob->id,
            "Driver {$user->name} accepted Route #{$poolingJob->id}."
        );

        $notifications = [];

        // Logistics partner
        $logisticsUserId = $poolingJob->logisticsProfile?->user_id;
        if ($logisticsUserId) {
            $notifications[] = [
                'user_id' => $logisticsUserId,
                'title'   => 'Driver Accepted Job',
                'message' => "Driver {$user->name} has accepted Route #{$poolingJob->id}. Trip starts soon — monitor progress from your Proposal Inbox.",
                'link'    => route('pooling.show', $poolingJob),
                'category' => 'logistics',
            ];
        }

        // Buyer
        if ($poolingJob->buyer_id) {
            $notifications[] = [
                'user_id' => $poolingJob->buyer_id,
                'title'   => 'Driver Assigned to Your Order',
                'message' => "Driver {$user->name} has accepted Route #{$poolingJob->id}. Trip starts soon — check Deliveries for status updates.",
                'link'    => route('buyer.tracking'),
                'category' => 'logistics',
            ];
        }

        // Each farmer on the route
        $notifiedFarmers = [];
        foreach ($poolingJob->harvests as $harvest) {
            $farmerId = $harvest->user_id;
            if (isset($notifiedFarmers[$farmerId])) continue;
            $notifiedFarmers[$farmerId] = true;

            $notifications[] = [
                'user_id' => $farmerId,
                'title'   => 'Driver Assigned to Your Route',
                'message' => "Driver {$user->name} has accepted Route #{$poolingJob->id} for your crop. Pickup starts soon — check My Logistics for status updates.",
                'link'    => route('farmer.logistics'),
                'category' => 'logistics',
            ];
        }

        self::sendBulkNotifications($notifications);

        return back()->with('success', 'Job accepted successfully.');
    }
}
