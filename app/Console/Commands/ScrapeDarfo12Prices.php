<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Darfo12Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScrapeDarfo12Prices extends Command
{
    protected $signature = 'crops:scrape:darfo12 {--force} {--source=auto} {--diagnose}';

    protected $description = 'Scrape daily crop prices from DA RFO12 (blog OCR, PDF, or HTML fallback)';

    public function handle(Darfo12Service $service): int
    {
        // Auto-clear compiled classes to prevent stale command cache
        $compiledPath = app()->bootstrapPath('cache/compiled.php');
        if (file_exists($compiledPath)) {
            @unlink($compiledPath);
        }

        $this->info('DA RFO12 Price Scraper');
        $this->newLine();

        // Diagnose mode: check binary availability and exit
        if ($this->option('diagnose')) {
            return $this->diagnose($service);
        }

        $source = $this->option('source');
        if (!in_array($source, ['auto', 'blog', 'pdf', 'html'])) {
            $this->error("Invalid --source value '{$source}'. Use: auto, blog, pdf, html");
            return self::FAILURE;
        }

        try {
            $latestStoredDate = \App\Models\CropPriceHistory::where('source', 'da_rfo12')
                ->max('source_date');
        } catch (\Exception $e) {
            Log::error('DA RFO12: Cannot read crop_price_history (DB down?).', ['error' => $e->getMessage()]);
            $this->error('Database connection failed: ' . $e->getMessage());
            $this->logStatus('failed', null, 'DB read failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Step 1: Blog (always freshest — posts images with price tables)
        if (in_array($source, ['auto', 'blog'])) {
            $this->info('Step 1: DA RFO12 Blog (freshest source)...');
            $post = $service->fetchLatestPost();

            if ($post) {
                $this->info("Found: {$post['title']}");
                $this->info("Date: {$post['date']}");

                if (!$this->option('force') && $latestStoredDate && $post['date'] <= $latestStoredDate) {
                    $msg = "Blog post ({$post['date']}) is not newer than stored ({$latestStoredDate}).";
                    if ($source === 'auto') {
                        $this->warn($msg . ' Checking HTML...');
                    } else {
                        $this->warn($msg);
                        $this->logStatus('skipped', $post['date'], $msg);
                        return self::SUCCESS;
                    }
                } else {
                    $imageUrls = $service->fetchPostImages((int) $post['post_id']);

                    if (!empty($imageUrls)) {
                        $this->info("Found " . count($imageUrls) . " images.");

                        $imagePaths = $service->downloadImages($imageUrls, $post['date']);
                        if (!empty($imagePaths)) {
                            $this->info('Running OCR...');
                            $ocrTexts = $service->ocrImages($imagePaths);
                            $this->info('OCR complete.');

                            $prices = $service->parseOcrOutput($ocrTexts);
                            $this->info("Parsed " . count($prices) . " price entries.");

                            if (!empty($prices)) {
                                try {
                                    [$stored, $skipped] = $service->storeCommodityPrices($prices, $post['date']);
                                    $this->info("Stored: {$stored} | Skipped: {$skipped}");
                                    $service->cleanup($post['date']);
                                    $this->logStatus('success', $post['date'], "Blog OCR: {$stored} commodities", $stored, $skipped);
                                    $this->info('Done!');
                                    return self::SUCCESS;
                                } catch (\Exception $e) {
                                    $this->error("DB update failed: {$e->getMessage()}");
                                    Log::error('DA RFO12: DB update failed (Blog).', ['error' => $e->getMessage()]);
                                }
                            }
                        } else {
                            $this->warn('Failed to download images.');
                        }
                    } else {
                        $this->warn('No images found in post.');
                    }
                }
            } else {
                $this->warn('No price index post found on blog.');
            }

            if ($source !== 'auto') {
                $this->logStatus('failed', null, 'Blog source failed.');
                return self::FAILURE;
            }
        }

        $this->newLine();

        // Step 2: Structured HTML fallback (bantaypresyo — slower but reliable)
        $this->info('Step 2: Structured HTML (bantaypresyo.da.gov.ph)...');
        $structuredDate = $service->fetchStructuredDate();

        if ($structuredDate) {
            $this->info("Data date: {$structuredDate}");

            if (!$this->option('force') && $latestStoredDate && $structuredDate <= $latestStoredDate) {
                $msg = "HTML data ($structuredDate) is not newer than stored ($latestStoredDate). Skipping.";
                $this->warn($msg);
                $this->logStatus('skipped', $structuredDate, $msg);
                return self::SUCCESS;
            }

            $this->info('Fetching price data...');
            $prices = $service->fetchStructuredPrices();

            if (!empty($prices)) {
                $this->info("Fetched " . count($prices) . " commodities.");
                try {
                    [$stored, $skipped] = $service->storeStructuredPrices($prices, $structuredDate);
                    $this->info("Stored: {$stored} | Skipped: {$skipped}");
                    $this->logStatus('success', $structuredDate, "HTML source: {$stored} commodities", $stored, $skipped);
                    $this->info('Done!');
                    return self::SUCCESS;
                } catch (\Exception $e) {
                    $this->error("DB update failed: {$e->getMessage()}");
                    Log::error('DA RFO12: DB update failed (HTML).', ['error' => $e->getMessage()]);
                }
            } else {
                $this->error('No prices returned from HTML source.');
            }
        } else {
            $this->error('Could not fetch HTML data date.');
        }

        $this->logStatus('failed', null, 'All sources failed.');
        return self::FAILURE;
    }

    private function diagnose(Darfo12Service $service): int
    {
        $this->info('=== DA RFO12 Scraper Diagnostics ===');
        $this->newLine();

        // Check Tesseract
        $tesseract = config('services.tesseract.binary', 'tesseract');
        $tesseractPath = trim((string) shell_exec("where {$tesseract} 2>nul"));
        if ($tesseractPath) {
            $this->info("Tesseract: {$tesseractPath}");
            $version = trim((string) shell_exec("{$tesseractPath} --version 2>&1"));
            $this->info("  Version: {$version}");
        } else {
            $this->warn("Tesseract: NOT FOUND at '{$tesseract}'");
            $this->warn("  Install: winget install tesseract-ocr.tesseract");
            $this->warn("  Impact: Blog image OCR and PDF OCR will fail. Only HTML source works (~48 commodities).");
        }

        // Check Poppler
        $pdftoppm = config('services.poppler.pdftoppm', 'pdftoppm');
        $pdftoppmPath = trim((string) shell_exec("where {$pdftoppm} 2>nul"));
        if ($pdftoppmPath) {
            $this->info("Poppler (pdftoppm): {$pdftoppmPath}");
        } else {
            $this->warn("Poppler (pdftoppm): NOT FOUND at '{$pdftoppm}'");
            $this->warn("  Install: winget install oschwartz101.poppler.windows");
            $this->warn("  Impact: PDF-based scraping will fail. Only blog images + HTML source work.");
        }

        // Check database
        $this->newLine();
        try {
            $count = \App\Models\CropPriceHistory::where('source', 'da_rfo12')->count();
            $latest = \App\Models\CropPriceHistory::where('source', 'da_rfo12')->max('source_date');
            $this->info("Database: OK ({$count} records, latest: {$latest})");
        } catch (\Exception $e) {
            $this->error("Database: FAILED - {$e->getMessage()}");
        }

        // Check scraper_status
        try {
            $lastRun = DB::table('scraper_status')->where('scraper_name', 'darfo12')->latest()->first();
            if ($lastRun) {
                $this->info("Last scraper run: {$lastRun->status} at {$lastRun->created_at}");
                $this->info("  Message: {$lastRun->message}");
            } else {
                $this->warn("Scraper has never been run.");
            }
        } catch (\Exception $e) {
            $this->error("scraper_status table: {$e->getMessage()}");
        }

        // Check network
        $this->newLine();
        $blogUrl = 'https://rfo12.da.gov.ph/category/bantay-presyo/';
        $htmlUrl = 'http://www.bantaypresyo.da.gov.ph';
        $blogOk = @strlen(@file_get_contents($blogUrl, false, stream_context_create(['http' => ['timeout' => 5]]))) > 0;
        $htmlOk = @strlen(@file_get_contents($htmlUrl, false, stream_context_create(['http' => ['timeout' => 5]]))) > 0;

        $this->info("Blog URL ({$blogUrl}): " . ($blogOk ? 'REACHABLE' : 'UNREACHABLE'));
        $this->info("HTML URL ({$htmlUrl}): " . ($htmlOk ? 'REACHABLE' : 'UNREACHABLE'));

        $this->newLine();
        $this->info('=== Diagnostics Complete ===');

        return self::SUCCESS;
    }

    private function logStatus(string $status, ?string $sourceDate, string $message, int $matched = 0, int $skipped = 0): void
    {
        try {
            DB::table('scraper_status')->insert([
                'scraper_name'    => 'darfo12',
                'status'          => $status,
                'source_date'     => $sourceDate,
                'message'         => $message,
                'records_matched' => $matched,
                'records_skipped' => $skipped,
                'created_at'      => now(),
                'updated_at'      => now(),
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
