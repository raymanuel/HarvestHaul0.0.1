# HarvestHaul — System Architecture Defense Document

> **Purpose**: Complete reference for project defense. Explains every architectural decision — the why, the how, the what, and the who.
> **Last updated**: 2026-07-18

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [DA RFO12 Price Scraper — Why, How, What](#2-da-rfo12-price-scraper)
3. [Cron Scheduling — Why 6 AM + Every 5 Hours](#3-cron-scheduling)
4. [OCR Pipeline — Why Tesseract](#4-ocr-pipeline)
5. [Data Storage — Why Two Tables](#5-data-storage)
6. [Dashboard Architecture — Why getDashboardData()](#6-dashboard-architecture)
7. [Market Prices Card — Why Staleness Indicators](#7-market-prices-card)
8. [Crop Registry — Why "Other" Option](#8-crop-registry)
9. [B2B Pricing Guidance — Why Removed](#9-b2b-pricing-guidance)
10. [Non-Crop Filtering — Why Word Boundaries](#10-non-crop-filtering)
11. [Idempotency — Why Post Date vs "Today"](#11-idempotency)
12. [Scraper Failure Tracking — Why scraper_status Table](#12-scraper-failure-tracking)
13. [Key Files Reference](#13-key-files-reference)

---

## 1. System Overview

**What is HarvestHaul?**
HarvestHaul is a web-based marketplace platform that connects Filipino farmers, logistics partners, and buyers in the agricultural supply chain. It is built with Laravel 12, PHP 8.2, MySQL, and Tailwind CSS.

**Who are the stakeholders?**
| Role | Description |
|------|-------------|
| **Farmer** | Lists crops for sale, negotiates with buyers, receives pooling proposals from logistics |
| **Buyer** | Browses crop board, negotiates with farmers, confirms delivery receipt |
| **Logistics Partner** | Plans routes (knapsack + TSP algorithms), assigns drivers, manages pooling jobs |
| **Driver** | Executes deliveries, streams GPS, logs fuel, captures arrival/load/deliver photos |
| **Admin** | Manages users, verifies documents, monitors audit logs, oversees scraper |

**Why does the system need real-time price data?**
Without government price data, farmers and buyers negotiate blind. The DA RFO12 scraper provides verified, government-published market prices so every stakeholder sees the same baseline — reducing exploitation and enabling fair negotiation.

---

## 2. DA RFO12 Price Scraper

### 2.1 Why DA RFO12?

**Question from panel**: "Why did you choose DA RFO12 as your data source instead of other DA regional offices or private price feeds?"

**Answer**:
- DA RFO12 (Region XII — SOCCSKSARGEN) publishes daily average price indices for agricultural commodities on their WordPress site at `rfo12.da.gov.ph/category/bantay-presyo/`
- The data covers the Mindanao region, which is a major agricultural production area in the Philippines
- The data is **government-verified** and published daily — making it the most authoritative free source available
- Private price feeds (like Cropital, FarmOn) either charge fees or don't cover the SOCCSKSARGEN region
- The DA national portal (`da.gov.ph/price-monitoring/`) blocks automated access with CAPTCHA — making scraping unreliable

**Why not the national DA portal?**
We initially tried scraping `da.gov.ph/price-monitoring/da.gov.ph/price-monitoring/` (see the old `ScrapeCropPrices` command). It failed because:
1. The page is JavaScript-rendered — simple HTTP requests return empty HTML
2. It uses CAPTCHA protection on automated requests
3. The price format is inconsistent across regions
4. The RFO12 blog posts are plain HTML with embedded images — much simpler to parse

### 2.2 Why Web Scraping Instead of an API?

**Question from panel**: "Why didn't you use a government API instead of scraping?"

**Answer**:
- The Philippine government does not provide a public API for agricultural price data
- DA's price monitoring page is a WordPress site — it has a REST API, but it returns 403 Forbidden for external requests
- The only way to access the data is to fetch the HTML archive page, find the latest post, and parse its content
- Web scraping is the standard approach when no API is available — it's used by major data platforms (Google News, Zillow, Indeed) for similar government data

**Technical justification**:
- The DA RFO12 site runs on WordPress with a standard HTML structure
- Posts use `<article class="mg-posts-sec-post">` with consistent markup
- Gallery images are inside `<figure class="wp-block-gallery"><img>` — directly accessible via `src` attribute
- No JavaScript rendering required — the HTML is server-rendered
- This makes scraping reliable and low-maintenance

### 2.3 Why Tesseract OCR?

**Question from panel**: "Why did you use Tesseract for OCR instead of a cloud service like Google Vision or AWS Textract?"

**Answer**:
- **Cost**: Google Vision charges ~$1.50 per 1,000 images. At 4 runs/day × 8 images = 32 images/day = ~$17.50/month. Tesseract is free and open-source.
- **Privacy**: DA price data is public government information — no privacy concern with local processing
- **Latency**: Tesseract runs locally in ~2-4 seconds per image. Cloud APIs add network latency (~500ms-2s per call).
- **Reliability**: No dependency on external API availability or rate limits
- **Accuracy**: For printed government price tables (clean fonts, consistent layout), Tesseract achieves ~95% accuracy — sufficient for price data
- **Deployment**: Single `apt install tesseract-ocr` on Linux — no API keys, no billing setup

**Why not pytesseract (Python)?**
We use PHP throughout the stack (`thiagoalessio/tesseract-ocr` PHP wrapper). Adding Python just for OCR would introduce a language boundary, require a subprocess call, and complicate deployment for a marginal benefit.

**Tradeoffs acknowledged**:
- Tesseract is less accurate than cloud services for handwritten or degraded text
- For our use case (printed price tables from government PDFs), this is not an issue
- The `PSM 6` mode (assume uniform text block) optimizes for table-formatted data

### 2.4 Why Not Use the Price PDF Directly?

**Question from panel**: "The DA posts have PDF attachments. Why not parse the PDF directly?"

**Answer**:
- The PDFs are **image-based** — they are JPG screenshots embedded in a PDF container
- Attempting to extract text from them with `pdftotext` or `smalot/pdfparser` returns empty strings
- The images inside the PDF are the same as the gallery images in the HTML post
- Downloading the images directly and running OCR is simpler, faster, and produces the same result as extracting images from the PDF first

---

## 3. Cron Scheduling — Why 6 AM + Every 5 Hours

### 3.1 Why Not Run Once Daily?

**Question from panel**: "Why not just run the scraper once a day at a fixed time?"

**Answer**:
- We do not know when DA RFO12 updates their data. They may post at 8 AM, 11 AM, or 2 PM — there is no documented schedule.
- If we run once at 10 AM and DA posts at 11 AM, we miss an entire day's data
- If DA posts at 6 AM and we run at 10 AM, we're 4 hours behind
- Running multiple times per day ensures we catch updates whenever they happen

### 3.2 Why Every 5 Hours Specifically?

**Question from panel**: "Why every 5 hours? Why not every hour or every 2 hours?"

**Answer**:
- DA RFO12 publishes at most 1 price index post per day — they don't update intra-day
- Running every hour would waste resources (each run: fetches archive, checks idempotency, bails if no new data)
- The overhead per run is: 1 HTTP request (archive page) + 1 DB query (max source_date) = ~2 seconds
- Every 5 hours (4 runs/day: 6 AM, 11 AM, 4 PM, 9 PM) gives us coverage across all likely posting times:
  - **6 AM**: Catch early-morning posts
  - **11 AM**: Catch mid-morning posts (most common for government offices)
  - **4 PM**: Catch afternoon posts
  - **9 PM**: Catch late-day posts (edge case — DA staff working late)
- This balances **freshness** (data is at most 5 hours old when viewed) against **resource efficiency** (only 4 lightweight checks per day)

### 3.3 Why Start at 6 AM?

**Question from panel**: "Why not start at 4 AM or 8 AM?"

**Answer**:
- Government offices in the Philippines typically start at 8 AM
- DA staff may prepare and post price data between 6 AM and 12 PM
- Starting at 6 AM catches the earliest possible posts
- Starting earlier (e.g., 4 AM) would be wasteful — DA staff are not working at 4 AM
- The first run at 6 AM establishes a baseline; subsequent runs at 11 AM, 4 PM, 9 PM catch any updates

### 3.4 Why Not Event-Driven (Webhooks)?

**Question from panel**: "Why not use webhooks to trigger scraping when DA publishes?"

**Answer**:
- DA RFO12 does not offer webhooks or any notification system for new posts
- We cannot modify the government website to add webhook support
- WordPress has XML-RPC and REST API, but both are blocked (403) for external requests
- Polling (cron) is the only viable approach when the data source does not support push notifications

---

## 4. OCR Pipeline — Why Tesseract

### 4.1 Pipeline Architecture

```
WordPress Archive (HTML)
    ↓ Symfony DomCrawler
Latest Post URL + Date
    ↓ HTTP GET
Post Page (HTML)
    ↓ DomCrawler: figure.wp-block-gallery img
Image URLs (JPG)
    ↓ HTTP Download
Local Storage (storage/da-prices/{date}/)
    ↓ Tesseract OCR (PSM 6, English)
Raw Text per Image
    ↓ Regex Parsing (preg_match)
Structured Price Data [{commodity, low, high, common, dpi}]
    ↓ Crop Matching (aliases + fuzzy)
CropPriceHistory rows + baseline_price_per_kg update
```

### 4.2 Why PSM 6?

PSM (Page Segmentation Mode) 6 tells Tesseract to treat the image as a single uniform block of text. This is optimal for government price tables which have:
- Consistent column alignment
- Uniform font size
- No mixed text/graphics
- Grid-like structure

### 4.3 Why English Language Pack?

The DA RFO12 price tables use English commodity names (Rice, Corn, Tomato, etc.) and Philippine peso formatting. The English language pack handles this correctly. Filipino/Tagalog language packs would actually reduce accuracy for these specific tables.

---

## 5. Data Storage — Why Two Tables

### 5.1 The Problem

We need two things from price data:
1. **Current baseline** — What is the latest price for each crop? (needed for negotiation bounds, dashboard display)
2. **Historical trend** — How has the price changed over time? (needed for trend arrows, staleness detection)

### 5.2 Why `crops.baseline_price_per_kg` AND `crop_price_history`?

| Table | Purpose | Updated By |
|-------|---------|------------|
| `crops.baseline_price_per_kg` | **Current snapshot** — the latest DA price for each crop. Used by negotiation controller (price bounds), admin analytics (price management), and CropResolverService (initialization). | `Darfo12Service::updateCropPrices()` — overwrites on each scrape |
| `crop_price_history` | **Historical log** — every price ever scraped, with source date. Used by `getDashboardData()` for trend calculation and `getLatestPrices()` for dashboard display. | `CropPriceHistory::updateOrCreate()` — appends new dates |

**Why not just one table?**
- If we only had `crop_price_history`, every dashboard render would need to query for the latest date, then filter by that date — adding a subquery to every page load
- If we only had `baseline_price_per_kg`, we couldn't show trends (no historical data)
- Having both is a **read optimization** — the baseline is O(1) lookup, the history is for aggregation

### 5.3 Why the Unique Constraint `(crop_id, source, source_date)`?

**Question from panel**: "Why do you have a unique constraint on crop_id + source + date?"

**Answer**:
- The `updateOrCreate()` method needs to know: "Does a record for this crop on this date from this source already exist?"
- Without the unique constraint, `updateOrCreate()` would fail to find the existing row and create duplicates
- The constraint also prevents data corruption from concurrent scraper runs (e.g., if two cron jobs fire simultaneously)
- The index on `[crop_id, source_date]` supports the `getPreviousPrices()` query efficiently

---

## 6. Dashboard Architecture — Why getDashboardData()

### 6.1 The DRY Problem

The same 25-line block of code (query latest date, fetch prices, compute trends) was originally copy-pasted in 3 controllers:
- `DashboardController::index()` — farmer + logistics dashboards
- `BuyerController::dashboard()` — buyer dashboard
- `AdminController::index()` — admin dashboard

**Why was this bad?**
- Any change to the DA price logic required editing 3 files
- Bug fixes could be applied to one controller but missed in others
- New features (like scraper status) would need to be added in 3 places

### 6.2 Why Extract to `Darfo12Service::getDashboardData()`?

- **Single source of truth** — one method, one query pattern, one place to maintain
- **All 4 dashboards** now call the same service method
- The method returns `['latestDate', 'daPrices', 'priceTrends', 'scraperStatus']` — a complete data package
- Controllers just destructure the array and pass it to the view

### 6.3 Why `getPreviousPrices()` Uses Max Date, Not `subDay()`?

**Question from panel**: "Why didn't you just compare with yesterday's date?"

**Answer**:
- DA RFO12 may not publish on weekends or holidays
- If the scraper misses a day (network error, DA site down), `subDay()` would compare with an empty date — showing all crops as "Stable" when they may have changed
- `where('source_date', '<', $currentDate)->max('source_date')` finds the actual most recent previous data point, regardless of calendar gaps
- This ensures trends are always meaningful

---

## 7. Market Prices Card — Why Staleness Indicators

### 7.1 Why Show Data Date?

**Question from panel**: "Why do you show the date on the Market Prices Card?"

**Answer**:
- Price data is time-sensitive — a farmer needs to know if they're looking at today's prices or last week's
- Without a date, the farmer might make pricing decisions based on stale data
- The date badge shows the source date in `M d, Y` format

### 7.2 Why Show Staleness Warning?

**Question from panel**: "Why do you show a warning when data is more than 24 hours old?"

**Answer**:
- If the scraper fails or DA hasn't published, the card shows old data with no indication it's stale
- The staleness indicator (amber badge with "3 days old" text) warns the farmer that prices may have changed
- This prevents the dangerous scenario where a farmer uses week-old prices for today's negotiation

### 7.3 Why Show Scraper Failure Warning?

**Question from panel**: "Why show a scraper failure warning to end users?"

**Answer**:
- Users need to know why they're seeing old data — transparency builds trust
- If the scraper fails, the admin needs to know (shown on admin dashboard)
- The warning says "Last scraper run failed. Showing oldest available data." — this is actionable (admin can manually trigger `php artisan crops:scrape:darfo12`)

---

## 8. Crop Registry — Why "Other" Option

### 8.1 The Problem

Farmers grow thousands of crop varieties. A dropdown limited to pre-seeded crops would exclude:
- Rare varieties (e.g., Purple Yam, Bitter Melon)
- Regional specialties (e.g., Marang, Lanzones)
- New crops the system hasn't been seeded with

### 8.2 Why Hybrid Dropdown + Free Text?

- **Default**: Dropdown with pre-seeded crops — fast selection, consistent naming, enables price tracking
- **Fallback**: "Other (type manually)" option — farmer types the crop name
- **Backend**: `CropResolverService` normalizes the input (trims, lowercases), checks if a crop with that name exists, creates it if not
- **Race condition handling**: If two farmers simultaneously create the same crop, the `try-catch` on `QueryException` retries the find — preventing duplicate crops

### 8.3 Why Not Just a Free-Text Field?

- Without a dropdown, farmers would type "Rice", "rice", "White Rice", "Local Rice" — all creating separate crops
- The dropdown enforces consistent naming while the "Other" option provides flexibility
- Pre-seeded crops also have `baseline_price_per_kg` from the DA scraper — enabling price display

---

## 9. B2B Pricing Guidance — Why Removed

### 9.1 What Was It?

The B2B Pricing Guidance widget showed:
- `baseline_price_per_kg` × 0.67 = "Broker price"
- `baseline_price_per_kg` = "B2B price"
- A "Direct Gain" percentage between them

### 9.2 Why Was It Removed?

**Question from panel**: "Why did you remove the B2B Pricing Guidance widget?"

**Answer**:
1. **Artificial math**: The "broker price" was simply `baseline × 0.67` — an arbitrary multiplier with no real-world basis. There is no actual broker price feed or market data backing this calculation.
2. **Redundancy**: The Market Prices Card already shows real DA RFO12 prices with trends — the same information, but grounded in actual government data
3. **Misleading**: A farmer seeing "Broker: ₱32/kg, B2B: ₱48/kg, +50% Direct Gain" might make decisions based on a fictitious price spread
4. **Hardcoded fallback**: When no crops had `baseline_price_per_kg` set, the widget showed hardcoded prices (Potato ₱48, Red Onion ₱125, etc.) — completely fictional data
5. **The profit calculator remains**: Farmers can still estimate costs and margins using the Interactive Net Profit Calculator — they just enter their actual sale price instead of a synthetic B2B price

---

## 10. Non-Crop Filtering — Why Word Boundaries

### 10.1 The Problem

DA RFO12 price data includes both crops AND non-crop commodities (meats, poultry, fish, seafood). We only want crops for our platform.

### 10.2 Why `preg_match('/\b...\b/')` Instead of `str_contains()`?

**Question from panel**: "Why did you use word-boundary matching instead of simple string contains?"

**Answer**:
- `str_contains("eggplant", "egg")` returns `true` — but eggplant is a crop, not an egg product
- `str_contains("goldfish", "fish")` returns `true` — but goldfish is not a commodity
- `preg_match('/\bfish\b/', "goldfish")` returns `false` — the word boundary prevents partial matches
- `preg_match('/\begg\b/', "eggplant")` returns `false` — "egg" is not a standalone word in "eggplant"
- This prevents false negatives (crops being incorrectly filtered out)

### 10.3 Why the Full List of Non-Crop Commodities?

The `NON_CROP_COMMODITIES` constant includes every non-crop item found in DA RFO12 data through manual inspection:
- Meats: pork, chicken, beef, meat, lechon
- Poultry: poultry
- Fish: fish, bangus, milkfish, tilapia, galunggong, tulingan, tuna, herring, sardines, round scad, dried fish, labahita
- Seafood: pusit, squid, shrimp, crab, shellfish

If DA RFO12 adds a new non-crop commodity in the future, it must be added to this list.

---

## 11. Idempotency — Why Post Date vs "Today"

### 11.1 The Original Problem

The original idempotency check was:
```php
if (CropPriceHistory::where('source_date', today())->exists()) { skip; }
```

This broke when DA updates mid-day:
1. 6 AM run → finds yesterday's post → stores yesterday's date
2. 11 AM run → finds same post → idempotency says "yesterday's date already stored" → skips
3. DA posts new data at 1 PM → scraper never sees it because it keeps finding the same (old) post

### 11.2 The Fix

```php
$latestStoredDate = CropPriceHistory::max('source_date');
if ($post['date'] <= $latestStoredDate) { skip; }
```

Now:
1. 6 AM run → finds yesterday's post → `latestStoredDate = null` → stores yesterday's data
2. 11 AM run → finds same post → `latestStoredDate = yesterday` → post date ≤ stored → skips
3. DA posts at 1 PM → archive now shows today's post → `latestStoredDate = yesterday` → post date > stored → proceeds

### 11.3 Why This Works

- The scraper compares the **post's date** against the **latest stored date** — not against "today"
- If the post is older than what we already have, we skip (no duplicate work)
- If the post is newer, we scrape and store it (fresh data)
- This handles: DA posting late, DA not posting on weekends, scraper missing runs, and mid-day updates

---

## 12. Scraper Failure Tracking — Why `scraper_status` Table

### 12.1 Why Not Just Log Files?

**Question from panel**: "Why create a database table for scraper status instead of just using Laravel's log files?"

**Answer**:
- Log files are not queryable from the application — the dashboard can't read `storage/logs/laravel.log`
- The Market Prices Card needs to show "Last scraper run failed" — this requires structured data
- The admin dashboard needs to see scraper health at a glance
- `scraper_status` stores: status (success/failed/skipped), source_date, error message, records matched/skipped, timestamp
- Every scraper run writes one row — this creates an audit trail

### 12.2 What Triggers the Warning?

The Market Prices Card checks:
1. **Scraper never ran** → "Price scraper has not run yet"
2. **Last run failed** → "Last scraper run failed. Showing oldest available data."
3. **Data is >24 hours old** → Amber date badge with "3 days old" text

These three conditions cover all failure modes:
- Fresh install with no scraper runs
- Network errors during scraping
- DA site being down
- DA not posting on weekends/holidays

---

## 13. Key Files Reference

| File | Purpose |
|------|---------|
| `app/Services/Darfo12Service.php` | Core scraper service: fetch, OCR, parse, store, dashboard data |
| `app/Console/Commands/ScrapeDarfo12Prices.php` | Artisan command with idempotency + status logging |
| `app/Models/CropPriceHistory.php` | Historical price records (crop_id, source, date, price) |
| `app/Models/Crop.php` | Crop model with `baseline_price_per_kg` current snapshot |
| `app/Services/CropResolverService.php` | Crop normalization + auto-creation from "Other" input |
| `resources/views/components/market-prices-card.blade.php` | Shared DA RFO12 price display with staleness indicators |
| `app/Http/Controllers/DashboardController.php` | Farmer + logistics + driver dashboards |
| `app/Http/Controllers/BuyerController.php` | Buyer dashboard + crop board |
| `app/Http/Controllers/AdminController.php` | Admin dashboard + user management |
| `routes/console.php` | Scheduler: scraper at 6AM/11AM/4PM/9PM |
| `config/services.php` | Tesseract binary path configuration |
| `database/migrations/2026_07_17_203426_create_crop_price_history_table.php` | Price history schema |
| `database/migrations/2026_07_18_082316_create_scraper_status_table.php` | Scraper health tracking schema |
| `eval/DEPLOYMENT.md` | Production deployment guide |
| `eval/harvesthaul-system-flowchart.drawio` | Visual system flowchart |

---

## Appendix: Decision Log

| Decision | Date | Rationale |
|----------|------|-----------|
| Use DA RFO12 blog instead of national portal | 2026-07-17 | National portal blocks automated access; RFO12 blog is plain HTML |
| Use Tesseract instead of cloud OCR | 2026-07-17 | Free, local, sufficient accuracy for printed tables |
| Store prices in both `crops` and `crop_price_history` | 2026-07-17 | Read optimization: baseline for O(1) lookup, history for trends |
| Schedule scraper 4x daily (6AM/11AM/4PM/9PM) | 2026-07-18 | Unknown DA posting schedule; 4x catches all likely times without waste |
| Use word-boundary matching for non-crop filter | 2026-07-18 | Prevents "egg" matching "eggplant" false positives |
| Compare post date vs stored max for idempotency | 2026-07-18 | Handles mid-day updates, missed runs, and weekends correctly |
| Create `scraper_status` table | 2026-07-18 | Queryable health monitoring vs unqueryable log files |
| Remove B2B Pricing Guidance widget | 2026-07-18 | Artificial multiplier (×0.67) with no real data backing |
| Extract DA price loading to `getDashboardData()` | 2026-07-18 | DRY: 3 controllers had identical 25-line blocks |
| Add staleness indicator to Market Prices Card | 2026-07-18 | Prevents decisions based on stale data |
| Use `CropResolverService` for "Other" crops | 2026-07-17 | Hybrid dropdown + free text balances consistency with flexibility |
| Word-boundary matching for NON_CROP_COMMODITIES | 2026-07-18 | `str_contains` caused false positives; regex `\b` prevents partial matches |
