<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Darfo12Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScrapeDarfo12Prices extends Command
{
    protected $signature = 'crops:scrape:darfo12 {--force}';

    protected $description = 'Scrape daily crop prices from DA RFO12';

    public function handle(Darfo12Service $service): int
    {
        $this->info('DA RFO12 Price Scraper');
        $this->newLine();

        $latestStoredDate = \App\Models\CropPriceHistory::where('source', 'da_rfo12')
            ->max('source_date');

        // Step 1: Try PDF source (primary — full table from DA RFO12 PDF)
        $this->info('Step 1: PDF source (rfo12.da.gov.ph)...');
        $pdfPrices = $service->fetchRegion12PricesFromPdf();

        if (!empty($pdfPrices)) {
            // Determine source date from first price or today
            $pdfDate = Carbon::now()->toDateString();

            if (!$this->option('force') && $latestStoredDate && $pdfDate <= $latestStoredDate) {
                $msg = "PDF data ($pdfDate) is not newer than stored ($latestStoredDate). Skipping.";
                $this->warn($msg);
                $this->logStatus('skipped', $pdfDate, $msg);
                return self::SUCCESS;
            }

            $this->info("Fetched " . count($pdfPrices) . " crop commodities from PDF.");
            $this->newLine();

            $this->info('Storing prices...');
            try {
                [$stored, $skipped] = $service->storeStructuredPrices($pdfPrices, $pdfDate);
                $this->info("Stored: {$stored} crop commodities | Skipped: {$skipped}");
                $this->logStatus('success', $pdfDate, "PDF source: {$stored} commodities", $stored, $skipped);
                $this->info('Done!');
                return self::SUCCESS;
            } catch (\Exception $e) {
                $this->error("DB update failed: {$e->getMessage()}");
                Log::error('DA RFO12: DB update failed (PDF).', ['error' => $e->getMessage()]);
            }
        } else {
            $this->warn('PDF source returned no data. Trying structured HTML...');
        }

        $this->newLine();

        // Step 2: Fallback — structured HTML scraping from bantaypresyo.da.gov.ph
        $this->info('Step 2: Structured data source (bantaypresyo.da.gov.ph)...');
        $structuredDate = $service->fetchStructuredDate();

        if ($structuredDate) {
            $this->info("Structured data date: {$structuredDate}");

            if (!$this->option('force') && $latestStoredDate && $structuredDate <= $latestStoredDate) {
                $msg = "Data ($structuredDate) is not newer than stored ($latestStoredDate). Skipping.";
                $this->warn($msg);
                $this->logStatus('skipped', $structuredDate, $msg);
                return self::SUCCESS;
            }

            $this->info('Fetching structured price data across all categories...');
            $prices = $service->fetchStructuredPrices();

            if (!empty($prices)) {
                $this->info("Fetched " . count($prices) . " commodities from structured source.");
                $this->newLine();

                $this->info('Storing prices...');
                try {
                    [$stored, $skipped] = $service->storeStructuredPrices($prices, $structuredDate);
                    $this->info("Stored: {$stored} crop commodities");
                    $this->logStatus('success', $structuredDate, "Structured source: {$stored} commodities", $stored, $skipped);
                    $this->info('Done!');
                    return self::SUCCESS;
                } catch (\Exception $e) {
                    $this->error("DB update failed: {$e->getMessage()}");
                    Log::error('DA RFO12: DB update failed (structured).', ['error' => $e->getMessage()]);
                }
            } else {
                $this->warn('No prices returned from structured source. Falling back to blog OCR...');
            }
        } else {
            $this->warn('Could not fetch structured data date. Falling back to blog OCR...');
        }

        $this->newLine();

        // Step 3: Final fallback — OCR from blog images
        $this->info('Step 3: Blog OCR fallback...');
        $post = $service->fetchLatestPost();

        if (!$post) {
            $this->error('No price index post found on DA RFO12 blog.');
            $this->logStatus('failed', null, 'All sources failed.');
            return self::FAILURE;
        }

        $this->info("Found: {$post['title']}");
        $this->info("Date: {$post['date']}");
        $this->newLine();

        if (!$this->option('force') && $latestStoredDate && $post['date'] <= $latestStoredDate) {
            $msg = "Blog post date ({$post['date']}) is not newer than stored ({$latestStoredDate}). Skipping.";
            $this->warn($msg);
            $this->logStatus('skipped', $post['date'], $msg);
            return self::SUCCESS;
        }

        $imageUrls = $service->fetchPostImages((int) $post['post_id']);
        if (empty($imageUrls)) {
            $this->error('No images found in the post.');
            $this->logStatus('failed', $post['date'], 'No images found.');
            return self::FAILURE;
        }
        $this->info("Found " . count($imageUrls) . " images.");

        $imagePaths = $service->downloadImages($imageUrls, $post['date']);
        if (empty($imagePaths)) {
            $this->error('Failed to download images.');
            $this->logStatus('failed', $post['date'], 'Failed to download images.');
            return self::FAILURE;
        }

        $this->info('Running OCR...');
        $ocrTexts = $service->ocrImages($imagePaths);
        $this->info('OCR complete.');

        $prices = $service->parseOcrOutput($ocrTexts);
        $this->info("Parsed " . count($prices) . " price entries.");

        $dbSuccess = false;
        if (!empty($prices)) {
            try {
                [$stored, $skipped] = $service->storeCommodityPrices($prices, $post['date']);
                $this->info("Stored: {$stored} crop commodities | Skipped: {$skipped}");
                $dbSuccess = true;
            } catch (\Exception $e) {
                $this->error("DB update failed: {$e->getMessage()}");
                Log::error('DA RFO12: DB update failed (OCR).', ['error' => $e->getMessage()]);
            }
        }

        if ($dbSuccess) {
            $service->cleanup($post['date']);
            $this->logStatus('success', $post['date'], "Blog OCR fallback: {$stored} commodities", $stored, $skipped);
        } else {
            $this->logStatus('failed', $post['date'], 'All sources failed.');
        }

        $this->info('Done!');
        return self::SUCCESS;
    }

    private function logStatus(string $status, ?string $sourceDate, string $message, int $matched = 0, int $skipped = 0): void
    {
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
    }
}
