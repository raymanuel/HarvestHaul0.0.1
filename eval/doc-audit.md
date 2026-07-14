# Documentation Audit Report

**Generated:** 2026-07-12
**Commit:** c2f9546
**Document Audited:** `docs/Capstone-1-Manuscript-lang-1.md`
**Codebase:** HarvestHaul 0.0.1

---

## Executive Summary

| Metric | Count |
|--------|-------|
| Claims Verified | 20 |
| Verified TRUE | 2 (10%) |
| Verified FALSE | 9 (45%) |
| Partially TRUE | 4 (20%) |
| Unverifiable | 5 (25%) |

**The documentation has significant drift from the actual codebase.** The most critical issue is the complete omission of the Buyer role (a fully implemented first-class user), false claims about no payment processing and no crop trading, and inaccurate technical details in the software tools appendix.

---

## False Claims Requiring Fixes

### CRITICAL — Buyer Role Entirely Missing (Claims #1, #20)

**Doc Lines:** 183, 195, 201, 209, 511, 527, 593
**Doc States:** "Administrator, Logistics Coordinator, Driver, and Farmer" — 4 roles throughout

**Reality:** The system has **5 roles**: `admin`, `farmer`, `logistics_partner`, `driver`, `buyer`.

| Evidence | Location |
|----------|----------|
| Role enum in DB | Migration `2026_06_20_151100_update_users_role_enum.php`: `ENUM('admin','farmer','logistics_partner','driver','buyer')` |
| Dedicated middleware | `app/Http/Middleware/EnsureUserIsBuyer.php` |
| Dedicated controller | `app/Http/Controllers/BuyerController.php` (212 lines) |
| Dedicated routes | `routes/web.php:247-253`: `/buyer/crop-board`, `/buyer/negotiations`, `/buyer/tracking`, `/buyer/deliveries/{job}/confirm` |
| Dedicated views | `resources/views/buyer/` (6 Blade templates) |
| Registration support | `RegisterController.php:25`: `$validRoles = ['farmer', 'logistics_partner', 'buyer']` |
| Admin management | `routes/web.php:320-322`: `/admin/buyers`, `/admin/buyers/{user}/verify`, `/admin/buyers/{user}/reject` |
| Dashboard routing | `DashboardController.php:133`: `'buyer' => app(BuyerController::class)->dashboard()` |

**Fix:** Add "Buyer" as the fifth user role throughout the entire document. Document the Buyer Module including Crop Board, B2B Negotiation, Delivery Tracking, and Receipt Confirmation.

---

### CRITICAL — "No Payment Processing" is False (Claim #2)

**Doc Lines:** 173, 251, 539
**Doc States:** "does not include crop trading, payment processing" / "The system does not process online payments, financial transactions"

**Reality:** The system has substantial payment/financial features:

| Feature | Evidence |
|---------|----------|
| Cost Ledger | `CostLedgerController.php` (334 lines) — proportional cost allocation per farmer |
| Payment receipt upload | `CostLedgerController::uploadReceipt()` — farmers upload JPG/PNG/PDF receipts |
| Mark-as-paid flow | `CostLedgerController::markPaid()` — logistics verifies receipt, marks `paid` |
| Payment status tracking | Pivot table: `unpaid` → `submitted` → `paid` |
| Invoice generation | `InvoiceService.php` + `invoices` table — auto-generated for completed jobs |
| Fleet analytics | `CostLedgerController::fleetAnalytics()` — revenue, net income, fuel cost per truck |
| Reference pricing | `ResourcePoolingService.php:157-162`: `(distance × 15) + (weight × 0.50) + 250` |

**Fix:** Revise to: "The system does not integrate third-party payment gateways (e.g., Stripe, PayPal). However, it includes a cost ledger for proportional freight cost allocation, payment receipt upload and verification, invoice generation, and fleet revenue analytics."

---

### CRITICAL — "No Crop Trading" is False (Claim #3)

**Doc Lines:** 173, 235, 521
**Doc States:** "does not cover crop trading" / "does not include crop trading"

**Reality:** The system has a complete B2B crop trading/negotiation module:

| Feature | Evidence |
|---------|----------|
| Crop Board | `BuyerController::cropBoard()` — buyers browse available harvests |
| Start Negotiation | `NegotiationController::start()` — buyer initiates on a harvest |
| Chat Room | `NegotiationController::sendMessage()` — real-time messaging |
| Propose Terms | `NegotiationController::proposeTerms()` — price/kg + volume negotiation |
| Agree Terms | `NegotiationController::agreeTerms()` — mutual agreement |
| Finalize Deal | `NegotiationController::finalizeDeal()` — harvest → `sold`, deal → `COMPLETED` |
| Database entities | `negotiations` table with `buyer_id`, `farmer_id`, `harvest_id`, `negotiated_price`, `negotiated_volume` |

**Fix:** Remove "does not cover crop trading" from limitations. Document the B2B Negotiation Module as a key feature.

---

### HIGH — In-App Messaging is Buyer-Farmer, Not Farmer-Logistics (Claim #8)

**Doc Lines:** 219-225
**Doc States:** "allows Farmers and Logistics Coordinators to communicate directly"

**Reality:** The `NegotiationController` messaging is between **Buyers and Farmers**:

| Evidence | Location |
|----------|----------|
| Negotiation model | `buyer_id` + `farmer_id` columns — no `logistics_coordinator_id` |
| Access control | `NegotiationController::room()` — accessible only by `buyer_id` or `farmer_id` |
| Start method | `NegotiationController::start()` — initiator must be `buyer` role |

Farmer-Logistics coordination uses **structured proposal/counter-offer workflows** via `PoolingJobController`, not chat-based messaging.

**Fix:** Revise to: "The in-app messaging feature allows Buyers and Farmers to negotiate pricing, volume, and deal terms. Logistics coordination between Farmers and Logistics Coordinators is handled through structured proposal and counter-offer workflows."

---

### HIGH — 5 Modules Listed, System Has 10-12 (Claim #9)

**Doc Lines:** 203-229
**Doc States:** 5 modules: User Registration, Pickup/Drop-Off/Route Planning, Delivery Monitoring/Tracking, In-app Messaging, PWA

**Missing Modules:**

| Module | Controller | Lines |
|--------|-----------|-------|
| Cost Ledger & Payment | `CostLedgerController.php` | 334 |
| B2B Crop Negotiation | `NegotiationController.php` | 275 |
| Crop Management (Admin) | `Admin/CropManagerController.php` | Full CRUD |
| Analytics & Reporting | `AdminController::analytics()`, `CostLedgerController::fleetAnalytics()` | Multiple |
| Invoice Generation | `InvoiceService.php` | PDF + HTML |
| Activity Audit Logging | `AuditLog` model | Used across all controllers |
| Notification System | `NotificationController.php` + 3 Notification classes | Real-time + email |
| Weather Integration | `WeatherService.php` + scheduled command | OpenWeatherMap |
| Compliance Documents | `FarmerDocumentController`, `LogisticsDocumentController` | Upload + admin review |
| Driver Identity Verification | `DriverController::uploadIdentity()` | ID photo + selfie |

**Fix:** Add at minimum: (6) Cost Ledger & Payment Tracking, (7) B2B Crop Negotiation & Trading, (8) Crop Management & Hierarchy, (9) Analytics & Reporting Dashboard, (10) Notification System, (11) Compliance Document Management.

---

### HIGH — Pricing Formula Factors are Wrong (Claim #19)

**Doc Line:** 251
**Doc States:** "pricing is based on distance, delivery volume, fuel cost, and crop type"

**Reality:** The actual formula in `ResourcePoolingService.php:157-162`:

```
priceReference = (totalDistance × 15.00) + (totalKg × 0.50) + 250.00
```

| Doc Claim | Actual |
|-----------|--------|
| "distance" | Correct — PHP 15/km |
| "delivery volume" | Correct — PHP 0.50/kg |
| "fuel cost" | **Wrong** — fuel is tracked separately in FuelLog for analytics only |
| "crop type" | **Wrong** — crop type has zero effect on pricing formula |
| (not mentioned) | Fixed base fee: PHP 250 |

**Fix:** Revise to: "Transportation pricing uses a reference formula: PHP 15/km × route distance + PHP 0.50/kg × cargo weight + PHP 250 fixed base fee. Per-farmer cost allocation is proportional to each farmer's cargo weight × individual haul distance."

---

### MEDIUM — Security Section Lists Only 3 of 5 Roles (Claim #17)

**Doc Line:** 593
**Doc States:** "Admin, Drivers, and Logistics Coordinator have different permissions"

**Reality:** 5 roles with dedicated middleware:
- `EnsureUserIsAdmin.php` — `role === 'admin'`
- `EnsureUserIsFarmer.php` — `role === 'farmer'`
- `EnsureUserIsLogistics.php` — `role === 'logistics_partner'`
- `EnsureUserIsDriver.php` — `role === 'driver'`
- `EnsureUserIsBuyer.php` — `role === 'buyer'`

**Fix:** Revise to: "The system implements role-based access control with five distinct roles: Administrator, Logistics Coordinator, Driver, Farmer, and Buyer. Each role has dedicated middleware enforcing permission boundaries."

---

### MEDIUM — Java Listed in Software Tools (Claim #14)

**Doc Line:** 742
**Doc States:** "Java structured the interface for a responsive layout"

**Reality:** Zero `.java` files in the project. No Java dependency in `composer.json` or `package.json`. This is a Laravel/PHP project using Blade + Tailwind CSS.

**Fix:** Remove Java. Replace with: "PHP (backend via Laravel), JavaScript (frontend interactivity), HTML/CSS (Blade templates with Tailwind CSS)."

---

### MEDIUM — Android Studio Listed as IDE (Claim #15)

**Doc Line:** 743
**Doc States:** Android Studio listed alongside VS Code

**Reality:** No Android native code exists. No `AndroidManifest.xml`, no `.java`/`.kt` files, no `build.gradle`. The mobile component is a PWA accessed through browsers.

**Fix:** Remove Android Studio. Only IDE used is Visual Studio Code.

---

### MEDIUM — Geographic Limitation Not Enforced in Code (Claim #11)

**Doc Lines:** 175, 235
**Doc States:** System limited to General Santos City and Polomolok

**Reality:** No geo-fencing anywhere in the codebase. Grep for "General Santos" and "Polomolok" in `app/` returns zero matches. Coordinates validated as generic `numeric|between:-90,90` (lat) and `numeric|between:-180,180` (lng) — valid for any location on Earth.

**Fix:** Clarify: "The system is designed for General Santos City and Polomolok but does not enforce geographic restrictions at the software level."

---

## Partially True Claims

### Tech Stack Omissions (Claim #4)

**Doc Lines:** 417-435, 555-577
**Partially True:** Core stack (Laravel, MySQL, Tailwind, PWA) confirmed. But missing:
- **Vite** — build tool compiling Tailwind + JS (confirmed in `vite.config.js`)
- **barryvdh/laravel-dompdf** — PDF invoice generation (confirmed in `composer.json`)
- **OpenWeatherMap API** — weather-based route alerts (confirmed in `.env.example:67`)

### PWA "Only for Drivers" (Claim #6)

**Partially True:** The service worker (`public/sw.js`) is driver-specific (offline GPS telemetry). But the PWA manifest (`public/manifest.json`) is global — any user on a mobile browser can install the app. No role restriction in the manifest.

### "No Automated Delivery Verification" (Claim #18)

**Partially True:** No AI/ML verification. But the system has extensive manual/hybrid verification:
- GPS geofence check (500m radius) — `DriverController::updateStopStatus()`
- Photo evidence (load photo + delivery receipt required)
- Crop confirmation by driver
- Quantity validation
- Odometer tracking
- Buyer receipt confirmation

### Vercel Deployment (Claim #5)

**Unverifiable / Likely False:** No `vercel.json` at project root. The only `vercel.json` found belongs to the unrelated `page-ui-main` template. No Laravel adapter config for Vercel exists.

---

## True Claims

| Claim | Verification |
|-------|-------------|
| No AI/ML route optimization (#7) | Confirmed — uses classical algorithms (knapsack, nearest-neighbor TSP, haversine) |
| MySQL database (#13) | Confirmed — `.env.example:23`: `DB_CONNECTION=mysql` |

---

## Unverifiable Claims

| Claim | Reason |
|-------|--------|
| Scrum Methodology (#10) | Process claim — cannot verify from code |
| Hardware Specs (#12) | Physical attributes — cannot verify from code |
| phpMyAdmin Used (#16) | Not referenced anywhere in codebase; may have been used manually via XAMPP but is not part of the application |

---

## Pattern Summary

| Pattern | Count | Root Cause |
|---------|-------|------------|
| Buyer role omitted | 9 doc locations | Buyer was added after initial documentation was written |
| "No trading/payment" false | 3 doc locations | Scope narrowed in doc but features were built |
| Incorrect technical details | 3 items | Software tools table copied from template/another project |
| Module count understated | 5+ missing modules | Documentation not updated as features were added |

---

## Priority Fix Order

1. **Immediate:** Add Buyer as 5th role throughout document (9+ locations)
2. **Immediate:** Remove "no crop trading" and "no payment processing" claims
3. **High:** Document B2B Negotiation Module and Cost Ledger Module
4. **High:** Fix messaging description (Buyer-Farmer, not Farmer-Logistics)
5. **High:** Correct pricing formula factors
6. **Medium:** Update software tools table (remove Java, Android Studio, phpMyAdmin)
7. **Medium:** Add missing modules to module list
8. **Low:** Clarify geographic limitation is intent, not code constraint
9. **Low:** Add Vite, DomPDF, OpenWeatherMap to tech stack
