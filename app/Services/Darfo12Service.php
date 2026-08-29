<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Crop;
use App\Models\CropPriceHistory;
use App\Models\ScraperStatus;
use Carbon\Carbon;

class Darfo12Service
{
    private const CROP_CATEGORIES = [
        'Rice'                  => ['rice', 'glutinous', 'gutnous', 'giutnous', 'basmati', 'milled', 'miles', 'miled',
                                     'jasponica', 'sinandomeng', 'ir48', 'ir64', 'ir76', 'ir78',
                                     'premium', 'well milled', 'regular milled', 'repolished'],
        'Corn'                  => ['corn', 'com ', 'com(', 'comg', 'yellow corn', 'white corn',
                                     'cracked corn', 'corn grits', 'corn grit'],
        'Root Crops'            => ['sweet potato', 'camote', 'cassava', 'kamoteng kahoy', 'taro', 'gabi', 'yam'],
        'Lowland Vegetables'    => ['tomato', 'kamatis', 'eggplant', 'talong', 'string bean', 'sitaw',
                                     'bitter gourd', 'ampalaya', 'squash', 'kalabasa', 'okra',
                                     'winged bean', 'patola', 'upo', 'bottle gourd', 'sponge gourd',
                                     'bell pepper', 'siling', 'chili', 'chilli', 'chili pepper', 'green chili',
                                     'eoopant', 'pole stao', 'sitao', 'bel pepper', 'chit '],
        'Highland Vegetables'   => ['cabbage', 'repolyo', 'carrot', 'carrots', 'potato', 'patatas',
                                     'broccoli', 'cauliflower', 'lettuce', 'celery',
                                     'spring onion', 'green onion', 'onion leeks', 'leeks',
                                     'pechay', 'pechay baguio', 'mustard', 'mustard leaves',
                                     'radish', 'labanos', 'chayote'],
        'Spices'                => ['garlic', 'bawang', 'onion', 'sibuyas', 'ginger', 'luya',
                                     'turmeric', 'luyang dilaw', 'lemon grass', 'lemongrass',
                                     'horseradish', 'malunggay'],
        'Legumes'               => ['mung bean', 'monggo', 'mongo', 'mung', 'mungbean', 'mungbea', 'peanut', 'mani',
                                     'winged bean', 'kadiwa', 'legume', 'hobichuelas', 'habichuelas', 'beans'],
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

    // Known commodity name aliases — maps source-name variants to display names
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

        // Corn — specific sub-types first (source variants: com, cor, corn)
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
        'pole sitao'                 => 'Sitao',
        'pole stao'                  => 'String Beans (Sitaw)',
        'sitaw'                      => 'String Beans (Sitaw)',
        'hobichuelas'                => 'String Beans',
        'habichuelas'                => 'Habichuelas',
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
        'mungbea'                    => 'Mung Bean',
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

    // Non-crop commodities to skip entirely — includes mangled variants
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

    // ─── Public: Bantay Presyo HTTP scraping (DA-AMAS Region XII) ─────

    public const HTTP_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    private const BANTAY_PRICES_URL = 'http://www.bantaypresyo.da.gov.ph/tbl_price_get_comm_price_veg.php';
    private const BANTAY_DATE_URL = 'http://www.bantaypresyo.da.gov.ph/tbl_veg.php';
    private const BANTAY_REGION = '120000000';
    private const BANTAY_CATEGORY_MAP = [1 => 'Rice', 2 => 'Corn', 3 => 'Legumes', 5 => 'Fruits', 6 => 'Highland Vegetables', 7 => 'Lowland Vegetables', 9 => 'Spices'];
    private const CROP_COMMODITY_CODES = [1, 2, 3, 5, 6, 7, 9];
    private const MIN_PLAUSIBLE_COMMODITIES = 10;

    public function fetchLatestBantayDate(): ?string
    {
        $response = $this->httpPostWithRetry(self::BANTAY_DATE_URL, [
            'action'    => 'get_latest_date',
            'commodity' => 6,
            'region'    => self::BANTAY_REGION,
        ]);

        if (!$response) {
            Log::warning('DA RFO12: Bantay Presyo latest-date fetch failed after retries.');
            return null;
        }

        $text = trim($response->body());

        try {
            $date = Carbon::createFromFormat('F j, Y', $text);
            if (!$date) {
                throw new \Exception('Unparseable Bantay Presyo date.');
            }
            Log::info('DA RFO12: Latest Bantay Presyo date.', ['date' => $date->toDateString()]);
            return $date->toDateString();
        } catch (\Exception $e) {
            Log::warning('DA RFO12: Bantay Presyo returned an unparseable date.', ['text' => $text]);
            return null;
        }
    }

    public function fetchRegion12Prices(?string $dateStr = null): array
    {
        $prices = [];
        $httpFailed = false;

        foreach (self::CROP_COMMODITY_CODES as $code) {
            $response = $this->httpPostWithRetry(self::BANTAY_PRICES_URL, [
                'commodity' => $code,
                'region'    => self::BANTAY_REGION,
            ]);

            if (!$response) {
                $httpFailed = true;
                Log::warning('DA RFO12: Bantay Presyo request failed.', ['commodity' => $code]);
                break;
            }

            $category = self::BANTAY_CATEGORY_MAP[$code] ?? 'Other Crops';
            $rows = $this->parseBantayPriceRows($response->body(), $category);
            foreach ($rows as $row) {
                $prices[] = $row;
            }
        }

        if ($httpFailed) {
            Log::warning('DA RFO12: Bantay Presyo fetch aborted due to HTTP failure. Returning empty set.');
            return [];
        }

        if (count($prices) < self::MIN_PLAUSIBLE_COMMODITIES) {
            Log::warning('DA RFO12: Bantay Presyo payload too thin; returning empty set.', ['rows' => count($prices)]);
            return [];
        }

        return $prices;
    }

    private function httpPostWithRetry(string $url, array $formData, int $maxAttempts = 3, array $delays = [10, 30, 60], int $timeout = 30): ?\Illuminate\Http\Client\Response
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::withHeaders(['User-Agent' => self::HTTP_USER_AGENT])
                    ->timeout($timeout)
                    ->connectTimeout(10)
                    ->asForm()
                    ->post($url, $formData);
                if ($response->successful()) {
                    return $response;
                }
                Log::warning('DA RFO12: HTTP POST failed.', [
                    'url' => $url, 'status' => $response->status(), 'attempt' => $attempt,
                ]);
            } catch (\Exception $e) {
                Log::warning('DA RFO12: HTTP POST error.', [
                    'url' => $url, 'error' => $e->getMessage(), 'attempt' => $attempt,
                ]);
            }

            if ($attempt < $maxAttempts) {
                $delay = $delays[$attempt - 1] ?? end($delays);
                sleep($delay);
            }
        }

        return null;
    }

    private function parseBantayPriceRows(string $html, string $category): array
    {
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $rows = [];
        foreach ($crawler->filter('tr') as $trNode) {
            $tr = new \Symfony\Component\DomCrawler\Crawler($trNode);
            $textWraps = $tr->filter('td.text-wrap');

            if ($textWraps->count() === 0) {
                continue;
            }

            $name = trim($textWraps->eq(0)->text());
            if ($name === '') {
                continue;
            }

            $priceLevels = $tr->filter('td')->reduce(function ($node) {
                $class = $node->attr('class') ?? '';
                return $class === '' || !str_contains($class, 'text-wrap');
            });

            $numeric = [];
            foreach ($priceLevels as $tdNode) {
                $td = new \Symfony\Component\DomCrawler\Crawler($tdNode);
                $value = trim($td->text());
                if ($value === '' || strtoupper($value) === 'N/A' || !is_numeric($value)) {
                    continue;
                }
                $numeric[] = (float) $value;
            }

            if (count($numeric) === 0) {
                continue;
            }

            $common = round(array_sum($numeric) / count($numeric), 2);

            $rows[] = [
                'commodity'    => $name,
                'category'     => $category,
                'low_price'    => min($numeric),
                'high_price'   => max($numeric),
                'common_price' => $common,
                'dpi_price'    => $common,
            ];
        }

        return $rows;
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
        return Cache::remember('darfo12.dashboard', 1800, function () {
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
        });
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
            $lastRun = ScraperStatus::where('scraper_name', 'darfo12')
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

    private function normalizeCommodityName(string $name): string
    {
        // Strip leading/trailing quotes, apostrophes, and stray characters
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

    private function classifyCropByName(string $commodity): ?string
    {
        return $this->matchCategory($commodity);
    }

    private function matchCategory(string $name): ?string
    {
        $name = strtolower($name);

        foreach (self::NON_CROP_KEYWORDS as $keyword) {
            if (str_contains($name, $keyword)) {
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
                if (str_contains($name, $keyword)) {
                    return $category;
                }
            }
        }

        return null;
    }

    private function categorizeCommodity(string $normalizedName): ?string
    {
        foreach (self::NON_CROP_KEYWORDS as $keyword) {
            if (str_contains($normalizedName, $keyword)) {
                return null;
            }
        }

        return $this->matchCategory($normalizedName) ?? 'Other Crops';
    }

    public function getLatestCropPrice(string $cropName): ?CropPriceHistory
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $cropName);

        return CropPriceHistory::where('source', 'da_rfo12')
            ->where('commodity_name', 'LIKE', '%' . $escaped . '%')
            ->orderBy('source_date', 'desc')
            ->first();
    }

    private function findOptionalCropId(string $normalizedName): ?int
    {
        $crop = Crop::whereRaw('LOWER(name) = ?', [$normalizedName])->first();
        return $crop?->id;
    }
}