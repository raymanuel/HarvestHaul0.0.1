<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\ScraperStatus;
use App\Notifications\PriceDataStale;
use App\Services\Darfo12Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPriceStale extends Command
{
    protected $signature = 'prices:check-stale';

    protected $description = 'Alert admins when DA RFO12 has newer price data we have not stored, or when scrapes keep failing';

    // Marker rows for alert dedupe live in scraper_status under this scraper_name.
    private const ALERT_SOURCE = 'price_stale';

    // Re-alert a persistent issue at most once within this window to avoid spam.
    private const ALERT_RECENCY_HOURS = 24;

    public function handle(Darfo12Service $service): int
    {
        @set_time_limit(120);

        try {
            $storedDate = \App\Models\CropPriceHistory::where('source', 'da_rfo12')->max('source_date');
            $lastRun = ScraperStatus::where('scraper_name', 'darfo12')->latest()->first();
        } catch (\Exception $e) {
            Log::error('DA RFO12 stale check: DB unavailable.', ['error' => $e->getMessage()]);
            $this->error('Database unavailable: ' . $e->getMessage());
            return self::FAILURE;
        }

        $docDate = $service->fetchLatestBantayDate();

        $sourceAhead = $docDate && $storedDate && $docDate > $storedDate;
        $lastRunFailed = $lastRun && $lastRun->status === 'failed';

        if (!$sourceAhead && !$lastRunFailed) {
            $this->info(
                'Price data is fresh (Bantay Presyo date: ' . ($docDate ?: 'unavailable')
                . ', stored: ' . ($storedDate ?: 'none') . '). No alert needed.'
            );
            return self::SUCCESS;
        }

        if ($sourceAhead) {
            $title = 'DA RFO12 has newer market prices available';
            $message = 'The Bantay Presyo source currently lists ' . $docDate
                . ' but HarvestHaul\'s newest stored data is ' . ($storedDate ?: 'empty')
                . '. The hourly scraper has not captured it yet.';
            $dedupeKey = 'source_ahead:' . $docDate;
        } else {
            $title = 'DA RFO12 price scraper is failing';
            $message = 'The last scraper run failed: ' . ($lastRun->message ?? 'unknown error')
                . ' (attempted at ' . $lastRun->created_at . ').';
            $dedupeKey = 'scraper_failed:' . ($lastRun->source_date ?: 'none');
        }

        if ($this->alertAlreadySent($dedupeKey)) {
            $this->info('Matching alert already sent recently; skipping to avoid spam.');
            return self::SUCCESS;
        }

        $admins = User::where('role', 'admin')->get();

        if ($admins->isEmpty()) {
            $this->warn('No admin users found; skipping notification.');
        } else {
            foreach ($admins as $admin) {
                try {
                    $admin->notify(new PriceDataStale($title, $message, route('prices.full')));
                } catch (\Exception $e) {
                    Log::warning('DA RFO12 stale check: notification delivery failed.', [
                        'user' => $admin->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            $this->info('Stale-price alert sent to ' . $admins->count() . ' admin(s).');
        }

        $this->recordAlert($dedupeKey, $docDate ?: $storedDate);

        return self::SUCCESS;
    }

    private function alertAlreadySent(string $dedupeKey): bool
    {
        return ScraperStatus::where('scraper_name', self::ALERT_SOURCE)
            ->where('message', $dedupeKey)
            ->where('created_at', '>=', now()->subHours(self::ALERT_RECENCY_HOURS))
            ->exists();
    }

    private function recordAlert(string $dedupeKey, ?string $sourceDate): void
    {
        try {
            ScraperStatus::create([
                'scraper_name'    => self::ALERT_SOURCE,
                'status'          => 'alerted',
                'source_date'     => $sourceDate,
                'message'         => $dedupeKey,
                'records_matched' => 0,
                'records_skipped' => 0,
            ]);
        } catch (\Exception $e) {
            Log::warning('DA RFO12 stale check: could not record alert marker.', ['error' => $e->getMessage()]);
        }
    }
}
