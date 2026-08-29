<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Safety net for the scheduler. If the OS cron (or schedule:work) has not
 * run for over 5 minutes, the heartbeat goes stale and this middleware
 * runs the due schedule itself in terminate(), after the response is sent.
 * The database lock prevents concurrent requests from double-running.
 */
class EnsureSchedulerAlive
{
    private const HEARTBEAT_KEY = 'scheduler:heartbeat';
    private const LOCK_KEY = 'scheduler:catchup';
    private const STALE_AFTER_SECONDS = 300;
    private const LOCK_TTL_SECONDS = 120;

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        try {
            $heartbeat = Cache::get(self::HEARTBEAT_KEY);

            if ($heartbeat !== null && (now()->timestamp - (int) $heartbeat) < self::STALE_AFTER_SECONDS) {
                return;
            }

            $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL_SECONDS);

            if (! $lock->get()) {
                return;
            }

            try {
                // Re-check after acquiring: another request may have just caught up.
                $heartbeat = Cache::get(self::HEARTBEAT_KEY);
                if ($heartbeat !== null && (now()->timestamp - (int) $heartbeat) < self::STALE_AFTER_SECONDS) {
                    return;
                }

                Artisan::call('schedule:run');
            } finally {
                $lock->release();
            }
        } catch (\Throwable $e) {
            // Cache/DB unreachable (e.g. MySQL stopped): fail quietly, the
            // response has already been sent and the next run will retry.
            report($e);
        }
    }
}
