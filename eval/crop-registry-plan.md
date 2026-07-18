# HarvestHaul Implementation Plan

## Features Overview

| # | Feature | Priority | Est. Lines |
|---|---------|----------|-----------|
| 1 | Crop Registry: Free-Text Harvest Entry | High | ~130 |
| 2 | DA RFO12 Price Scraper (OCR) | High | ~320 |
| 3 | Farmer Suggested Price | High | ~60 |
| 4 | Dashboard Price Cards (all roles) | High | ~180 |

**Total estimated new code:** ~690 lines

---

# Feature 1: Crop Registry — Free-Text Harvest Entry

## Problem

Farmers can only select from admin-managed crop dropdowns. They want freedom to type in their own harvested crops. Concerns: data normalization (Apple vs apple), baseline pricing for unknown crops, and uniformity.

## Solution: Hybrid Approach with CropResolverService

Farmers pick from existing dropdowns OR select "Other" and type manually. A resolver service normalizes inputs and auto-creates new crops/varieties in the registry.

---

### Phase 1: CropResolverService

**New file:** `app/Services/CropResolverService.php` (~60 lines)

```
Input: (crop_name, variety_name, category_id)
Logic:
  1. Normalize inputs (trim, lowercase)
  2. Find or create Crop via case-insensitive match on (crop_category_id, name)
  3. Find or create CropVariety via case-insensitive match on (crop_id, name)
  4. Return (Crop, CropVariety)
Race condition handling: DB transaction + unique constraint safety net
```

Required from farmer: crop name, variety name, category (dropdown)
NOT required: description, baseline price, scientific name (admin fills later)

---

### Phase 2: Form UI Changes

**Files:**
- `resources/views/harvests/create.blade.php`
- `resources/views/harvests/edit.blade.php`

**Changes:**
- Add "Other (type manually)" as last option in category, crop, and variety dropdowns
- When "Other" is selected, reveal a text input inline
- JavaScript handles show/hide of text inputs
- When crop "Other" text is typed, hide/reset variety dropdown (varieties depend on crop)

---

### Phase 3: Controller + FormRequest Changes

**Files:**
- `app/Http/Requests/StoreHarvestRequest.php`
- `app/Http/Requests/UpdateHarvestRequest.php`
- `app/Http/Controllers/HarvestController.php`

**FormRequest changes:**
- Conditional validation: if crop_id = 'other', require crop_name text instead; same for variety
- If crop_variety_id = 'other', require variety_name text

**Controller changes:**
- If "other" mode, resolve via CropResolverService instead of findOrFail
- Store both FK references AND denormalized strings (existing pattern)

---

### Phase 4: Baseline Price Display

- Buyer crop board views: Show "Negotiable" / "TBD" when baseline_price_per_kg is null
- No schema changes needed (baseline_price_per_kg is already nullable)
- Admin can set baseline prices later via existing POST /admin/crops/{crop}/baseline-price route
- New auto-created crops start with null baseline price

---

### Phase 5: Existing Data

- Keep current seeded crops (already in DB)
- They become the initial registry farmers can pick from
- Auto-created crops grow the registry organically

---

### Key Design Decisions

1. **Normalization**: Trim + lowercase before lookup. "Apple" and "apple" hit same DB query.
2. **Auto-create**: New crops created with status='active', immediately available to other farmers.
3. **Baseline pricing**: Starts null. Buyer sees "Negotiable". Admin sets later.
4. **Category**: Required. Farmer picks from existing dropdown + "Other" option.
5. **No schema changes**: Existing nullable FK columns + denormalized strings support this pattern.

---

### Files to Modify

| File | Change |
|---|---|
| `app/Services/CropResolverService.php` | NEW - normalize + find_or_create logic |
| `app/Http/Requests/StoreHarvestRequest.php` | Conditional validation for "other" mode |
| `app/Http/Requests/UpdateHarvestRequest.php` | Conditional validation for "other" mode |
| `app/Http/Controllers/HarvestController.php` | Use CropResolverService when "other" mode |
| `resources/views/harvests/create.blade.php` | "Other" options + text inputs in dropdowns |
| `resources/views/harvests/edit.blade.php` | Same UI changes |
| Crop board views | Handle null baseline_price_per_kg gracefully |

---

### Stress-Testing

- **Race condition**: Two farmers type "Apple" simultaneously → unique DB constraint catches duplicates
- **Empty input**: Farmer submits blank text → validation blocks it
- **Case collision**: "Apple" vs "apple" → normalization ensures single DB row
- **Existing crop**: Farmer types name that already exists → resolver returns existing crop, no duplicate
- **Variety mismatch**: Variety exists under different crop → resolver creates new variety under correct crop

---

# Feature 2: DA RFO12 Price Scraper (OCR)

## Problem

Daily crop price data for SOCCSKSARGEN is published as image-based PDFs on the DA RFO12 website. Currently there's no automated way to ingest this data into HarvestHaul for baseline pricing.

## Solution: Blog Scraping + Tesseract OCR

Scrape the DA RFO12 WordPress blog for daily price index posts, download the table images, run OCR, parse structured data, and update crop baseline prices.

---

### Data Source Analysis

**Blog archive:** `https://rfo12.da.gov.ph/category/bantay-presyo/?paged=N`
- WordPress site with 796+ pages of price posts (5 posts/page)
- Each post title: "Average Daily Price Index of Agricultural Commodities in SOCCSKSARGEN Region (Month DD, YYYY)"
- Post URL: `https://rfo12.da.gov.ph/?p={POST_ID}` (canonical, reliable)
- WordPress REST API blocked (403 Forbidden)

**Single post structure:**
- Featured image: cover page (title, date, markets)
- Gallery: 8 images total (cover + 7 table pages)
- Each image: 526x946px JPEG, contains one price table
- Downloadable PDF also available (but image-based, not text-extractable)

**Image content (8 pages):**
1. Cover page (title, date, markets covered)
2. International Rice + Local Rice prices
3. Fish prices
4. Meat prices (pork, chicken, beef)
5. Leafy vegetable prices
6. Root/tuber vegetable prices
7. Fruit prices
8. Summary/totals

**Table structure per image:**
- Columns: Commodity | Lowest Price | Highest Price | Common (Retail) | DPI (Prevailing Average)
- Markets: Koronadal, Polomolok, Tacurong, Isulan, Kabacan, Kidapawan, GenSan, Alabel

---

### Phase 1: Dependencies

```bash
# PHP packages
composer require thiagoalessio/tesseract_ocr
composer require symfony/dom-crawler symfony/css-selector

# System-level (CI + production)
# Linux:
apt-get install -y tesseract-ocr tesseract-ocr-eng
# Windows dev:
choco install tesseract
```

**Package rationale:**
- `thiagoalessio/tesseract_ocr` (v2.13.0): PHP wrapper for Tesseract CLI. 3k+ stars, MIT, PHP 8.2 compatible.
- `symfony/dom-crawler` + `symfony/css-selector`: Replace fragile regex with CSS selectors for HTML parsing.

---

### Phase 2: Darfo12Service

**New file:** `app/Services/Darfo12Service.php` (~200 lines)

```
┌─────────────────────────────────────────────────────┐
│                  Darfo12Service                      │
├─────────────────────────────────────────────────────┤
│ fetchLatestPost(): PostData                         │
│   - GET category/bantay-presyo/?paged=1             │
│   - DomCrawler: filter article.mg-posts-sec-post    │
│   - Extract: postId, title, date, thumbnail         │
│   - Filter: title contains "Average Daily Price"    │
│   - Return first match                              │
│                                                     │
│ fetchPostImages(int $postId): array<string>         │
│   - GET ?p={postId}                                 │
│   - DomCrawler: filter wp-block-gallery img         │
│   - Extract src attributes (full-size, not srcset)  │
│   - Return array of image URLs                      │
│                                                     │
│ downloadImages(array $urls): array<string>          │
│   - Http::get() each URL                            │
│   - Save to storage/app/da-prices/{date}/           │
│   - Return local file paths                         │
│                                                     │
│ ocrImages(array $paths): array<string>              │
│   - TesseractOCR each image                         │
│   - Return array of OCR text strings                │
│                                                     │
│ parseOcrOutput(array $texts): array<PriceRow>       │
│   - Regex parse commodity name + 4 price columns    │
│   - Normalize commodity names (strip market headers)│
│   - Return structured price data                    │
│                                                     │
│ updateCropPrices(array $prices): void               │
│   - CropResolverService::resolveCrop() for matching │
│   - Update baseline_price_per_kg                    │
│   - Store in crop_price_history table               │
└─────────────────────────────────────────────────────┘
```

**Key methods:**

```php
class Darfo12Service
{
    private const ARCHIVE_URL = 'https://rfo12.da.gov.ph/category/bantay-presyo/';
    private const POST_URL = 'https://rfo12.da.gov.ph/?p=%d';

    public function fetchLatestPost(): ?array
    public function fetchPostImages(int $postId): array
    public function downloadImages(array $urls, string $dateDir): array
    public function ocrImages(array $paths): array
    public function parseOcrOutput(array $texts): array
    public function updateCropPrices(array $prices, string $sourceDate): void
}
```

---

### Phase 3: Artisan Command

**New file:** `app/Console/Commands/ScrapeDarfo12Prices.php` (~80 lines)

```
Signature: crops:scrape:darfo12
Description: Scrape daily crop prices from DA RFO12 blog

Flow:
1. Darfo12Service::fetchLatestPost()
2. Darfo12Service::fetchPostImages(postId)
3. Darfo12Service::downloadImages(urls)
4. Darfo12Service::ocrImages(paths)
5. Darfo12Service::parseOcrOutput(texts)
6. Darfo12Service::updateCropPrices(prices)
7. Log results, cleanup temp files
```

---

### Phase 4: Price History

**New migration:** `create_crop_price_history_table` (~20 lines)

```
Schema:
- id
- crop_id (FK, cascade delete)
- source (enum: 'da_rfo12', 'admin', 'manual')
- source_date (date - the date of the DA report)
- price_per_kg (decimal 10,2)
- created_at, updated_at

Indexes: [crop_id, source_date], [source, source_date]
```

**Purpose:** Track price trends over time, enable analytics charts, audit trail.

---

### Phase 5: Scheduling

**File:** `routes/console.php`

```php
Schedule::command('crops:scrape:darfo12')->dailyAt('10:00');
```

Runs daily at 10:00 AM (after DA typically publishes morning prices).

---

### Files to Create/Modify

| File | Change |
|---|---|
| `app/Services/Darfo12Service.php` | NEW - scraping, OCR, parsing, DB update |
| `app/Console/Commands/ScrapeDarfo12Prices.php` | NEW - Artisan command |
| `database/migrations/xxxx_create_crop_price_history_table.php` | NEW - price history table |
| `routes/console.php` | Add scheduling for new command |
| `composer.json` | Add tesseract_ocr + dom-crawler dependencies |

---

### Stress-Testing

- **Blog structure changes:** DomCrawler selectors will break → need monitoring/alerting on command failure
- **Tesseract accuracy:** Image quality may cause OCR errors → use whitelist chars, PSM mode 6 (uniform block)
- **No post published:** DA skips a day → command logs warning, no update, no error
- **Image URL pattern changes:** WordPress media IDs are sequential → fallback to gallery extraction
- **CI testing:** OCR too slow/unreliable for CI → mock HTTP responses, test parsing logic only
- **Duplicate scrape:** Same post scraped twice → check crop_price_history for existing source_date
- **Rate limiting:** DA blocks rapid requests → add 1-second delay between image downloads
- **Image preprocessing:** May need grayscale + threshold for better OCR → optional enhancement phase

---

### OCR Strategy Details

**Tesseract configuration for price tables:**
```php
$tesseract = new TesseractOCR($imagePath);
$tesseract->lang('eng');
$tesseract->psm(6);  // Assume uniform block of text
$tesseract->whitelist('0123456789.,PHPphp ');
```

**Post-OCR parsing strategy:**
1. Split OCR output by lines
2. Match commodity names using known crop list + fuzzy matching
3. Extract 4 numeric values per line (Low, High, Common, DPI)
4. Normalize peso amounts (strip ₱, commas)
5. Return structured PriceRow objects

**Fallback if OCR fails:**
- Log warning
- Skip that image
- Continue with remaining images
- Admin notified via notification

---

# Feature 3: Farmer Suggested Price

## Problem

Farmers have no way to indicate what they think their crop is worth. Buyers only see admin-set baseline prices (if any). Farmers want a voice in pricing.

## Solution

Add a `suggested_price_per_kg` field to harvests. Farmers optionally set a price when posting. Buyers see it alongside the DA market price and baseline.

---

### New Migration

**File:** `database/migrations/xxxx_add_suggested_price_to_harvests_table.php` (~15 lines)

```
Schema:
- harvests.suggested_price_per_kg (decimal 10,2, nullable)
```

---

### Form Changes

**Files:** `create.blade.php`, `edit.blade.php`
- Add optional "Your Suggested Price (₱/kg)" input field after quantity
- Placeholder: "e.g. 45.00 (leave blank if negotiable)"
- JavaScript: show helper text "Buyers will see this as your asking price"
- Validation: `nullable|numeric|min:0|max:99999.99`

---

### Controller Changes

**Files:** `StoreHarvestRequest`, `UpdateHarvestRequest`, `HarvestController`
- Add `suggested_price_per_kg` to fillable + validation rules
- Store alongside existing harvest fields

---

### Buyer Display

**Files:** `buyer/crop-board.blade.php`, `buyer/crop-detail.blade.php`
- Crop board card: Show "Farmer's Price: ₱X.XX/kg" or "Negotiable" if null
- Crop detail page: Show alongside "Reference Price" (variety price_per_kg)
- Visual distinction: Suggested price in green, baseline in gray, DA price in blue

---

# Feature 4: Dashboard Price Cards

## Problem

Users have no at-a-glance view of current market prices. They must dig into analytics pages or negotiate to discover prices.

## Solution

Add a "Market Prices" widget to every role's dashboard showing the latest DA RFO12 scraped prices with trend indicators.

---

### Price Card Design (shared component)

**New file:** `resources/views/components/market-prices-card.blade.php`

```
┌─────────────────────────────────────────┐
│  DA Market Prices (Jul 17, 2026)        │
│  Source: DA RFO12 SOCCSKSARGEN          │
├─────────────────────────────────────────┤
│  Crop        │ DA Price  │ Trend        │
│  ────────────┼───────────┼────────────  │
│  Rice (Loc)  │ P38.00/kg │ +2.1% up     │
│  Rice (Intl) │ P42.50/kg │ Stable       │
│  Mango       │ P65.00/kg │ -3.2% down   │
│  Tomato      │ P55.00/kg │ +5.0% up     │
│  Potato      │ P48.00/kg │ Stable       │
│  ...                                      │
│  [View Full Price List]                  │
└─────────────────────────────────────────┘
```

**Trend logic:**
- Compare today's price with yesterday's from `crop_price_history`
- Green: price increased >1%
- Red: price decreased >1%
- Gray: price stable (within +/-1%)

---

### Dashboard Integration Per Role

#### Farmer Dashboard (`farmer-view.blade.php`)
- **Location:** Below the existing "B2B Pricing Guidance Hub" (right column)
- **Enhancement:** Show DA prices alongside existing broker/B2B prices
- **Data passed:** `$daPrices` (latest DA prices), `$priceTrends` (today vs yesterday)
- **Existing widget already shows:** broker price, B2B price, trend badges
- **Add:** DA price column with trend arrow

#### Buyer Dashboard (`buyer/dashboard.blade.php`)
- **Location:** New card below stat cards, before negotiations table
- **Content:** Market Prices card showing DA prices for relevant crops
- **Data passed:** `$daPrices`, `$priceTrends`
- **Also enhance:** Crop board cards show `suggested_price_per_kg` from farmer

#### Logistics Dashboard (`logistics-view.blade.php`)
- **Location:** New card in the right column (below sent proposals)
- **Content:** Market Prices card (logistics partners care about commodity values for cargo insurance/valuation)
- **Data passed:** `$daPrices`, `$priceTrends`

#### Admin Dashboard (`admin-view.blade.php`)
- **Location:** Enhance existing analytics section
- **Content:** DA price history chart (CSS bar chart like existing weekly pricing chart)
- **Data passed:** `$daPriceHistory` (last 30 days of DA prices per crop)
- **Also:** Add DA price source status to admin dashboard (last scraped date, # of crops matched)

---

### Controller Data Preparation

**New method or inline in `DashboardController::index()`:**

```php
// Latest DA price date
$latestDaDate = CropPriceHistory::where('source', 'da_rfo12')
    ->max('source_date');

// Latest DA prices
$daPrices = CropPriceHistory::where('source', 'da_rfo12')
    ->where('source_date', $latestDaDate)
    ->with('crop')
    ->get();

// Yesterday's prices for trend comparison
$yesterdayDaPrices = CropPriceHistory::where('source', 'da_rfo12')
    ->where('source_date', Carbon::parse($latestDaDate)->subDay())
    ->get()
    ->keyBy('crop_id');

// Compute trends
$priceTrends = $daPrices->map(function ($price) use ($yesterdayDaPrices) {
    $prev = $yesterdayDaPrices->get($price->crop_id);
    $change = $prev
        ? (($price->price_per_kg - $prev->price_per_kg) / $prev->price_per_kg) * 100
        : 0;
    return [
        'crop' => $price->crop->name,
        'price' => $price->price_per_kg,
        'trend' => $change > 1 ? 'up' : ($change < -1 ? 'down' : 'stable'),
        'change_pct' => round($change, 1),
        'date' => $price->source_date,
    ];
});
```

---

# Implementation Order

| # | Task | Depends On | Est. Lines |
|---|------|-----------|-----------|
| 1 | CropResolverService | Nothing | ~60 |
| 2 | Form UI: "Other" option + suggested price | CropResolverService | ~60 |
| 3 | Controller + FormRequest (both features) | CropResolverService | ~40 |
| 4 | Migration: `suggested_price_per_kg` | Nothing | ~15 |
| 5 | Install dependencies (Tesseract, DomCrawler) | Nothing | Commands |
| 6 | Darfo12Service | Tesseract + DomCrawler | ~200 |
| 7 | ScrapeDarfo12Prices command | Darfo12Service | ~80 |
| 8 | Migration: `crop_price_history` | Nothing | ~20 |
| 9 | Scheduling | Command | ~2 |
| 10 | Dashboard price controller logic | crop_price_history table | ~40 |
| 11 | `market-prices-card` component | Dashboard logic | ~80 |
| 12 | Integrate into all 4 dashboards | Component | ~60 |

**Recommended execution:** Features 1+3 together (crop registry + suggested price), then Feature 2 (scraper), then Feature 4 (dashboards).

---

# Complete Files Summary

| File | Change |
|---|---|
| `app/Services/CropResolverService.php` | NEW |
| `app/Services/Darfo12Service.php` | NEW |
| `app/Console/Commands/ScrapeDarfo12Prices.php` | NEW |
| `database/migrations/xxxx_add_suggested_price_to_harvests.php` | NEW |
| `database/migrations/xxxx_create_crop_price_history_table.php` | NEW |
| `app/Http/Requests/StoreHarvestRequest.php` | Add suggested_price + "other" validation |
| `app/Http/Requests/UpdateHarvestRequest.php` | Add suggested_price + "other" validation |
| `app/Http/Controllers/HarvestController.php` | CropResolverService + suggested_price |
| `app/Http/Controllers/DashboardController.php` | Pass DA price data to all views |
| `resources/views/harvests/create.blade.php` | "Other" option + suggested price input |
| `resources/views/harvests/edit.blade.php` | "Other" option + suggested price input |
| `resources/views/components/market-prices-card.blade.php` | NEW - shared price card component |
| `resources/views/farmers/farmer-view.blade.php` | Add DA prices to pricing hub |
| `resources/views/buyer/dashboard.blade.php` | Add market prices card |
| `resources/views/buyer/crop-board.blade.php` | Show suggested price on cards |
| `resources/views/buyer/crop-detail.blade.php` | Show suggested price + DA price |
| `resources/views/logistics/logistics-view.blade.php` | Add market prices card |
| `resources/views/admin/admin-view.blade.php` | Add DA price status |
| `routes/console.php` | Schedule scraper |
| `composer.json` | Add dependencies |

---

# Stress-Testing: All Features

### Feature 1 (Crop Registry)
- **Race condition**: Two farmers type "Apple" simultaneously → unique DB constraint catches duplicates
- **Empty input**: Farmer submits blank text → validation blocks it
- **Case collision**: "Apple" vs "apple" → normalization ensures single DB row
- **Existing crop**: Farmer types name that already exists → resolver returns existing crop, no duplicate
- **Variety mismatch**: Variety exists under different crop → resolver creates new variety under correct crop

### Feature 2 (DA Scraper)
- **Blog structure changes:** DomCrawler selectors will break → need monitoring/alerting on command failure
- **Tesseract accuracy:** Image quality may cause OCR errors → use whitelist chars, PSM mode 6 (uniform block)
- **No post published:** DA skips a day → command logs warning, no update, no error
- **Image URL pattern changes:** WordPress media IDs are sequential → fallback to gallery extraction
- **CI testing:** OCR too slow/unreliable for CI → mock HTTP responses, test parsing logic only
- **Duplicate scrape:** Same post scraped twice → check crop_price_history for existing source_date
- **Rate limiting:** DA blocks rapid requests → add 1-second delay between image downloads

### Feature 3 (Suggested Price)
- **Farmer suggests absurd price** (P1/kg or P99999/kg) → Validation min/max bounds, but mostly informational
- **Null suggested price** → Buyer sees "Negotiable" / "Open to Offer"
- **Edit after posting** → Farmer can update suggested price if harvest is still ACTIVE

### Feature 4 (Dashboard Cards)
- **No DA data yet** (first run, or scraper hasn't run) → Price card shows "No data available" placeholder
- **DA price for crop not in DB** → OCR parses name, CropResolverService tries to match, unmatched prices logged
- **Dashboard performance** → Price queries are simple (single table, indexed), minimal impact
- **Mobile layout** → Price cards need responsive grid (1-col on mobile, 2-col on tablet, sidebar on desktop)
