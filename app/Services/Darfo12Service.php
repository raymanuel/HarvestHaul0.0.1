<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\DomCrawler\Crawler;
use thiagoalessio\TesseractOCR\TesseractOCR;
use App\Models\Crop;
use App\Models\CropPriceHistory;
use Carbon\Carbon;

class Darfo12Service
{
    private const ARCHIVE_URL = 'https://rfo12.da.gov.ph/category/bantay-presyo/';

    private const CROP_CATEGORIES = [
        'Rice'                  => ['rice', 'glutinous', 'gutnous', 'giutnous', 'milled', 'miles', 'miled',
                                     'jasponica', 'sinandomeng', 'ir48', 'ir64', 'ir76', 'ir78',
                                     'premium', 'well milled', 'regular milled', 'repolished'],
        'Corn'                  => ['corn', 'com ', 'com(', 'comg', 'yellow corn', 'white corn',
                                     'cracked corn', 'corn grits', 'corn grit'],
        'Root Crops'            => ['sweet potato', 'camote', 'cassava', 'kamoteng kahoy', 'taro', 'gabi', 'yam'],
        'Lowland Vegetables'    => ['tomato', 'kamatis', 'eggplant', 'talong', 'string bean', 'sitaw',
                                     'bitter gourd', 'ampalaya', 'squash', 'kalabasa', 'okra',
                                     'winged bean', 'patola', 'upo', 'bottle gourd', 'sponge gourd',
                                     'bell pepper', 'siling', 'chili', 'chili pepper', 'green chili',
                                     'eoopant', 'pole stao', 'bel pepper', 'chit '],
        'Highland Vegetables'   => ['cabbage', 'repolyo', 'carrot', 'carrots', 'potato', 'patatas',
                                     'broccoli', 'cauliflower', 'lettuce', 'celery',
                                     'spring onion', 'green onion', 'onion leeks', 'leeks',
                                     'pechay', 'pechay baguio', 'mustard', 'mustard leaves',
                                     'radish', 'labanos', 'chayote'],
        'Spices'                => ['garlic', 'bawang', 'onion', 'sibuyas', 'ginger', 'luya',
                                     'turmeric', 'luyang dilaw', 'lemon grass', 'lemongrass',
                                     'horseradish', 'malunggay'],
        'Legumes'               => ['mung bean', 'monggo', 'mongo', 'mungbean', 'peanut', 'mani',
                                     'winged bean', 'kadiwa', 'legume', 'hobichuelas'],
        'Fruits'                => ['mango', 'mangga', 'banana', 'saging', 'pineapple', 'pinya',
                                     'papaya', 'calamansi', 'kalamansi', 'claman', 'watermelon', 'pakwan',
                                     'melon', 'cantaloupe', 'avocado', 'guava', 'bayabas',
                                     'rambutan', 'lansones', 'lanzones', 'durian', 'marang',
                                     'guyabano', 'atis', 'chico', 'santol', 'star apple', 'caimito',
                                     'apple', 'orange', 'dalandan', 'dragon fruit',
                                     'siniguelas', 'sineguelas', 'duhat', 'pomelo'],
        'Coconut Products'      => ['coconut', 'niyog', 'copra', 'coco'],
        'Other Crops'           => ['coffee', 'kape', 'cacao', 'tsokolate', 'tobacco', 'tabako',
                                     'abaca', 'manila hemp', 'rubber', 'goma', 'sugarcane', 'sugar cane',
                                     'asukal', 'honey'],
    ];

    // Known commodity name aliases — maps OCR-mangled text to display names
    // IMPORTANT: Longer/more specific aliases MUST come before shorter ones
    //            because partial matching checks in order
    private const COMMODITY_ALIASES = [
        // Rice
        'gutnous'                    => 'Glutinous Rice',
        'giutnous'                   => 'Glutinous Rice',
        'gutnus'                     => 'Glutinous Rice',
        'gutinous'                   => 'Glutinous Rice',
        'gutnous rice'               => 'Glutinous Rice',
        'wel miles'                  => 'Well Milled Rice',
        'regu miled'                 => 'Regular Milled Rice',
        'ropu mies'                  => 'Regular Milled Rice',
        'miled'                      => 'Regular Milled Rice',
        'miles'                      => 'Well Milled Rice',
        'jasponica'                  => 'Jasponica Rice',
        'jeponicajasponica'          => 'Jasponica Rice',
        'sinandomeng'                => 'Sinandomeng Rice',
        'sinandomagi'                => 'Sinandomeng Rice',
        'other special rice'         => 'Other Special Rice',
        'other special ice'          => 'Other Special Rice',
        'premium'                    => 'Premium Rice',

        // Corn — specific sub-types first (OCR mangles: com, cor, corn)
        'corn cracked (yellow feed grade)'  => 'Corn Cracked (Yellow, Feed Grade)',
        'corn cracked (yellow, feed grade)' => 'Corn Cracked (Yellow, Feed Grade)',
        'com cracked (yeon, feed grade)'    => 'Corn Cracked (Yellow, Feed Grade)',
        'com cracked (yellow feed grade)'   => 'Corn Cracked (Yellow, Feed Grade)',
        'com cracked (yellow, feed grade)'  => 'Corn Cracked (Yellow, Feed Grade)',
        'com cracked (yelow feedrade)'      => 'Corn Cracked (Yellow, Feed Grade)',
        'corn grits (yellow food grade)'    => 'Corn Grits (Yellow, Food Grade)',
        'corn grits (yellow, food grade)'   => 'Corn Grits (Yellow, Food Grade)',
        'com gris (yelow, food grade)'      => 'Corn Grits (Yellow, Food Grade)',
        'com grits (yellow food grade)'     => 'Corn Grits (Yellow, Food Grade)',
        'com grits (yellow, food grade)'    => 'Corn Grits (Yellow, Food Grade)',
        'corn grits (white food grade)'     => 'Corn Grits (White, Food Grade)',
        'corn grits (white, food grade)'    => 'Corn Grits (White, Food Grade)',
        'com gis (white, food grade)'       => 'Corn Grits (White, Food Grade)',
        'com grits (white food grade)'      => 'Corn Grits (White, Food Grade)',
        'com grits (white, food grade)'     => 'Corn Grits (White, Food Grade)',
        'corn grits (feed grade)'           => 'Corn Grits (Feed Grade)',
        'com gits (feed grade)'             => 'Corn Grits (Feed Grade)',
        'com grits (feed grade)'            => 'Corn Grits (Feed Grade)',
        'com gets feed grade'               => 'Corn Grits (Feed Grade)',
        'cor grits (feed grade)'            => 'Corn Grits (Feed Grade)',
        'corn, white'                       => 'Corn, White',
        'cor (white)'                       => 'Corn, White',
        'com white'                         => 'Corn, White',
        'corn, yellow'                      => 'Corn, Yellow',
        'com (yeon)'                        => 'Corn, Yellow',
        'com (yellow)'                      => 'Corn, Yellow',
        'com ('                             => 'Corn',
        'comg'                              => 'Corn',
        'corn'                              => 'Corn',

        // Eggplant
        'eoopant'                    => 'Eggplant',
        'talong'                     => 'Eggplant',
        'soe ate'                     => 'Soy Sauce',

        // String Beans
        'pole stao'                  => 'String Beans (Sitaw)',
        'sitaw'                      => 'String Beans (Sitaw)',
        'hobichuelas'                => 'String Beans',
        'bel pepper (green, local'   => 'Bell Pepper (Green)',
        'bel pepper (green)'         => 'Bell Pepper (Green)',
        'bel pepper (red), local'    => 'Bell Pepper (Red)',
        'bel pepper (red)'           => 'Bell Pepper (Red)',
        'bel pepper'                 => 'Bell Pepper',
        'ba pepper (green, local'    => 'Bell Pepper (Green)',
        'chl red), local'            => 'Chili (Red)',
        'chit '                      => 'Chili',
        'hobichuelasboquio beans'    => 'Hobichuelas (Boquio Beans)',
        'native pechay'              => 'Pechay',
        'parparo, local'             => 'Patola (Local)',
        'patola'                     => 'Patola',

        // Root Crops
        'camote'                     => 'Sweet Potato',
        'wit potato'                 => 'Sweet Potato',
        'kamoteng kahoy'             => 'Cassava',
        'gabi'                       => 'Taro',

        // Highland Vegetables
        'repolyo'                    => 'Cabbage',
        'patatas'                    => 'Potato',
        'pechay baguio'              => 'Pechay Baguio',
        'pechay'                     => 'Pechay',
        'labanos'                    => 'Radish',
        'oebers'                     => 'Lettuce (OEbers)',
        'broczh local'               => 'Broccoli (Local)',
        'broccoli local'             => 'Broccoli (Local)',
        'broccoli'                   => 'Broccoli',
        'cauitiower, local'          => 'Cauliflower (Local)',
        'cauliflower local'          => 'Cauliflower (Local)',
        'cauliflower'                => 'Cauliflower',
        'cars, local'                => 'Carrots (Local)',
        'carrots local'              => 'Carrots (Local)',
        'carrots'                    => 'Carrots',
        'lettuce (caber)'            => 'Lettuce',

        // Spices
        'sibuyas'                    => 'Red Onion',
        'bawang'                     => 'Garlic',
        'luya'                       => 'Ginger',
        'luyang dilaw'               => 'Turmeric',
        'gari, ported'               => 'Garlic (Imported)',
        'garlic, ported'             => 'Garlic (Imported)',
        'red orion, imported'        => 'Red Onion (Imported)',
        'red onion, imported'        => 'Red Onion (Imported)',
        'white orion imported'       => 'White Onion (Imported)',
        'white onion, imported'      => 'White Onion (Imported)',
        'galunogor, local'           => 'Green Onion (Local)',

        // Legumes
        'monggo'                     => 'Mung Bean',
        'mongo'                      => 'Mung Bean',
        'mungbean'                   => 'Mung Bean',
        'mani'                       => 'Peanut',

        // Fruits
        'mangga'                     => 'Mango',
        'saging'                     => 'Banana',
        'pinya'                      => 'Pineapple',
        'kalamansi'                  => 'Calamansi',
        'claman'                     => 'Calamansi',
        'pakwan'                     => 'Watermelon',
        'watermelon'                 => 'Watermelon',
        'watermeton'                 => 'Watermelon',
        'bayabas'                    => 'Guava',
        'lanzones'                   => 'Lanzones',
        'lansones'                   => 'Lanzones',
        'atis'                       => 'Atis',
        'santol'                     => 'Santol',
        'caimito'                    => 'Star Apple',
        'siniguelas'                 => 'Siniguelas',
        'sineguelas'                 => 'Siniguelas',
        'duhat'                      => 'Duhat',
        'ampaloya'                   => 'Ampalaya (Bitter Gourd)',
        'ampalaya'                   => 'Ampalaya (Bitter Gourd)',

        // Coconut
        'niyog'                      => 'Coconut',

        // Other Crops
        'kape'                       => 'Coffee',
        'tsokolate'                  => 'Cacao',
        'tabako'                     => 'Tobacco',
        'goma'                       => 'Rubber',
        'asukal'                     => 'Sugar',
        'kamatis'                    => 'Tomato',
        'kalabasa'                   => 'Squash',
        'mustard'                    => 'Mustard Leaves',
        'kadiwa'                     => 'Kadiwa',
        'melon'                      => 'Melon',
        'pomelo'                     => 'Pomelo',
    ];

    // Non-crop commodities to skip entirely — includes OCR-mangled variants
    private const NON_CROP_KEYWORDS = [
        'pork', 'baboy', 'perk', 'pek ', 'ork ', 'portier', 'chicken', 'manok', 'chckon', 'core!',
        'beef', 'baka', 'boot ', 'bootr', 'bee ', 'be tong', 'be rib', 'oof ', 'short fib', 'meat', 'karne',
        'poultry', 'duck', 'itik', 'goat', 'kambing',
        'fish', 'isda', 'bangus', 'milkfish', 'tilapia', 'galunggong', 'galunagong', 'salmon', 'simon',
        'tulingan', 'tuna', 'pusit', 'squid', 'shrimp', 'hipon', 'sugpo', 'amano',
        'crab', 'alimasag', 'shellfish', 'labahita', 'tano',
        'round scad', 'herring', 'sardines', 'dried fish', 'tuyo',
        'daing', 'bulad', 'lechon', 'longganisa', 'tocino', 'ham', 'bacon', 'sausage', 'chicharon',
        'bagoong', 'fish sauce', 'patis', 'shrimp paste', 'alamang', 'oyster',
        'cooking oil', 'cooking ol', 'cooking ot', 'sugar',
        'bet fan', 'betal', 'oo plato', 'soy', 'salt rock', 'salt',
        'tabata', 'sau ust', 'simon head', 'cicken', 'eog', 'egg',
    ];

    // ─── Public: Archive & Image Fetching ────────────────────────

    public function fetchLatestPost(): ?array
    {
        $response = Http::timeout(15)->get(self::ARCHIVE_URL);

        if ($response->failed()) {
            Log::warning('DA RFO12: Failed to fetch archive page.', ['status' => $response->status()]);
            return null;
        }

        $crawler = new Crawler($response->body());

        $posts = $crawler->filter('article.mg-posts-sec-post')->each(function (Crawler $node) {
            $title = $node->filter('h4.entry-title a')->text('');
            $href = $node->filter('h4.entry-title a')->attr('href');
            $dateText = $node->filter('span.mg-blog-date a')->text('');

            preg_match('/\?p=(\d+)/', $href, $matches);
            $postId = $matches[1] ?? null;

            $thumbHtml = $node->filter('div.mg-post-thumb')->attr('style', '');
            preg_match("/background-image:\s*url\(['\"]?(.+?)['\"]?\)/", $thumbHtml, $thumbMatches);
            $thumbnail = $thumbMatches[1] ?? null;

            return [
                'post_id' => $postId,
                'title' => trim($title),
                'date_text' => trim($dateText),
                'thumbnail' => $thumbnail,
                'href' => $href,
            ];
        });

        foreach ($posts as $post) {
            if (str_contains($post['title'], 'Average Daily Price Index')) {
                preg_match('/\((.+?)\)\s*$/', $post['title'], $dateMatches);
                $dateStr = $dateMatches[1] ?? $post['date_text'];
                $date = $this->parseDate($dateStr);

                return [
                    'post_id' => $post['post_id'],
                    'title' => $post['title'],
                    'date' => $date,
                    'thumbnail' => $post['thumbnail'],
                ];
            }
        }

        return null;
    }

    public function fetchPostImages(int $postId): array
    {
        $url = "https://rfo12.da.gov.ph/?p={$postId}";
        $response = Http::timeout(15)->get($url);

        if ($response->failed()) {
            Log::warning('DA RFO12: Failed to fetch post page.', ['post_id' => $postId]);
            return [];
        }

        $crawler = new Crawler($response->body());

        $images = $crawler->filter('figure.wp-block-gallery img')->each(function (Crawler $node) {
            return $node->attr('src');
        });

        return array_filter($images);
    }

    public function downloadImages(array $urls, string $dateDir): array
    {
        $paths = [];
        $dir = "da-prices/{$dateDir}";

        foreach ($urls as $index => $url) {
            try {
                $response = Http::timeout(15)->get($url);

                if ($response->failed()) {
                    Log::warning('DA RFO12: Failed to download image.', ['url' => $url]);
                    continue;
                }

                $filename = "page_{$index}.jpg";
                $path = "{$dir}/{$filename}";

                Storage::disk('local')->put($path, $response->body());
                $paths[] = Storage::disk('local')->path($path);
            } catch (\Exception $e) {
                Log::warning('DA RFO12: Image download error.', ['url' => $url, 'error' => $e->getMessage()]);
            }
        }

        return $paths;
    }

    public function cleanup(string $dateDir): void
    {
        $dir = "da-prices/{$dateDir}";
        $files = Storage::disk('local')->files($dir);

        foreach ($files as $file) {
            Storage::disk('local')->delete($file);
        }

        Storage::disk('local')->deleteDirectory($dir);
    }

    // ─── Public: Structured HTML Scraping (Primary) ──────────────

    private const BANTAY_BASE_URL = 'http://www.bantaypresyo.da.gov.ph';
    private const REGION_XII_CODE = '120000000';

    private const BANTAY_CATEGORY_MAP = [
        1  => 'Rice',
        2  => 'Corn',
        3  => 'Legumes',
        5  => 'Fruits',
        6  => 'Highland Vegetables',
        7  => 'Lowland Vegetables',
        9  => 'Spices',
    ];

    public function fetchStructuredDate(): ?string
    {
        try {
            $response = Http::timeout(30)->asForm()->post(
                self::BANTAY_BASE_URL . '/tbl_price_get_date_rice.php',
                ['commodity' => 1, 'region' => self::REGION_XII_CODE]
            );

            if ($response->failed()) return null;

            $dateStr = trim($response->body());
            $date = Carbon::createFromFormat('F j, Y', $dateStr);
            return $date ? $date->toDateString() : null;
        } catch (\Exception $e) {
            Log::warning('DA Bantay Presyo: Failed to fetch date.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function fetchStructuredPrices(): array
    {
        $allPrices = [];

        foreach (self::BANTAY_CATEGORY_MAP as $categoryId => $categoryName) {
            try {
                $prices = $this->fetchCategoryPrices($categoryId, $categoryName);
                $allPrices = array_merge($allPrices, $prices);
            } catch (\Exception $e) {
                Log::warning("DA Bantay Presyo: Failed to fetch category {$categoryName}.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $allPrices;
    }

    private function fetchCategoryPrices(int $categoryId, string $categoryName): array
    {
        $base = self::BANTAY_BASE_URL;
        $region = self::REGION_XII_CODE;

        // Get header to determine column count
        $headerResp = Http::timeout(30)->asForm()->post("{$base}/tbl_price_get_comm_header.php", [
            'commodity' => $categoryId,
            'region'    => $region,
        ]);

        if ($headerResp->failed()) return [];

        $headerHtml = $headerResp->body();
        $count = $this->countTableColumns($headerHtml);

        // Get price data
        $priceResp = Http::timeout(30)->asForm()->post("{$base}/tbl_price_get_comm_price.php", [
            'commodity' => $categoryId,
            'count'     => $count,
            'region'    => $region,
        ]);

        if ($priceResp->failed()) return [];

        return $this->parseBantayTableRows($priceResp->body(), $categoryName);
    }

    private function countTableColumns(string $headerHtml): int
    {
        preg_match_all('/colspan\s*=\s*["\']?(\d+)["\']?/', $headerHtml, $matches);
        $count = 0;
        foreach ($matches[1] as $colspan) {
            $count += (int) $colspan;
        }
        if ($count === 0) {
            $count = substr_count($headerHtml, '<td');
        }
        return max($count, 1);
    }

    private function parseBantayTableRows(string $html, string $categoryName): array
    {
        $prices = [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<table>' . $html . '</table>');
        libxml_clear_errors();

        $trElements = $dom->getElementsByTagName('tr');
        foreach ($trElements as $tr) {
            $tds = $tr->getElementsByTagName('td');
            if ($tds->length < 3) continue;

            $commodity = trim($tds->item(0)->textContent);
            $specification = trim($tds->item(1)->textContent);

            // Collect all market prices, skip N/A
            $marketPrices = [];
            for ($i = 2; $i < $tds->length; $i++) {
                $text = trim($tds->item($i)->textContent);
                if ($text !== 'N/A' && is_numeric($text) && (float) $text > 0) {
                    $marketPrices[] = (float) $text;
                }
            }

            // Need at least one valid market price
            if (empty($marketPrices)) continue;

            $prices[] = [
                'commodity'      => $commodity,
                'commodity_raw'  => $commodity,
                'specification'  => $specification,
                'category'       => $categoryName,
                'low_price'      => min($marketPrices),
                'high_price'     => max($marketPrices),
                'common_price'   => round(array_sum($marketPrices) / count($marketPrices), 2),
                'dpi_price'      => round(array_sum($marketPrices) / count($marketPrices), 2),
                'market_count'   => count($marketPrices),
            ];
        }

        return $prices;
    }

    // ─── Public: Store structured prices ─────────────────────────

    public function storeStructuredPrices(array $prices, string $sourceDate): array
    {
        $stored = 0;
        $skipped = 0;

        DB::transaction(function () use ($prices, $sourceDate, &$stored, &$skipped) {
            foreach ($prices as $price) {
                $category = $price['category'] ?? null;

                if (!$category) {
                    $skipped++;
                    continue;
                }

                $rawName = $price['commodity'];
                $displayName = $this->normalizeCommodityName($rawName);

                CropPriceHistory::updateOrCreate(
                    [
                        'commodity_name' => $displayName,
                        'source'         => 'da_rfo12',
                        'source_date'    => $sourceDate,
                    ],
                    [
                        'commodity_category' => $category,
                        'price_per_kg'       => $price['dpi_price'],
                        'low_price'          => $price['low_price'],
                        'high_price'         => $price['high_price'],
                        'common_price'       => $price['common_price'],
                        'crop_id'            => $this->findOptionalCropId(strtolower($displayName)),
                    ]
                );

                $stored++;
            }
        });

        return [$stored, $skipped];
    }

    // ─── Public: PDF-Based Scraping (Primary for Region 12) ─────

    private const PDF_BASE_URL = 'https://rfo12.da.gov.ph/wp-content/uploads';

    // Map PDF category headers to our categories
    private const PDF_CATEGORY_MAP = [
        'IMPORTED COMMERCIAL RICE'  => 'Rice',
        'LOCAL COMMERCIAL RICE'     => 'Rice',
        'CORN'                      => 'Corn',
        'LEGUMES'                   => 'Legumes',
        'LOWLAND VEGETABLES'        => 'Lowland Vegetables',
        'HIGHLAND VEGETABLES'       => 'Highland Vegetables',
        'SPICES'                    => 'Spices',
        'FRUITS'                    => 'Fruits',
    ];

    // Categories in the PDF that are NOT crops (we skip these entirely)
    private const PDF_SKIP_CATEGORIES = [
        'FISH', 'BEEF', 'PORK', 'POULTRY', 'OTHER LIVESTOCK',
        'WHOLESALE', 'OTHER BASIC COMMODITIES',
    ];

    public function buildPdfUrl(string $dateStr): ?string
    {
        $date = Carbon::parse($dateStr);
        $monthName = $date->format('F');
        $day = $date->format('j');
        $year = $date->format('Y');
        $month = $date->format('m');

        return self::PDF_BASE_URL . "/{$year}/{$month}/Average-Daily-Price-Index-of-Agricultural-Commodities-in-SOCCSKSARGEN-Region-{$monthName}-{$day}-{$year}.pdf";
    }

    public function fetchRegion12PricesFromPdf(?string $dateStr = null): ?array
    {
        if (!$dateStr) {
            $dateStr = Carbon::now()->toDateString();
        }

        $pdfUrl = $this->buildPdfUrl($dateStr);
        if (!$pdfUrl) return null;

        Log::info('DA RFO12 PDF: Attempting download.', ['url' => $pdfUrl, 'date' => $dateStr]);

        $tmpDir = storage_path('app/pdf-scrape-' . bin2hex(random_bytes(8)));
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
        $pdfPath = "{$tmpDir}/dpi.pdf";

        try {
            $response = Http::timeout(30)->get($pdfUrl);
            if ($response->failed()) {
                Log::warning('DA RFO12 PDF: Download failed.', ['url' => $pdfUrl, 'status' => $response->status()]);
                $this->cleanupTempDir($tmpDir);
                return null;
            }
            file_put_contents($pdfPath, $response->body());
            Log::info('DA RFO12 PDF: Downloaded.', ['bytes' => filesize($pdfPath)]);
        } catch (\Exception $e) {
            Log::warning('DA RFO12 PDF: Download error.', ['error' => $e->getMessage()]);
            $this->cleanupTempDir($tmpDir);
            return null;
        }

        try {
            $renderedPages = $this->renderPdfPages($pdfPath, $tmpDir);
            Log::info('DA RFO12 PDF: Rendered pages.', ['count' => count($renderedPages)]);
            if (empty($renderedPages)) {
                return null;
            }

            $allOcrText = [];
            foreach ($renderedPages as $pagePath) {
                $text = $this->ocrSingleImage($pagePath);
                if ($text) {
                    $allOcrText[] = $text;
                    Log::info('DA RFO12 PDF: OCR page ok.', ['path' => basename($pagePath), 'len' => strlen($text)]);
                } else {
                    Log::warning('DA RFO12 PDF: OCR page empty.', ['path' => basename($pagePath)]);
                }
            }

            Log::info('DA RFO12 PDF: OCR results.', ['pages_with_text' => count($allOcrText)]);

            if (empty($allOcrText)) {
                return null;
            }

            $prices = $this->parsePdfOcrOutput($allOcrText, $dateStr);
            Log::info('DA RFO12 PDF: Parsed prices.', ['count' => count($prices)]);
            return $prices;
        } finally {
            $this->cleanupTempDir($tmpDir);
        }
    }

    private function renderPdfPages(string $pdfPath, string $tmpDir): array
    {
        $outputDir = "{$tmpDir}/pages";
        if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

        $pdftoppm = config('services.poppler.pdftoppm', 'pdftoppm');
        $cmd = escapeshellarg($pdftoppm) . ' -png -r 300 ' . escapeshellarg($pdfPath) . ' ' . escapeshellarg("{$outputDir}/page");
        exec($cmd . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            Log::warning('DA RFO12 PDF: pdftoppm failed (binary may be missing).', ['binary' => $pdftoppm, 'output' => implode("\n", $output)]);
            return [];
        }

        $pages = glob("{$outputDir}/page-*.png");
        sort($pages);
        return $pages;
    }

    private function ocrSingleImage(string $imagePath): ?string
    {
        $tesseract = config('services.tesseract.binary', 'tesseract');
        $cmd = escapeshellarg($tesseract) . ' ' . escapeshellarg($imagePath) . ' stdout --psm 6 2>&1';
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || empty($output)) {
            Log::warning('DA RFO12 PDF: Tesseract OCR failed (binary may be missing).', ['binary' => $tesseract, 'exit_code' => $exitCode]);
            return null;
        }

        return implode("\n", $output);
    }

    private function parsePdfOcrOutput(array $texts, string $sourceDate): array
    {
        $prices = [];
        $currentCategory = null;
        $currentSubSection = null;

        foreach ($texts as $text) {
            $text = str_replace("\r\n", "\n", $text);
            $lines = explode("\n", $text);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Skip header/footer lines
                if (preg_match('/^(SOCCSKSARGEN|AGRICULTURE AND FISHERY|AS OF|Low\s*\||HIGH\s*\||COMMON\s*\||DPI\s*\||Prevailing|@ |please|Agri Tayo|AMAD|GD\.)/i', $line)) {
                    continue;
                }

                // Detect category headers — try exact ALL CAPS first, then case-insensitive fallback
                $upper = strtoupper($line);
                $isCategoryHeader = false;

                if (strlen($line) > 3 && !preg_match('/\d/', $line)) {
                    // Check crop categories
                    foreach (self::PDF_CATEGORY_MAP as $pdfCat => $ourCat) {
                        if (str_contains($upper, $pdfCat)) {
                            $currentCategory = $ourCat;
                            // Track Imported vs Local sub-sections within Rice
                            if (str_contains($upper, 'IMPORTED')) {
                                $currentSubSection = 'Imported';
                            } elseif (str_contains($upper, 'LOCAL')) {
                                $currentSubSection = 'Local';
                            } else {
                                $currentSubSection = null;
                            }
                            $isCategoryHeader = true;
                            break;
                        }
                    }
                    // Check skip categories
                    if (!$isCategoryHeader) {
                        foreach (self::PDF_SKIP_CATEGORIES as $skipCat) {
                            if (str_contains($upper, $skipCat)) {
                                $currentCategory = null;
                                $currentSubSection = null;
                                $isCategoryHeader = true;
                                break;
                            }
                        }
                    }
                    if ($isCategoryHeader) continue;
                }

                // Skip lines not in a crop category
                if ($currentCategory === null) continue;

                // Handle OCR artifacts: § → 5 (not space!), strip | and ]
                $cleanLine = str_replace(['|', ']'], ' ', $line);
                $cleanLine = str_replace('§', '5', $cleanLine);
                $cleanLine = str_replace(',', '', $cleanLine);

                if (preg_match('/^(.+?)\s+(\d+\.?\d*)\s+(\d+\.?\d*)\s+(\d+\.?\d*)\s+(\d+\.?\d*)/', $cleanLine, $matches)) {
                    $commodity = trim($matches[1]);
                    $low = (float) $matches[2];
                    $high = (float) $matches[3];
                    $common = (float) $matches[4];
                    $dpi = (float) $matches[5];

                    if ($low <= 0 && $high <= 0) continue;

                    // Prefix Imported/Local to differentiate within same category
                    if ($currentSubSection) {
                        $commodity = "{$commodity} ({$currentSubSection})";
                    }

                    if ($low > 0 && $high >= $low && $common > 0) {
                        $prices[] = [
                            'commodity' => $commodity,
                            'category' => $currentCategory,
                            'low_price' => $low,
                            'high_price' => $high,
                            'common_price' => $common,
                            'dpi_price' => $dpi,
                        ];
                    }
                }
            }
        }

        return $prices;
    }

    private function cleanupTempDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }

    // ─── Public: OCR (Fallback) ─────────────────────────────────

    public function ocrImages(array $paths): array
    {
        $results = [];

        foreach ($paths as $path) {
            try {
                $ocr = new TesseractOCR($path);
                $ocr->executable(config('services.tesseract.binary', 'tesseract'));
                $ocr->lang('eng');
                $ocr->psm(6);

                $text = $ocr->run();
                $results[] = $text;
            } catch (\Exception $e) {
                Log::warning('DA RFO12: OCR failed.', ['path' => $path, 'error' => $e->getMessage()]);
                $results[] = '';
            }
        }

        return $results;
    }

    // ─── Public: Parsing ─────────────────────────────────────────

    public function parseOcrOutput(array $texts): array
    {
        $prices = [];

        foreach ($texts as $text) {
            $lines = explode("\n", $text);

            foreach ($lines as $line) {
                $line = str_replace(['|', ']', '[', ':'], ' ', $line);
                $line = trim($line);
                if (empty($line)) continue;

                if (preg_match('/^(commodity|lowest|highest|common|dpi|price|market|total|source|note|as of|agriculture|fishery|retail)/i', $line)) {
                    continue;
                }

                if (preg_match('/^(.+?)\s+(\d+[\.,]?\d*)\s+(\d+[\.,]?\d*)\s+(\d+[\.,]?\d*)\s+(\d+[\.,]?\d*)/', $line, $matches)) {
                    $commodity = trim($matches[1]);
                    $rawLow = $matches[2];
                    $rawHigh = $matches[3];
                    $rawCommon = $matches[4];
                    $rawDpi = $matches[5];

                    $low = $this->parsePrice($rawLow);
                    $high = $this->parsePrice($rawHigh);
                    $common = $this->parsePrice($rawCommon);
                    $dpi = $this->parsePrice($rawDpi);

                    // Correct OCR decimal drops — pass raw strings to check for actual decimal points
                    // Order: high→common→low→DPI (correct larger values first, use them as references for low)
                    $high = $this->correctPriceFromRaw($high, $rawHigh, 0, 0);
                    $common = $this->correctPriceFromRaw($common, $rawCommon, $high, 0);
                    $low = $this->correctPriceFromRaw($low, $rawLow, max($high, $common), 0);
                    $dpi = $this->correctPriceFromRaw($dpi, $rawDpi, $common, 0);

                    if ($low > 0 && $high >= $low && $low >= 1) {
                        $prices[] = [
                            'commodity' => $commodity,
                            'low_price' => $low,
                            'high_price' => $high,
                            'common_price' => $common,
                            'dpi_price' => $dpi,
                        ];
                    }
                }
            }
        }

        return $prices;
    }

    // ─── Public: Store ALL crop prices ───────────────────────────

    public function storeCommodityPrices(array $prices, string $sourceDate): array
    {
        $grouped = $this->groupPricesByCommodity($prices);
        $stored = 0;
        $skipped = 0;

        DB::transaction(function () use ($grouped, $sourceDate, &$stored, &$skipped) {
            foreach ($grouped as $normalizedName => $data) {
                $category = $this->categorizeCommodity($normalizedName);

                if (!$category) {
                    $skipped++;
                    Log::info('DA RFO12: Skipped non-crop commodity.', ['commodity' => $data['original_name']]);
                    continue;
                }

                $baselinePrice = $data['dpi_price'] > 0 ? $data['dpi_price'] : $data['common_price'];

                $displayName = $this->normalizeCommodityName($data['original_name']);

                CropPriceHistory::updateOrCreate(
                    [
                        'commodity_name' => $displayName,
                        'source'         => 'da_rfo12',
                        'source_date'    => $sourceDate,
                    ],
                    [
                        'commodity_category' => $category,
                        'price_per_kg'       => $baselinePrice,
                        'low_price'          => $data['low_price'],
                        'high_price'         => $data['high_price'],
                        'common_price'       => $data['common_price'],
                        'crop_id'            => $this->findOptionalCropId(strtolower($displayName)),
                    ]
                );

                $stored++;
            }
        });

        return [$stored, $skipped];
    }

    // ─── Public: Dashboard Data ──────────────────────────────────

    public function getDashboardData(): array
    {
        $latestDate = CropPriceHistory::where('source', 'da_rfo12')
            ->max('source_date');

        $priceTrends = collect();
        $daPrices = collect();

        if ($latestDate) {
            $daPrices = CropPriceHistory::where('source', 'da_rfo12')
                ->where('source_date', $latestDate)
                ->get();

            $previousPrices = $this->getPreviousPrices($latestDate);

            $priceTrends = $daPrices->map(function ($price) use ($previousPrices) {
                $prev = $previousPrices->get($price->commodity_name);
                $change = $prev && $prev->price_per_kg > 0
                    ? (($price->price_per_kg - $prev->price_per_kg) / $prev->price_per_kg) * 100
                    : 0;

                return [
                    'commodity'   => $price->commodity_name,
                    'category'   => $price->commodity_category,
                    'price'      => $price->price_per_kg,
                    'low'        => $price->low_price,
                    'high'       => $price->high_price,
                    'common'     => $price->common_price,
                    'trend'      => $change > 1 ? 'up' : ($change < -1 ? 'down' : 'stable'),
                    'change_pct' => round($change, 1),
                    'date'       => $price->source_date,
                ];
            })->sortBy('category');
        }

        return [
            'latestDate'    => $latestDate,
            'daPrices'      => $daPrices,
            'priceTrends'   => $priceTrends,
            'scraperStatus' => $this->getScraperStatus(),
        ];
    }

    public function getPreviousPrices(string $currentDate)
    {
        $prevDate = CropPriceHistory::where('source', 'da_rfo12')
            ->where('source_date', '<', $currentDate)
            ->max('source_date');

        if (!$prevDate) {
            return collect();
        }

        return CropPriceHistory::where('source', 'da_rfo12')
            ->where('source_date', $prevDate)
            ->get()
            ->keyBy('commodity_name');
    }

    public function getScraperStatus(): array
    {
        try {
            $lastRun = DB::table('scraper_status')
                ->where('scraper_name', 'darfo12')
                ->latest()
                ->first();
        } catch (\Exception $e) {
            Log::error('DA RFO12: Could not read scraper_status table.', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Database unavailable: ' . $e->getMessage(), 'last_run_at' => null, 'source_date' => null, 'stale' => true, 'records_matched' => 0];
        }

        if (!$lastRun) {
            return ['status' => 'never_run', 'message' => 'Scraper has never been executed.', 'last_run_at' => null, 'source_date' => null];
        }

        $stale = false;
        if ($lastRun->status === 'success' && $lastRun->source_date) {
            $dataAge = Carbon::now()->diffInHours(Carbon::parse($lastRun->source_date), false);
            $stale = abs($dataAge) > 24;
        }

        return [
            'status'     => $lastRun->status,
            'message'    => $lastRun->message,
            'last_run_at' => $lastRun->created_at,
            'source_date' => $lastRun->source_date,
            'stale'      => $stale,
            'records_matched' => $lastRun->records_matched,
        ];
    }

    // ─── Private Helpers ────────────────────────────────────────

    private function parseDate(string $dateStr): ?string
    {
        $dateStr = trim($dateStr, '()');

        try {
            $date = Carbon::parse($dateStr);
            return $date->toDateString();
        } catch (\Exception $e) {
            $date = Carbon::createFromFormat('F j, Y', $dateStr);
            return $date ? $date->toDateString() : null;
        }
    }

    private function parsePrice(string $value): float
    {
        $cleaned = str_replace(['P', ' '], '', $value);
        $cleaned = trim($cleaned, ',');
        return (float) $cleaned;
    }

    private function correctPriceFromRaw(float $value, string $raw, float $low, float $high): float
    {
        if ($value <= 0) return $value;

        // If the raw OCR text already contains a decimal or comma-as-decimal, it was read correctly
        if (str_contains($raw, '.') || str_contains($raw, ',')) {
            return $value;
        }

        // Raw text has no decimal — OCR likely dropped it
        // Try all possible corrections and pick the one closest to the reference
        $ref = max($low, $high);
        $candidates = [$value]; // original as fallback

        if ($value > 10) $candidates[] = $value / 10;     // dropped decimal point
        if ($value > 100) $candidates[] = $value / 100;   // dropped two decimals
        if ($value < 100) $candidates[] = $value * 10;    // dropped trailing zero
        if ($value < 10) $candidates[] = $value * 100;    // dropped two trailing zeros

        if ($ref > 0) {
            // Pick the candidate within reasonable range of ref, closest to ref
            $valid = array_filter($candidates, fn($v) => $v >= 1 && $v <= $ref * 5);
            if (!empty($valid)) {
                usort($valid, fn($a, $b) => abs($a - $ref) <=> abs($b - $ref));
                return $valid[0];
            }
        }

        // Standalone: prices over ₱800 with no decimal are suspicious for crops
        if ($value > 800) {
            return $value / 100;
        }

        return $value;
    }

    private function normalizeCommodityName(string $name): string
    {
        // Strip leading/trailing quotes, apostrophes, and stray OCR characters
        $name = preg_replace('/^[\x{2018}\x{2019}\x{201C}\x{201D}\'"`\s]+/u', '', $name);
        $name = preg_replace('/[\x{2018}\x{2019}\x{201C}\x{201D}\'"`\s]+$/u', '', $name);
        $name = trim($name);

        // Extract (Imported)/(Local) suffix if present — strip for alias matching, re-append after
        $suffix = '';
        if (preg_match('/\s*\((Imported|Local)\)$/i', $name, $suffixMatch)) {
            $suffix = $suffixMatch[0];
            $name = substr($name, 0, -strlen($suffixMatch[0]));
        }

        $key = strtolower($name);

        // Direct alias match
        if (isset(self::COMMODITY_ALIASES[$key])) {
            return self::COMMODITY_ALIASES[$key] . $suffix;
        }

        // Partial match — sort by key length descending so longer/more specific matches win
        $sorted = collect(self::COMMODITY_ALIASES)->sortKeysDesc();
        foreach ($sorted as $alias => $proper) {
            if (str_contains($key, $alias) && strlen($alias) >= 4) {
                return $proper . $suffix;
            }
        }

        // Title-case the original if no alias found
        return ucwords(trim($name)) . $suffix;
    }

    private function groupPricesByCommodity(array $prices): array
    {
        $grouped = [];

        foreach ($prices as $price) {
            $original = $price['commodity'];
            // Grouping key: only light cleanup, NOT alias replacement
            // This keeps "Corn, Yellow" and "Corn, White" as separate entries
            $key = $this->cleanOcrName($original);

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'original_name' => $original,
                    'low_price' => $price['low_price'],
                    'high_price' => $price['high_price'],
                    'common_sum' => $price['common_price'],
                    'dpi_sum' => $price['dpi_price'],
                    'count' => 1,
                ];
            } else {
                $grouped[$key]['low_price'] = min($grouped[$key]['low_price'], $price['low_price']);
                $grouped[$key]['high_price'] = max($grouped[$key]['high_price'], $price['high_price']);
                $grouped[$key]['common_sum'] += $price['common_price'];
                $grouped[$key]['dpi_sum'] += $price['dpi_price'];
                $grouped[$key]['count']++;
            }
        }

        foreach ($grouped as &$data) {
            $data['common_price'] = $data['common_sum'] / $data['count'];
            $data['dpi_price'] = $data['dpi_sum'] / $data['count'];
            unset($data['common_sum'], $data['dpi_sum'], $data['count']);
        }

        return $grouped;
    }

    private function cleanOcrName(string $name): string
    {
        // Light cleanup only — strip quotes, stray chars, normalize whitespace
        // Does NOT apply alias map, so distinct items stay separate
        $name = preg_replace('/^[\x{2018}\x{2019}\x{201C}\x{201D}\'"`\s]+/u', '', $name);
        $name = preg_replace('/[\x{2018}\x{2019}\x{201C}\x{201D}\'"`\s]+$/u', '', $name);
        $name = trim($name);
        return strtolower($name);
    }

    public function categorizeCommodityPublic(string $name): ?string
    {
        return $this->categorizeCommodity($name);
    }

    public function normalizeCommodityNamePublic(string $name): string
    {
        return $this->normalizeCommodityName($name);
    }

    private function categorizeCommodity(string $normalizedName): ?string
    {
        foreach (self::NON_CROP_KEYWORDS as $keyword) {
            if (str_contains($normalizedName, $keyword)) {
                return null;
            }
        }

        $order = [
            'Rice', 'Corn', 'Root Crops', 'Lowland Vegetables',
            'Highland Vegetables', 'Spices', 'Legumes', 'Fruits',
            'Coconut Products', 'Other Crops',
        ];

        foreach ($order as $category) {
            $keywords = self::CROP_CATEGORIES[$category];
            foreach ($keywords as $keyword) {
                if (str_contains($normalizedName, $keyword)) {
                    return $category;
                }
            }
        }

        return 'Other Crops';
    }

    private function findOptionalCropId(string $normalizedName): ?int
    {
        $crop = Crop::whereRaw('LOWER(name) = ?', [$normalizedName])->first();
        return $crop?->id;
    }
}