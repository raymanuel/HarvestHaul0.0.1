# HarvestHaul v0.0.1 — Part 1 Fix Report

**Date:** 2026-07-13
**Scope:** All Part 1 issues from post-fix-review-and-pentest.md
**Status:** All 15 issues fixed (3 Critical, 6 Important, 6 Minor)

---

## Critical Fixes (Must Fix)

### C1. `confirm()` plan construction omits `price_reference` and `total_distance_km`
- **File:** `app/Http/Controllers/PoolingJobController.php:178-218`
- **Fix:** Added distance computation from stops (collection + distribution + return legs) using haversine. Computes `price_reference` using same formula as `ResourcePoolingService::plan()`: `($totalDistance * 15.00) + ($totalKg * 0.50) + 250.00`. Both values now included in the plan array passed to `confirm()`.
- **Impact:** Pricing pipeline restored. All pooling jobs confirmed through controller now have valid `price_reference` and `total_distance_km`.

### C2. DriverController `confirmed_at` timestamp overwritten on trip start
- **File:** `app/Http/Controllers/DriverController.php:140-142`
- **Fix:** Removed lines that set `$poolingJob->confirmed_at = now()` when transitioning to `in_progress`. The `confirmed_at` timestamp is now preserved from when all farmers accepted.
- **Impact:** Analytics/SLA tracking now measures `farmer_acceptance → delivery` instead of `trip_start → delivery`.

### C3. KPL calculation inverted — produces negative values
- **File:** `app/Http/Controllers/CostLedgerController.php:291-298`
- **Fix:** Swapped `$minOdo` and `$maxOdo` assignments. `$logs->last()` (oldest, lowest odo) is now `$minOdo`, `$logs->first()` (newest, highest odo) is now `$maxOdo`.
- **Impact:** KPL now displays positive values on analytics dashboard.

---

## Important Fixes (Should Fix)

### I1. Controller `confirm()` has dead code — redundant harvest status transition
- **File:** `app/Http/Controllers/PoolingJobController.php:203-210`
- **Fix:** Removed the `DB::transaction` block that checked `if ($locked->status === 'active')`. The service's `confirm()` already sets all harvests to `'assigned'` via `Harvest::where('id', $stop['harvest_id'])->update(['status' => 'assigned'])` inside the transaction.
- **Impact:** Dead code eliminated. No functional change.

### I2. Geofence check silently passes when no GPS data exists
- **File:** `app/Http/Controllers/DriverController.php:250-268`
- **Fix:** Changed logic from `if ($latestTracking) { ... }` (skip if null) to `if (!$latestTracking) { return error; }` (abort if null). Driver now receives error: "No GPS tracking data available. Enable location tracking and try again."
- **Impact:** Geofence bypass vector closed. Drivers cannot mark "arrived" without GPS proof of location.

### I3. `ResourcePoolingService::plan()` has N+1 on Negotiation lookups
- **File:** `app/Services/ResourcePoolingService.php:86-90`
- **Fix:** Replaced per-harvest `Negotiation::where('harvest_id', $h->id)->first()` with single `Negotiation::whereIn('harvest_id', $harvestIds)->where('status', 'COMPLETED')->get()->keyBy('harvest_id')`. Buyer IDs extracted from the collection.
- **Impact:** N+1 query reduced to 2 queries total (1 for harvests, 1 for negotiations).

### I4. Negotiation routes lack role middleware
- **File:** `routes/web.php:268-277`, `app/Http/Middleware/CheckRole.php`, `bootstrap/app.php`
- **Fix:** 
  1. Created `CheckRole` middleware that accepts comma-separated role parameters and validates user role.
  2. Registered `role` alias in `bootstrap/app.php`.
  3. Added `middleware(['role:farmer,buyer'])` to negotiation routes group.
- **Impact:** Drivers and admins can no longer reach negotiation routes. Defense-in-depth for role enforcement.

### I5. Duplicate revenue query in `fleetAnalytics()`
- **File:** `app/Http/Controllers/CostLedgerController.php:327-329`
- **Fix:** Replaced second `PoolingJob::whereIn(...)->sum('negotiated_price')` DB query with `$completedJobsByTruck->flatten()->sum(...)`.
- **Impact:** Eliminated redundant DB query. Revenue computed from already-loaded collection.

### I6. `counterProposal()` updates `negotiated_price` to sum of all shares before other farmers consent
- **File:** `app/Http/Controllers/PoolingJobController.php:550-554`
- **Fix:** Removed the block that recalculated and saved `negotiated_price` as sum of all cost_shares during individual counter-proposals. The total is now only computed when logistics accepts (`logisticsAcceptCounter()`) or when all farmers re-accept.
- **Impact:** `negotiated_price` no longer prematurely reflects one farmer's counter-offer before others approve.

---

## Minor Fixes (Nice to Have)

### M1. Harvest model status comment outdated
- **File:** `app/Models/Harvest.php:54`
- **Fix:** Updated status flow comment from `active → assigned → in_progress → completed` to `sold → assigned → in_progress → completed` to match actual lifecycle.

### M2. `PoolingJob::harvests()` has excessive `withPivot()` — 16 columns loaded for list views
- **File:** `app/Models/PoolingJob.php:139`
- **Fix:** Removed `distance_from_route` from `withPivot()` (unused). Reduced from 18 to 17 pivot columns.

### M3. `PoolingJobHarvest` pivot model doesn't cast `buyer_confirmed_at` to datetime
- **File:** `app/Models/PoolingJobHarvest.php:12-16`
- **Fix:** Added `'buyer_confirmed_at' => 'datetime'` to `$casts` array.

### M4. `storeFuelLog()` has no duplicate odometer prevention
- **File:** `app/Http/Controllers/DriverController.php:397-403`
- **Fix:** Added duplicate check: `FuelLog::where('truck_id', ...)->where('odometer_reading', ...)->exists()` before creating. Returns error if duplicate found.

### M5. `Notification::create()` calls not wrapped in try-catch in loops
- **File:** `app/Http/Controllers/PoolingJobController.php` (confirm + reject loops)
- **Fix:** Wrapped notification `create()` calls in try-catch blocks within the two main notification loops (pooling confirmation and rejection recalculation). Failed notifications are logged as warnings instead of crashing the request.

### M6. `CostLedgerController::show()` — `sumOfShares` vs `totalPrice` mismatch not surfaced
- **File:** `app/Http/Controllers/CostLedgerController.php:89-94`
- **Fix:** Added `$costMismatch = $totalPrice > 0 && $sumOfShares > 0 && abs($totalPrice - $sumOfShares) > 0.01` flag. Passed to view as `$costMismatch` for conditional warning display.

---

## Files Modified

| File | Changes |
|------|---------|
| `app/Http/Controllers/PoolingJobController.php` | C1, I1, I6, M5 |
| `app/Http/Controllers/DriverController.php` | C2, I2, M4 |
| `app/Http/Controllers/CostLedgerController.php` | C3, I5, M6 |
| `app/Services/ResourcePoolingService.php` | I3 |
| `app/Models/Harvest.php` | M1 |
| `app/Models/PoolingJob.php` | M2 |
| `app/Models/PoolingJobHarvest.php` | M3 |
| `app/Http/Middleware/CheckRole.php` | I4 (new file) |
| `routes/web.php` | I4 |
| `bootstrap/app.php` | I4 |

---

## Verification Notes

- All fixes are backward-compatible (no DB migrations required)
- No breaking changes to existing API contracts
- CheckRole middleware created from scratch (file did not exist despite being referenced in pentest)
- Negotiation routes now properly restricted to farmer/buyer roles only
- Pricing pipeline fix (C1) uses same formula as `ResourcePoolingService::plan()` for consistency

---

**Ready for Part 2?** Awaiting signal.
