# HarvestHaul v0.0.1 — Part 2 Fix Report (Business Logic Pentest)

**Date:** 2026-07-13
**Scope:** All Part 2 issues from post-fix-review-and-pentest.md
**Status:** All 10 pentest findings fixed (2 Critical, 4 Important, 4 Minor)

---

## Critical Fixes

### P1. agreeTerms() — No Status Guard → Delivery Redirection [WSTG-BUSL-03]
- **File:** `app/Http/Controllers/NegotiationController.php:199-206`
- **Fix:** Added status guard: `if ($negotiation->status !== 'OPEN') return error`. Only OPEN negotiations can transition to AGREED. Prevents re-opening COMPLETED deals to redirect delivery to arbitrary GPS coordinates.
- **Impact:** Closes delivery redirection attack vector. COMPLETED negotiations can no longer be reverted to AGREED.

### P2. confirmQuantity() — Progressive Harvest Quantity Reduction [WSTG-BUSL-01/03]
- **File:** `app/Http/Controllers/CostLedgerController.php:215-232`
- **Fix:**
  1. Added idempotency guard: `if ($harvest->pivot->farmer_qty_confirmed) return error` — prevents re-confirmation.
  2. Changed max validation to use `$harvest->pivot->quantity_kg` (original posted amount) instead of `$harvest->quantity_kg` (mutable field).
  3. Removed `$harvest->update(['quantity_kg' => ...])` — harvest record is never mutated. Confirmed quantity stored exclusively in pivot's `actual_quantity_kg`.
- **Impact:** Progressive reduction attack eliminated. Harvest quantity_kg is immutable after posting. Confirmed amounts only affect pivot table.

---

## Important Fixes

### P3. confirm() — Arbitrary total_kg Without Validation [WSTG-BUSL-01]
- **File:** `app/Http/Controllers/PoolingJobController.php:128,164-168`
- **Fix:**
  1. Added `min:0.01` to `total_kg` validation rule.
  2. Added server-side verification: submitted `total_kg` must match actual harvest sum within 1% tolerance. Rejects if outside range.
- **Impact:** `total_kg=0` rejected. Server-side check prevents manipulation of total weight to bypass cost share calculations.

### P4. counterProposal() — Price Bounds Bypass When price_reference Is Null [WSTG-BUSL-01]
- **File:** `app/Http/Controllers/PoolingJobController.php:553-560`
- **Fix:** Changed price bounds check from conditional (`if ($referencePrice > 0)`) to mandatory. When `price_reference <= 0`, returns error: "Cannot counter-propose. Reference price is not set."
- **Impact:** Price manipulation via null reference price eliminated. Counter-proposals require a valid reference price.

### P5. Tracking Rate Limit Bypass via /tracking/stream [WSTG-BUSL-05]
- **File:** `routes/web.php:296`
- **Fix:** Added `->middleware('throttle:12,1')` to `/tracking/stream` route, matching the same rate limit as `/driver/tracking/store`.
- **Impact:** Both GPS telemetry endpoints now rate-limited to 12 requests/minute per driver. Flooding vector closed.

### P6. Cost Ledger & Pooling Routes Lack Role-Specific Middleware [WSTG-BUSL-06]
- **File:** `routes/web.php:284-290`
- **Fix:** Wrapped cost ledger routes in `Route::middleware(['role:farmer,logistics_partner'])` and pooling accept/reject/counter routes in same middleware group.
- **Impact:** Drivers and admins can no longer reach cost ledger or pooling accept/reject/counter endpoints. Defense-in-depth enforced.

---

## Minor Fixes

### P7. markPaid() — No Payment Status State Check [WSTG-BUSL-03]
- **File:** `app/Http/Controllers/CostLedgerController.php:177-179`
- **Fix:** Added check: `if ($harvest->pivot->payment_status === 'paid') return error`. Prevents duplicate mark-paid operations and duplicate notifications.
- **Impact:** Idempotent payment marking. No duplicate notifications sent.

### P8. uploadReceipt() — No Job Status Check [WSTG-BUSL-06]
- **File:** `app/Http/Controllers/CostLedgerController.php:113-116`
- **Fix:** Added job status validation: `in_array($poolingJob->status, ['confirmed', 'in_progress', 'awaiting_confirmation'])`. Rejects receipt uploads for pending jobs.
- **Impact:** Receipt uploads restricted to active/delivered jobs only. Prevents misleading logistics partners.

### P9. No Rate Limiting on Pooling accept/reject/counter [WSTG-BUSL-05]
- **File:** `routes/web.php:288-290`
- **Fix:** Added `->middleware('throttle:30,1')` to all three pooling action routes.
- **Impact:** Rapid concurrent accept/reject/counter calls throttled to 30/minute. Race condition attack surface reduced.

### P10. Negotiation proposeTerms() Has No Round Limit [WSTG-BUSL-05]
- **File:** `app/Http/Controllers/NegotiationController.php:155-162`
- **Fix:** Added round counter by counting `[System Offer]` messages in the negotiation. Enforces max 10 rounds. Returns error when limit reached.
- **Impact:** Indefinite propose→reject loop prevented. Negotiations capped at 10 rounds.

---

## Files Modified

| File | Changes |
|------|---------|
| `app/Http/Controllers/NegotiationController.php` | P1, P10 |
| `app/Http/Controllers/CostLedgerController.php` | P2, P7, P8 |
| `app/Http/Controllers/PoolingJobController.php` | P3, P4 |
| `routes/web.php` | P5, P6, P9 |

---

## Verification Notes

- P1 (status guard): One-line fix, closes critical delivery redirection vector
- P2 (quantity reduction): Two-part fix — idempotency guard + removed harvest mutation. Pivot is now single source of truth for confirmed quantities
- P3 (total_kg): Server-side tolerance check (1%) prevents manipulation while allowing floating-point rounding
- P4 (null price bounds): Changed from conditional to mandatory check — clean security boundary
- P5-P6 (rate limits + role middleware): Route-level fixes, no controller changes needed
- P7-P9 (state checks + throttles): Simple guard clauses, no breaking changes
- P10 (round limit): Uses existing message table instead of new DB column — zero migration needed

---

**Part 1 + Part 2 complete.** Combined: 25 issues fixed across 10 files.
