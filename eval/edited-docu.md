# HarvestHaul — Web-Based B2B Crop Distribution and Logistics Management System with Real-Time Tracking

**Technical Documentation (Reverse-Engineered from Codebase)**
**Generated:** 2026-07-12 | **Commit:** c2f9546

---

## 1. System Overview

HarvestHaul is a web-based Business-to-Business (B2B) crop distribution and logistics management platform built for agricultural stakeholders in General Santos City and Polomolok, Philippines. The system connects five user types — Administrator, Farmer, Logistics Partner, Driver, and Buyer — through a centralized platform that manages crop listings, B2B negotiation, route optimization, real-time GPS tracking, cost allocation, invoicing, and payment settlement.

**Key differentiators:**
- Classical optimization algorithms (knapsack + nearest-neighbor TSP) for route planning
- Real-time GPS tracking with WebSocket broadcast and offline-capable PWA for drivers
- Proportional cost allocation based on weight × distance scoring
- Weather-aware logistics via OpenWeatherMap integration
- Full audit trail across all system actions

---

## 2. User Roles and Access Control

| Role | Middleware | Registration | Verification |
|------|-----------|-------------|-------------|
| **Admin** | `EnsureUserIsAdmin` | Seeder/manual only | N/A (pre-verified) |
| **Farmer** | `EnsureUserIsFarmer` | Self-registration via `/register/farmer` | Admin verifies profile + documents |
| **Logistics Partner** | `EnsureUserIsLogistics` | Self-registration via `/register/logistics_partner` | Admin verifies business permit + profile |
| **Driver** | `EnsureUserIsDriver` | Created by Logistics Partner via `/drivers/create` | Admin verifies identity (ID photo + selfie) |
| **Buyer** | `EnsureUserIsBuyer` | Self-registration via `/register/buyer` | Admin verifies profile |

**Route protection:** All authenticated routes require email OTP verification. Role-based middleware gates access to role-specific route groups. An additional `EnsureAccountIsActive` middleware blocks suspended accounts.

---

## 3. Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Backend Framework** | Laravel 12 (PHP 8.2+) | Server-side logic, routing, ORM, authentication |
| **Database** | MySQL (SQLite for tests) | Relational data storage |
| **Frontend Styling** | Tailwind CSS 4 | Utility-first CSS framework |
| **Build Tool** | Vite 7.0.7 | Asset compilation (CSS + JS) |
| **Templating** | Blade | Dynamic HTML rendering |
| **Maps** | Leaflet + OpenStreetMap | Interactive geospatial views |
| **PDF Generation** | barryvdh/laravel-dompdf | Invoice PDF export |
| **Weather API** | OpenWeatherMap | Weather-based route advisories |
| **PWA** | Service Worker + Manifest | Offline-capable driver mobile experience |
| **IDE** | Visual Studio Code | Development environment |
| **Local Server** | XAMPP | Apache + MySQL + PHP for local development |

---

## 4. System Modules

### 4.1 User Registration and Management
- Multi-role registration with role-specific forms
- Email OTP verification (6-digit code, 10-minute expiry, 5 attempts/min throttle)
- Admin user CRUD with pagination (50 per page)
- Account activation/suspension toggle
- Password complexity enforcement (uppercase, lowercase, digit, special character, min 8 chars)

### 4.2 Crop Management (Admin)
- Three-tier hierarchy: Category → Crop → Variety
- Baseline price per kg per crop (admin-settable)
- CRUD operations for categories, crops, and varieties
- Crop photos uploaded at harvest listing (up to 5 images, 5MB each)

### 4.3 Harvest Listings (Farmer)
- Farmers post harvest listings with: crop variety, quantity (kg), price per kg, destination coordinates
- GPS coordinates auto-copied from farmer profile
- Status lifecycle: `active` → `negotiating` → `sold` → `assigned` → `in_progress` → `completed`
- Crop photos for buyer trust
- Philippine GPS bounds validation (4°N–21°N, 116°E–127°E)

### 4.4 B2B Crop Negotiation and Trading (Buyer ↔ Farmer)
- **Crop Board:** Buyers browse available harvest listings from verified farmers
- **Negotiation Chat Room:** Real-time messaging between Buyer and Farmer
- **Propose Terms:** Buyer proposes price/kg and volume
- **Agree Terms:** Both parties must agree
- **Finalize Deal:** Buyer provides drop-off coordinates; harvest status → `sold`
- **Stale Negotiation Timeout:** Auto-closes negotiations inactive for 7+ days
- Status: `OPEN` → `AGREED` → `COMPLETED` (or `CANCELLED`)

### 4.5 Route Optimization and Resource Pooling (Logistics Partner)
- **Leaflet Map Interface:** View all `sold` harvests on interactive map
- **Truck Selection:** Choose from available fleet (capacity 1,000–5,000 kg)
- **Plan Route:**
  1. Knapsack algorithm selects optimal harvest subset (exact 0/1 for n≤20, greedy fallback for n>20)
  2. Nearest-neighbor TSP orders stops by proximity
  3. Haversine formula computes distances
  4. Cost allocation: `(weight × individual_haul_distance) / total_score × price_reference`
  5. Reference price: `(distance × ₱15/km) + (weight × ₱0.50/kg) + ₱250 base`
  6. Weather checked per waypoint at plan time
- **Confirm Route:** Persists pooling job, reserves truck, notifies farmers
- **Auto-Assign Driver:** Finds nearest available driver using real-time GPS heartbeats (50km radius, 8-hour rest enforcement)

### 4.6 Pooling Job Lifecycle
- **Status Flow:** `pending` → `confirmed` → `in_progress` → `awaiting_confirmation` → `completed`
- **Farmer Actions:** Accept, Reject (harvest returns to `active`), Counter-propose (±75% of reference price, max 5 rounds)
- **Logistics Actions:** Accept farmer counter, Counter-offer (recalculates all cost shares)
- **Proposal Expiry:** 48 hours — auto-rejects via scheduled command
- **Partial Acceptance Detection:** Notifies logistics when some farmers accept and others reject

### 4.7 Driver PWA (Progressive Web App)
- **Installable:** Service Worker + manifest.json for home-screen installation
- **Offline Support:** IndexedDB `hh_telemetry` queue — GPS pings queued offline, flushed on reconnect
- **Job Dashboard:** View assigned jobs with route details, stop sequence, truck info
- **Stop Lifecycle:** `assigned` → `arrived` (geofence: ≤500m from farm GPS) → `loaded` (crop confirmed + load photo required) → `delivered` (delivery receipt required)
- **Fuel Logging:** Record liters, cost, odometer reading
- **GPS Telemetry:** Streams location every 15–30 seconds with speed/bearing computation
- **Wake Lock API:** Prevents screen from sleeping during active delivery

### 4.8 Real-Time GPS Tracking
- **GPS Ingestion:** `POST /driver/tracking/store` (throttled: 12 req/min + 5s app-level cooldown)
- **Data Validation:** Accuracy filter (>500m rejected), deduplication (same coords within 30s ignored)
- **Speed/Bearing:** Computed from consecutive GPS points using haversine + azimuth formula
- **WebSocket Broadcast:** DB polling every 2s + heartbeat every 30s + stale client cleanup after 120s
- **Fallback:** Polling every 10s when WebSocket unavailable
- **Data Retention:** Tracking records older than 30 days cleaned by scheduled command

### 4.9 ETA Calculation
- **Method:** Remaining distance through waypoints ÷ current speed (or 30 km/h default)
- **Speed Smoothing:** Median of last 3 GPS records rejects spikes >2x median
- **Terrain Multiplier:** 0.85× for rural Philippine roads
- **Confidence Scoring:** `confidence_score` (0.0–1.0) from GPS recency + speed stability + ping count
- **Data Quality Label:** `high` / `medium` / `low` / `stale`
- **Caching:** 10-second TTL to prevent over-fetching

### 4.10 Delay Detection
- **Stall Detection:** Speed <1 km/h for >15 min (warning) or >30 min (critical)
- **Stop Delay:** >20 min at arrived/loaded status without progression
- **Dark Detection:** No GPS for 10+ minutes
- **ETA-Based:** Active >2h with 0 stops completed
- **Auto-Escalation:** Critical delays get distinct escalation notification
- **Resolution Tracking:** Detects when stall clears → sends "Delay Resolved" notification
- **Runs:** Every 15 minutes via `delays:check` scheduled command

### 4.11 Weather Integration
- **API:** OpenWeatherMap (current + 3-hour forecast)
- **Per-Waypoint:** Checks depot, each farm, and destination
- **Advisory Generation:** Severe weather alerts (storms, heavy rain, extreme heat)
- **Route Planning:** Weather checked at plan time; `weather_alerts` + `weather_severe` fields populated
- **History:** Persisted to `weather_logs` table for post-trip analysis
- **Runs:** Every 30 minutes via `weather:check` scheduled command

### 4.12 Cost Ledger and Payment
- **Proportional Cost Allocation:** Per-farmer share based on weight × distance scoring
- **Cost Breakdown:** Visible in cost ledger view per pooling job
- **Payment Receipt Upload:** Farmers upload JPG/PNG/PDF (MIME validated, min 1KB)
- **Mark-as-Paid:** Logistics verifies receipt, marks payment complete, `paid_at` recorded
- **Payment Status:** `unpaid` → `submitted` → `paid`
- **Fleet Analytics:** Revenue, net income, fuel cost, KPL per truck

### 4.13 Invoice Generation
- **Trigger:** Hourly scheduled command for completed jobs
- **Format:** PDF via DomPDF + HTML fallback
- **Invoice Number:** `HH-INV-YYYYMMDD-XXXXX`
- **Content:** Route details, farmer list with crops/qty/cost, total amount
- **Due Date:** 30 days from generation
- **Delivery:** Emailed to logistics partner + all farmers via Gmail SMTP
- **Voiding:** Admin/logistics can void with reason tracking

### 4.14 Notification System
- **Channels:** Database + Email (Gmail SMTP)
- **Types:** Delay alerts, invoice ready, proposal notifications, negotiation messages
- **API:** Paginated with `per_page`, `current_page`, `last_page`; filterable by `type` and `category`
- **Read Tracking:** `read_at` timestamp, mark-all-read endpoint
- **Retention:** Read notifications >90 days auto-cleaned

### 4.15 Admin Console
- **User Management:** CRUD, pagination (50/page), account toggle, password reset
- **Farmer Verification:** Review profiles + documents, approve/reject
- **Logistics Verification:** Review business permits + profiles, approve/reject
- **Buyer Verification:** Review profiles, approve/reject
- **Driver Identity Verification:** Review ID photo + selfie, verify/reject
- **Crop Manager:** Category → Crop → Variety hierarchy CRUD
- **Baseline Pricing:** Set per-crop reference prices
- **Analytics Dashboard:** Crop pricing trends, fleet efficiency, fuel cost summary
- **Audit Logs:** Searchable log of all system actions
- **Data Export:** CSV export for users and harvests (PDF via DomPDF)

### 4.16 Compliance Document Management
- **Farmer Documents:** Upload government ID, RSBSA certificate → admin reviews
- **Logistics Documents:** Upload business permits, CDA registration → admin reviews
- **Approval Workflow:** Admin approve/reject with status tracking

### 4.17 Activity Audit Logging
- Every driver status change, admin action, invoice generation, payment update, and user action logged
- Immutable audit trail for compliance and dispute resolution

---

## 5. Database Schema

### Core Entities (24 models, 63 migrations)

| Entity | Key Fields | Relationships |
|--------|-----------|--------------|
| **User** | role (enum: 5 values), verification_status, status, email_otp | hasOne: FarmerProfile, LogisticsProfile, BuyerProfile, DriverProfile |
| **Harvest** | crop_variety_id, quantity_kg, price_per_kg, status, coordinates, crop_photos | belongsTo: User, CropVariety; belongsToMany: PoolingJob |
| **PoolingJob** | logistics_user_id, status, route_geometry, negotiated_price, weather fields | belongsTo: User (logistics); belongsToMany: Harvest (pivot) |
| **PoolingJobHarvest** (pivot) | cost_share, status, payment_status, loaded_quantity_kg, crop_confirmed | Custom pivot model with `stop_duration` accessor |
| **Negotiation** | buyer_id, farmer_id, harvest_id, negotiated_price, negotiated_volume, status | belongsTo: User, Harvest; hasMany: NegotiationMessage |
| **TrackingRecord** | pooling_job_id, lat, lng, speed, bearing, posted_at, accuracy_meters | belongsTo: PoolingJob |
| **Invoice** | invoice_number, total_amount, pdf_path, status, due_at, sent_at | belongsTo: PoolingJob |
| **Crop** | crop_name, crop_category_id, baseline_price_per_kg | belongsTo: CropCategory; hasMany: CropVariety |
| **Truck** | name, plate_number, vehicle_type, capacity_kg, status, notes | belongsTo: User (logistics) |

### Supporting Tables
- `farmer_profiles`, `logistics_profiles`, `buyer_profiles`, `driver_profiles`
- `crop_categories`, `crop_varieties`
- `destinations`, `fuel_logs`, `weather_logs`, `driver_heartbeats`, `driver_schedules`
- `notifications`, `audit_logs`, `farmer_documents`, `logistics_documents`

---

## 6. Scheduled Commands

| Command | Frequency | Purpose |
|---------|-----------|---------|
| `deliveries:auto-complete` | Hourly | Auto-complete deliveries awaiting buyer confirmation >48h |
| `deliveries:auto-complete-stale` | Hourly | Auto-complete stale in_progress deliveries >48h |
| `delays:check` | Every 15 min | Detect stalls and stop delays |
| `invoices:generate` | Hourly | Auto-generate invoices for completed jobs |
| `weather:check` | Every 30 min | Check weather for active jobs |
| `proposals:auto-reject-expired` | Hourly | Auto-reject expired pooling proposals (48h) |
| `negotiations:auto-close-stale` | Daily | Auto-close stale OPEN negotiations (7 days) |
| `data:cleanup` | Daily | Clean up stale tracking records, old notifications, weather logs |

---

## 7. Security Measures

- **Authentication:** Email + password with OTP verification (6-digit, 10-min expiry)
- **Rate Limiting:** Login (5/min), OTP verify (5/min), OTP resend (3/min), GPS tracking (12/min), negotiations (10-15/min)
- **Authorization:** 5 role-based middleware classes + `EnsureAccountIsActive`
- **Input Validation:** Laravel validation rules on all endpoints, PH GPS bounds, MIME type checks
- **Password Security:** Bcrypt hashing (12 rounds), complexity requirements (upper, lower, digit, special char)
- **OTP Security:** `hash_equals()` for constant-time comparison, hidden from JSON serialization
- **Race Condition Protection:** `lockForUpdate()` on truck and harvest during confirmation
- **WebSocket Auth:** Token-based handshake validation
- **PWA Security:** Service Worker scoped to same origin

---

## 8. Scope and Limitations

### In Scope
- B2B crop distribution logistics within General Santos City and Polomolok
- Crop, fruit, and vegetable transportation coordination
- Real-time GPS tracking and delivery monitoring
- Route optimization with classical algorithms
- Cost allocation and manual payment settlement
- Weather-aware logistics advisories
- Progressive Web App for driver field operations

### Not in Scope
- Third-party payment gateway integration (Stripe, PayPal, GCash)
- AI/ML-based route optimization or predictive analytics
- IoT sensor integration
- Native mobile applications (PWA only)
- Intercity/regional/nationwide logistics
- Livestock, fisheries, or processed goods
- Crop production management or farm management

---

*Generated from codebase analysis at commit c2f9546. Based on Diátaxis Framework — Reference quadrant.*
