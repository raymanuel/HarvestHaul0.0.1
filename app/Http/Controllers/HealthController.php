<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\ScraperStatus;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $lastRun = ScraperStatus::where('scraper_name', 'darfo12')->latest()->first();
            $dataDate = DB::table('crop_price_history')->where('source', 'da_rfo12')->max('source_date');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'app' => 'up',
                'database' => false,
                'checked_at' => now()->toIso8601String(),
            ], 503);
        }

        // The scraper writes a status row every hour, so the row's age is the scheduler heartbeat.
        $lastRunAt = $lastRun ? \Carbon\Carbon::parse($lastRun->created_at) : null;
        $minutesSinceLastRun = $lastRunAt ? (int) $lastRunAt->diffInMinutes(now(), false) : null;
        $heartbeatOk = $minutesSinceLastRun !== null && $minutesSinceLastRun >= 0 && $minutesSinceLastRun <= 180;
        $lastRunFailed = $lastRun && $lastRun->status === 'failed';

        $status = ($heartbeatOk && !$lastRunFailed) ? 'ok' : 'degraded';

        return response()->json([
            'status' => $status,
            'app' => 'up',
            'database' => true,
            'scheduler_heartbeat_ok' => $heartbeatOk,
            'latest_data_date' => $dataDate,
            'last_scraper_status' => $lastRun?->status,
            'last_scraper_run_at' => $lastRunAt?->toIso8601String(),
            'checked_at' => now()->toIso8601String(),
        ]);
    }
}
