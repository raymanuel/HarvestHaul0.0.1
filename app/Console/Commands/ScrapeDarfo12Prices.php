<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Darfo12Service;
use App\Models\ScraperStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ScrapeDarfo12Prices extends Command
{
    protected $signature = 'crops:scrape:darfo12 {--force} {--diagnose}';

    protected $description = 'Scrape daily crop prices from the DA Bantay Presyo site';

    // Below this many stored commodities for a source date, the scrape is treated as incomplete.
    private const MIN_PLAUSIBLE_COMMODITIES = 10;

    public function handle(Darfo12Service $service): int
    {
        // Give the full HTTP fetch pipeline room to finish under cron and via the Refresh Prices button.
        @set_time_limit(600);

        // Auto-clear compiled classes to prevent stale command cache
        $compiledPath = app()->bootstrapPath('cache/compiled.php');
        if (file_exists($compiledPath)) {
            @unlink($compiledPath);
        }

        $this->info('DA RFO12 Price Scraper');
        $this->newLine();

        // Diagnose mode: run environment and endpoint checks, then exit
        if ($this->option('diagnose')) {
            return $this->diagnose($service);
        }

        // Outer guard: any unexpected error still produces a 'failed' status row + FAILURE exit
        // instead of silently stopping status logging.
        try {
            return $this->runScrape($service);
        } catch (\Throwable $e) {
            Log::error('DA RFO12: Unexpected scrape failure.', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 1000),
            ]);
            $this->error('Unexpected error: ' . $e->getMessage());
            $this->logStatus('failed', null, 'Unexpected error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function runScrape(Darfo12Service $service): int
    {
        try {
            $latestStoredDate = \App\Models\CropPriceHistory::where('source', 'da_rfo12')
                ->max('source_date');
        } catch (\Exception $e) {
            Log::error('DA RFO12: Cannot read crop_price_history (DB down?).', ['error' => $e->getMessage()]);
            $this->error('Database connection failed: ' . $e->getMessage());
            $this->logStatus('failed', null, 'DB read failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Step 1: Fetch latest date from DA Bantay Presyo...');
        $latestDocDate = $service->fetchLatestBantayDate();

        if (!$latestDocDate) {
            $msg = 'Could not fetch the latest date from the DA Bantay Presyo site.';
            $this->error($msg);
            $this->logStatus('failed', null, $msg);
            return self::FAILURE;
        }

        $this->info("Latest date available: {$latestDocDate}");
        $this->info('Step 2: Fetch Region XII prices from DA Bantay Presyo...');

        if (!$this->option('force') && $latestStoredDate && $latestDocDate <= $latestStoredDate) {
            $storedCount = \App\Models\CropPriceHistory::where('source', 'da_rfo12')
                ->where('source_date', $latestStoredDate)
                ->count();

            $lastRunForDate = ScraperStatus::where('scraper_name', 'darfo12')
                ->where('source_date', $latestStoredDate)
                ->latest()
                ->first();

            $shouldRescrape = false;
            if ($lastRunForDate) {
                if ($lastRunForDate->status === 'failed') {
                    $shouldRescrape = true; // last attempt failed → retry to recover
                } elseif ($lastRunForDate->status !== 'success' && $storedCount < self::MIN_PLAUSIBLE_COMMODITIES) {
                    $shouldRescrape = true; // never succeeded and stored data looks incomplete → retry
                }
            } elseif ($storedCount < self::MIN_PLAUSIBLE_COMMODITIES) {
                $shouldRescrape = true; // no run recorded for the date yet and data is thin → retry
            }

            if (!$shouldRescrape) {
                $msg = "Bantay Presyo date ($latestDocDate) is not newer than stored ($latestStoredDate). Nothing to do.";
                $this->warn($msg);
                $this->logStatus('skipped', $latestDocDate, $msg);
                return self::SUCCESS;
            }

            $this->warn("Stored data for {$latestStoredDate} looks incomplete ({$storedCount} items). Re-scraping to catch corrections.");
        }

        $prices = $service->fetchRegion12Prices($latestDocDate);

        if (empty($prices)) {
            $msg = 'No prices parsed from the DA Bantay Presyo site for ' . $latestDocDate . '.';
            $this->error($msg);
            $this->logStatus('failed', $latestDocDate, $msg);
            return self::FAILURE;
        }

        $this->info('Step 3: Storing parsed prices...');
        $this->info('Parsed ' . count($prices) . ' price entries.');

        try {
            [$stored, $skipped] = $service->storeCommodityPrices($prices, $latestDocDate);
            $this->info("Stored: {$stored} | Skipped: {$skipped}");
            $this->logStatus('success', $latestDocDate, "DA Bantay Presyo: {$stored} commodities", $stored, $skipped);
            Cache::forget('darfo12.dashboard');
            $this->info('Done!');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("DB update failed: {$e->getMessage()}");
            Log::error('DA RFO12: DB update failed (Bantay Presyo).', ['error' => $e->getMessage()]);
            $this->logStatus('failed', $latestDocDate, 'DB update failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function diagnose(Darfo12Service $service): int
    {
        $this->info('=== DA RFO12 Scraper Diagnostics ===');
        $this->newLine();

        // Check database
        try {
            $count = \App\Models\CropPriceHistory::where('source', 'da_rfo12')->count();
            $latest = \App\Models\CropPriceHistory::where('source', 'da_rfo12')->max('source_date');
            $this->info("Database: OK ({$count} records, latest: {$latest})");
        } catch (\Exception $e) {
            $this->error("Database: FAILED - {$e->getMessage()}");
        }

        // Check scraper_status
        try {
            $lastRun = ScraperStatus::where('scraper_name', 'darfo12')->latest()->first();
            if ($lastRun) {
                $this->info("Last scraper run: {$lastRun->status} at {$lastRun->created_at}");
                $this->info("  Message: {$lastRun->message}");
            } else {
                $this->warn("Scraper has never been run.");
            }
        } catch (\Exception $e) {
            $this->error("scraper_status table: {$e->getMessage()}");
        }

        // Check the DA Bantay Presyo data endpoint
        $this->newLine();
        $url = 'http://www.bantaypresyo.da.gov.ph/tbl_price_get_comm_price_veg.php';
        try {
            $response = Http::withHeaders(['User-Agent' => Darfo12Service::HTTP_USER_AGENT])
                ->timeout(10)
                ->connectTimeout(10)
                ->asForm()
                ->post($url, ['commodity' => 6, 'region' => '120000000']);
            $reachable = $response->successful();
            $statusLabel = "HTTP {$response->status()}";
        } catch (\Exception $e) {
            $reachable = false;
            $statusLabel = $e->getMessage();
        }

        $this->info("DA Bantay Presyo endpoint ({$url}): " . ($reachable ? "REACHABLE ({$statusLabel})" : "UNREACHABLE ({$statusLabel})"));

        if ($reachable) {
            $latestDate = $service->fetchLatestBantayDate();
            $this->info('  Latest DA Bantay Presyo date: ' . ($latestDate ?: 'unavailable'));
        }

        $this->newLine();
        $this->info('=== Diagnostics Complete ===');

        return self::SUCCESS;
    }

    private function logStatus(string $status, ?string $sourceDate, string $message, int $matched = 0, int $skipped = 0): void
    {
        try {
            ScraperStatus::create([
                'scraper_name'    => 'darfo12',
                'status'          => $status,
                'source_date'     => $sourceDate,
                'message'         => $message,
                'records_matched' => $matched,
                'records_skipped' => $skipped,
            ]);
        } catch (\Exception $e) {
            Log::warning('DA RFO12: Could not log scraper status to DB.', [
                'intended_status' => $status,
                'intended_message' => $message,
                'db_error' => $e->getMessage(),
            ]);
        }
    }
}