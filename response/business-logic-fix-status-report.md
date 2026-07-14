# Business Logic Vulnerability Fix Status Report
**Date:** July 12, 2026  
**Scope:** 12 business logic vulnerabilities identified in pentest  
**Result:** All 12 addressed (10 fixed, 2 pre-fixed in code review)

---

## Summary

| ID | Vulnerability | Severity | OWASP | Status |
|----|--------------|----------|-------|--------|
| BL#1 | OTP brute-force (no rate limit) | Critical | WSTG-BUSL-05 | Pre-fixed (code review) |
| BL#2 | Counter-proposal price manipulation | High | WSTG-BUSL-01/02 | Fixed |
| BL#3 | Logistics unilaterally resets farmer prices | High | WSTG-BUSL-02/06 | Fixed |
| BL#4 | Reject sets harvest to 'sold' (B2B bypass) | High | WSTG-BUSL-06 | Pre-fixed (code review) |
| BL#5 | Unbounded quantity confirmation | Medium | WSTG-BUSL-01 | Fixed |
| BL#6 | Admin route authorization fragility | High | WSTG-BUSL-03 | Fixed |
| BL#7 | Proposal expiration never enforced | Medium | WSTG-BUSL-05/06 | Fixed |
| BL#8 | Post-finalize term manipulation | Medium | WSTG-BUSL-06 | Fixed |
| BL#9 | Harvest delete disrupts pooling jobs | Low | WSTG-BUSL-06 | Fixed |
| BL#10 | Login brute-force (no rate limit) | High | WSTG-BUSL-05 | Fixed |
| BL#11 | Admin middleware vs method pattern | Medium | WSTG-BUSL-03 | Fixed |
| BL#12 | Race condition in harvest attachment | Low | WSTG-BUSL-03 | Fixed |

---

## Fix Details

### BL#2 — Counter-proposal price manipulation
**File:** `app/Http/Controllers/PoolingJobController.php:479-490`  
**Change:** Added validation that `counter_price` must be within ±75% of `$poolingJob->price_reference`. Returns error with min/max bounds if outside range.  
**Before:** Any numeric value 1–999,999 accepted.  
**After:** Counter-price bounded to 25%–175% of reference price.

### BL#3 — Logistics unilaterally resets farmer prices
**File:** `app/Http/Controllers/PoolingJobController.php:604-625`  
**Change:** Moved `recalculateCostShares()` call **before** the farmer notification loop so individual cost shares are recalculated proportionally before farmers are asked to re-approve. Added "Your cost share has been recalculated" to notification message.  
**Before:** `negotiated_price` was set but individual `cost_share` values were not recalculated.  
**After:** Cost shares recomputed proportionally before farmer notification.

### BL#5 — Unbounded quantity confirmation
**File:** `app/Http/Controllers/CostLedgerController.php:218-222`  
**Change:** Added `max:{harvest.quantity_kg}` to validation rule and explicit server-side check that `actual_quantity_kg <= harvest->quantity_kg`.  
**Before:** No upper bound on `actual_quantity_kg`.  
**After:** Capped at harvest's posted quantity.

### BL#6 + BL#11 — Admin route authorization
**Files:**  
- `app/Http/Middleware/EnsureUserIsAdmin.php` (new)  
- `bootstrap/app.php:23` (registered alias)  
- `routes/web.php:295` (applied middleware)  

**Change:** Created `EnsureUserIsAdmin` middleware matching existing role middleware pattern. Registered `'admin'` alias in `bootstrap/app.php`. Applied `middleware('admin')` to the admin route group prefix. Retained `$this->adminOnly()` as defense-in-depth.  
**Before:** Every admin method relied on manual `$this->adminOnly()` call.  
**After:** Route-level middleware + method-level guard (belt and suspenders).

### BL#7 — Proposal expiration never enforced
**File:** `app/Http/Controllers/PoolingJobController.php` (5 methods)  
**Change:** Added expiration check to `acceptProposal`, `rejectProposal`, `counterProposal`, `logisticsAcceptCounter`, and `logisticsCounter`:  
```php
if ($poolingJob->proposal_expires_at && $poolingJob->proposal_expires_at->isPast()) {
    abort(410, 'This proposal has expired.');
}
```
**Before:** `proposal_expires_at` was set but never checked.  
**After:** All proposal actions return 410 Gone if expired.

### BL#8 — Post-finalize term manipulation
**File:** `app/Http/Controllers/NegotiationController.php:155-157`  
**Change:** Changed status check from `=== 'AGREED'` to `in_array($status, ['AGREED', 'COMPLETED'])`.  
**Before:** Could re-propose terms after deal finalized.  
**After:** Both AGREED and COMPLETED block re-proposing.

### BL#9 — Harvest delete disrupts pooling jobs
**Files:**  
- `app/Http/Controllers/HarvestController.php:299-302` (check before delete)  
- `app/Models/Harvest.php:125-130` (new `poolingJobs()` relationship)  

**Change:** Added check that harvest is not attached to any active pooling job (pending/confirmed/in_progress) before allowing deletion. Added `poolingJobs()` many-to-many relationship to Harvest model.  
**Before:** Could delete harvest mid-route, orphaning pooling job data.  
**After:** Blocked with user-facing error message.

### BL#10 — Login brute-force
**File:** `routes/web.php:77`  
**Change:** Added `middleware('throttle:5,1')` to the POST `/login` route (5 attempts per minute per IP).  
**Before:** Unlimited login attempts.  
**After:** Throttled to 5/minute.

### BL#12 — Race condition in harvest attachment
**File:** `app/Http/Controllers/PoolingJobController.php:180-187`  
**Change:** After `$poolingService->confirm()`, wrapped harvest status updates in a `DB::transaction()` with `lockForUpdate()` to prevent two concurrent confirmations from attaching the same harvest.  
**Before:** Harvest status updated without row-level locking.  
**After:** Pessimistic lock prevents duplicate assignment.

---

## Files Modified

| File | Changes |
|------|---------|
| `app/Http/Middleware/EnsureUserIsAdmin.php` | New — role:admin middleware |
| `bootstrap/app.php` | Registered `'admin'` alias |
| `routes/web.php` | Added `throttle:5,1` to login POST; added `middleware('admin')` to admin group |
| `app/Http/Controllers/PoolingJobController.php` | Price bounds (BL#2), cost share recalc (BL#3), expiration checks (BL#7), lockForUpdate (BL#12) |
| `app/Http/Controllers/CostLedgerController.php` | Quantity cap (BL#5) |
| `app/Http/Controllers/NegotiationController.php` | Block COMPLETED re-propose (BL#8) |
| `app/Http/Controllers/HarvestController.php` | Pooling job check before delete (BL#9) |
| `app/Models/Harvest.php` | Added `poolingJobs()` relationship |

---

## Risk Assessment

- **Low regression risk:** All fixes are additive guards (validation, middleware, DB locks) — no business logic rewrites.
- **BL#7 expiration check:** Existing `proposal_expires_at` column already stores 48hr expiry; now enforced. No migration needed.
- **BL#12 lockForUpdate:** Requires database driver supporting row-level locks (MySQL/PostgreSQL). SQLite fallback is safe but non-locking (acceptable for dev).
