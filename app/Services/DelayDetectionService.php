<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PoolingJob;
use App\Models\TrackingRecord;
use App\Models\User;
use App\Notifications\DelayAlert;
use Illuminate\Support\Facades\Log;

class DelayDetectionService
{
    private const STALL_THRESHOLD_MINUTES = 15;
    private const SPEED_THRESHOLD_KMH = 1;
    private const STOP_EXPECTED_DURATION_MINUTES = 20;
    private const MAX_GAP_WITHOUT_GPS_MINUTES = 10;
    private const ETA_SLIP_THRESHOLD_MINUTES = 30;
    private const CRITICAL_ESCALATION_MINUTES = 60;

    private array $activeAlerts = []; // tracks active alerts to detect resolution

    public function checkAllActiveJobs(): array
    {
        $activeJobs = PoolingJob::whereIn('status', ['in_progress'])
            ->with(['driver', 'logisticsProfile', 'harvests.farmer', 'harvests.crop'])
            ->get();

        $alerts = [];

        foreach ($activeJobs as $job) {
            $alerts = array_merge($alerts, $this->checkJob($job));
        }

        return $alerts;
    }

    public function checkJob(PoolingJob $job): array
    {
        $alerts = [];

        // 1. Stall detection (stationary at speed 0)
        $stallAlert = $this->detectStall($job);
        if ($stallAlert) {
            $alerts[] = $stallAlert;
            $this->sendDelayNotification($job, $stallAlert['message']);
        }

        // 2. Stop delay (driver arrived but hasn't moved for >20 min)
        $stopDelayAlert = $this->detectStopDelay($job);
        if ($stopDelayAlert) {
            $alerts[] = $stopDelayAlert;
            $this->sendDelayNotification($job, $stopDelayAlert['message']);
        }

        // 3. ETA-based delay (moving but ETA slipped by >30 min from original)
        $etaDelayAlert = $this->detectETADelay($job);
        if ($etaDelayAlert) {
            $alerts[] = $etaDelayAlert;
            $this->sendDelayNotification($job, $etaDelayAlert['message']);
        }

        // 4. Dark detection (no GPS signal received at all)
        $darkAlert = $this->detectNoGPSSignal($job);
        if ($darkAlert) {
            $alerts[] = $darkAlert;
            $this->sendDelayNotification($job, $darkAlert['message']);
        }

        // 5. Auto-escalation for critical delays
        $this->autoEscalate($job, $alerts);

        // 6. Resolution detection
        $this->detectResolution($job, $alerts);

        // Track current alerts for resolution detection next cycle
        $this->activeAlerts[$job->id] = [
            'has_stall' => !is_null($stallAlert),
            'has_dark' => !is_null($darkAlert),
            'checked_at' => now(),
        ];

        return $alerts;
    }

    private function detectStall(PoolingJob $job): ?array
    {
        $recentRecords = TrackingRecord::where('pooling_job_id', $job->id)
            ->where('posted_at', '>=', now()->subMinutes(self::STALL_THRESHOLD_MINUTES))
            ->orderBy('posted_at', 'desc')
            ->take(3)
            ->get();

        if ($recentRecords->count() < 2) return null;

        $newest = $recentRecords->first();
        $oldest = $recentRecords->last();

        if (!$newest->speed_kmh || $newest->speed_kmh > self::SPEED_THRESHOLD_KMH) return null;

        $stallDuration = $newest->posted_at->diffInMinutes($oldest->posted_at);

        if ($stallDuration >= self::STALL_THRESHOLD_MINUTES) {
            return [
                'type' => 'stall_detected',
                'pooling_job_id' => $job->id,
                'driver_name' => $job->driver?->name ?? 'Unknown',
                'latitude' => (float) $newest->latitude,
                'longitude' => (float) $newest->longitude,
                'stall_duration_minutes' => $stallDuration,
                'message' => "Driver {$job->driver?->name} has been stationary for {$stallDuration} minutes on Route #{$job->id}.",
                'severity' => $stallDuration > 30 ? 'critical' : 'warning',
            ];
        }

        return null;
    }

    private function detectStopDelay(PoolingJob $job): ?array
    {
        $job->loadMissing('harvests');

        foreach ($job->harvests as $harvest) {
            // Check both 'arrived' and 'loaded' statuses (stall at loading dock)
            $checkStatuses = ['arrived', 'loaded'];
            if (!in_array($harvest->pivot->status, $checkStatuses)) continue;

            $lastTracking = TrackingRecord::where('pooling_job_id', $job->id)
                ->latest('id')
                ->first();

            if ($lastTracking) {
                $timeAtStop = $lastTracking->posted_at->diffInMinutes(now());
                if ($timeAtStop > self::STOP_EXPECTED_DURATION_MINUTES) {
                    $statusLabel = $harvest->pivot->status === 'loaded' ? 'loading dock' : 'pickup';
                    return [
                        'type' => 'stop_delay',
                        'pooling_job_id' => $job->id,
                        'driver_name' => $job->driver?->name ?? 'Unknown',
                        'harvest_id' => $harvest->id,
                        'crop' => $harvest->crop->name ?? 'Unknown',
                        'delay_minutes' => $timeAtStop,
                        'message' => "Driver {$job->driver?->name} delayed at {$statusLabel} for '{$harvest->crop->name}' — {$timeAtStop} minutes.",
                        'severity' => $timeAtStop > 45 ? 'critical' : 'warning',
                    ];
                }
            }
        }

        return null;
    }

    private function detectNoGPSSignal(PoolingJob $job): ?array
    {
        $latestRecord = TrackingRecord::where('pooling_job_id', $job->id)
            ->latest('posted_at')
            ->first();

        if (!$latestRecord) return null;

        $minutesSinceLastPing = $latestRecord->posted_at->diffInMinutes(now());

        if ($minutesSinceLastPing >= self::MAX_GAP_WITHOUT_GPS_MINUTES) {
            return [
                'type' => 'gps_signal_lost',
                'pooling_job_id' => $job->id,
                'driver_name' => $job->driver?->name ?? 'Unknown',
                'last_known_latitude' => (float) $latestRecord->latitude,
                'last_known_longitude' => (float) $latestRecord->longitude,
                'minutes_since_last_ping' => $minutesSinceLastPing,
                'message' => "GPS signal lost for Route #{$job->id} — no data from driver {$job->driver?->name} for {$minutesSinceLastPing} minutes.",
                'severity' => $minutesSinceLastPing > 30 ? 'critical' : 'warning',
            ];
        }

        return null;
    }

    private function detectETADelay(PoolingJob $job): ?array
    {
        if (!$job->confirmed_at) return null;

        $latestRecord = TrackingRecord::where('pooling_job_id', $job->id)
            ->latest('posted_at')
            ->first();

        if (!$latestRecord) return null;

        $elasticHours = $job->confirmed_at->diffInHours(now());
        if ($elasticHours < 1) return null; // Don't check ETA slip in first hour

        // Crude ETA check: if job started >1 hour ago and progress is slow
        // A simple heuristic: if time elapsed is significantly more than expected
        $harvestsCompleted = $job->harvests->filter(fn($h) => $h->pivot->status === 'delivered')->count();
        $totalHarvests = $job->harvests->count();

        if ($totalHarvests > 0 && $harvestsCompleted === 0 && $elasticHours > 2) {
            return [
                'type' => 'eta_delay',
                'pooling_job_id' => $job->id,
                'driver_name' => $job->driver?->name ?? 'Unknown',
                'elastic_hours' => $elasticHours,
                'message' => "Route #{$job->id} has been active for {$elasticHours} hours but no stops completed yet (0/{$totalHarvests}). Possible route delay.",
                'severity' => 'warning',
            ];
        }

        return null;
    }

    private function autoEscalate(PoolingJob $job, array $alerts): void
    {
        foreach ($alerts as $alert) {
            if ($alert['severity'] === 'critical') {
                $escalationKey = 'delay_escalated_' . $job->id . '_' . $alert['type'];

                // Check if already escalated recently (prevent duplicate escalation in same run)
                $alreadyEscalated = Notification::where('user_id', $job->logisticsProfile?->user_id)
                    ->where('title', 'LIKE', '%Escalated%')
                    ->where('message', 'LIKE', "%Route #{$job->id}%")
                    ->where('created_at', '>=', now()->subMinutes(30))
                    ->exists();

                if (!$alreadyEscalated && $job->logisticsProfile?->user_id) {
                    $user = User::find($job->logisticsProfile->user_id);
                    if ($user) {
                        $user->notify(new DelayAlert(
                            '🚨 Critical Delay Escalated',
                            "Route #{$job->id}: {$alert['message']} This delay has been escalated. Manual intervention required.",
                            route('tracking.index')
                        ));
                    }

                    Log::warning("CRITICAL DELAY ESCALATED for Route #{$job->id}: {$alert['message']}");
                }
            }
        }
    }

    private function detectResolution(PoolingJob $job, array $currentAlerts): void
    {
        $prevAlert = $this->activeAlerts[$job->id] ?? null;
        if (!$prevAlert) return;

        // Check if previously stalled but now moving
        if ($prevAlert['has_stall']) {
            $stillStalled = false;
            foreach ($currentAlerts as $a) {
                if ($a['type'] === 'stall_detected') {
                    $stillStalled = true;
                    break;
                }
            }

            if (!$stillStalled) {
                $this->sendDelayResolvedNotification($job, 'Driver resumed movement. Stall resolved.');
            }
        }

        // Check if dark but now has signal
        if ($prevAlert['has_dark']) {
            $stillDark = false;
            foreach ($currentAlerts as $a) {
                if ($a['type'] === 'gps_signal_lost') {
                    $stillDark = true;
                    break;
                }
            }

            if (!$stillDark) {
                $this->sendDelayResolvedNotification($job, 'GPS signal restored.');
            }
        }
    }

    private function sendDelayResolvedNotification(PoolingJob $job, string $resolutionMessage): void
    {
        $recipients = [];
        if ($job->logisticsProfile?->user_id) {
            $recipients[] = $job->logisticsProfile->user_id;
        }
        foreach ($job->harvests as $h) {
            $recipients[] = $h->user_id;
        }
        $recipients = array_unique($recipients);

        foreach ($recipients as $userId) {
            $user = User::find($userId);
            if ($user) {
                $user->notify(new DelayAlert(
                    '✅ Delay Resolved',
                    "Route #{$job->id}: {$resolutionMessage}",
                    route('tracking.index')
                ));
            }
        }

        Log::info("Delay resolved for Route #{$job->id}: {$resolutionMessage}");
    }

    private function sendDelayNotification(PoolingJob $job, string $message): void
    {
        $recipients = [];

        if ($job->logisticsProfile?->user_id) {
            $recipients[] = $job->logisticsProfile->user_id;
        }

        foreach ($job->harvests as $h) {
            $recipients[] = $h->user_id;
        }

        $recipients = array_unique($recipients);

        foreach ($recipients as $userId) {
            $user = User::find($userId);
            if ($user) {
                $user->notify(new DelayAlert(
                    '⚠️ Route Delay Detected',
                    $message,
                    route('tracking.index')
                ));
            }
        }

        Log::info("Delay notification sent for Route #{$job->id}: {$message}");
    }
}
