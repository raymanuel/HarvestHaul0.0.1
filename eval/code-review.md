# HarvestHaul Code Review — Partial Sale / Buyer Visibility System

**Date:** 2026-07-13
**Scope:** Full implementation of partial sales, buyer visibility, negotiation lifecycle, cancellation, and pre-existing bug fixes
**Files reviewed:** 19 files (3 new, 16 modified)

---

## Executive Summary

| Severity | Count | Key Themes |
|----------|-------|------------|
| **Critical** | 2 | `lockForUpdate` outside transaction, ENUM migration conflict (fixed) |
| **High** | 5 | Wrong visibility on partial sale, missing visibility enforcement, incorrect pivot quantities, wrong status reset, non-proportional cost shares |
| **Medium** | 7 | Missing audit log, stale remaining on edit, missing notifications, eager load ordering, missing edit guard, missing visibility filter, status constant inconsistency |
| **Low** | 7 | Pattern-matching self-agreement check, no harvest availability on propose, meta refresh disruption, missing `in_progress` guard, missing `negotiating` lock for partial, stale pivot in notifications, sort order gaps |
| **Info** | 5 | Good spec fixes applied, inconsistent constant usage, rate limiting gap, latitude precision, spec vs implementation mismatch |

---

## Critical

### C1. `lockForUpdate()` Called Outside `DB::transaction()` — Race Condition

**File:** `app/Http/Controllers/NegotiationController.php:297-307`

```php
$harvest = Harvest::lockForUpdate()->find($harvest->id);  // line 297
// ... checks ...
DB::transaction(function () use (...) {                     // line 307
```

In MySQL InnoDB, `SELECT ... FOR UPDATE` without an explicit transaction runs in an auto-commit transaction. The lock is released immediately after the SELECT completes. By the time `DB::transaction()` starts on line 307, **the lock is already gone**. Two concurrent finalizations can both pass the quantity check and both write.

**Fix:** Move `lockForUpdate()` inside the transaction:
```php
DB::transaction(function () use (...) {
    $harvest = Harvest::lockForUpdate()->find($harvest->id);
    // ... checks and updates ...
});
```

---

### C2. Migration ENUM Conflict — `partially_sold` Dropped (FIXED)

**File:** `database/migrations/2026_07_13_083400_fix_harvest_status_to_proper_enum.php:11`

The "fix" migration overwrote the ENUM without `partially_sold`. Since it has a later timestamp than the partial sale migration, it would drop the value. **Fixed** — `partially_sold` added to ENUM list.

---

## High

### H1. `finalizeDeal()` Sets `visibility = 'logistics_only'` on Partial Sale — Breaks Partial Sale Flow

**File:** `app/Http/Controllers/NegotiationController.php:327-334`

```php
// Partially sold — harvest stays visible for more buyers
$harvest->update([
    'remaining_quantity_kg' => $newRemaining,
    'status'                => 'partially_sold',
    'visibility'            => 'logistics_only',  // ← WRONG
]);
```

Comment says "stays visible for more buyers" but code sets `logistics_only`, removing the harvest from the buyer crop board. Per design spec, partially-sold harvests should keep `visibility = 'buyers_only'` (independent) or `'both'` (cooperative) so new buyers can negotiate on the remainder.

**Fix:** Respect the farmer's original visibility:
```php
$isIndependent = $harvest->user?->farmerProfile?->affiliation_type === 'independent';
'visibility' => $isIndependent ? 'buyers_only' : 'both',
```

---

### H2. `scopedHarvestQuery()` Missing Visibility and Remaining Quantity Filters

**File:** `app/Http/Controllers/BuyerController.php:198-211`

The query filters by `HarvestStatus::BUYER_AVAILABLE` but does **not** filter by `visibility` or `remaining_quantity_kg > 0`. Harvests with `visibility = 'logistics_only'` and fully-sold harvests (remaining = 0) still appear to buyers.

**Fix:** Add:
```php
->whereIn('visibility', ['buyers_only', 'both'])
->where('remaining_quantity_kg', '>', 0)
```

---

### H3. `cancelDeal()` Missing `lockForUpdate()` — Race Condition on Quantity Restore

**File:** `app/Http/Controllers/NegotiationController.php:363-410`

```php
$harvest = Harvest::find($negotiation->harvest_id);  // line 363 — no lock
// ...
DB::transaction(function () use ($negotiation, $harvest) {
    // ... quantity restore happens here without lock
```

Two concurrent cancellations or a cancel + finalize running simultaneously can produce incorrect `remaining_quantity_kg` values. Both read the same stale quantity and write their own delta.

**Fix:** Add `lockForUpdate()` inside the transaction:
```php
DB::transaction(function () use ($negotiation, $harvest) {
    $harvest = Harvest::lockForUpdate()->find($harvest->id);
    // ... quantity restore ...
});
```

---

### H4. `PoolingJobController@confirm()` Stops Use Original Quantity, Not Negotiated Volume

**File:** `app/Http/Controllers/PoolingJobController.php:180-188`

```php
$stops[] = [
    'harvest_id' => $h->id,
    'quantity_kg' => (float) $h->quantity_kg,  // ← original, not negotiated
];
```

The pivot table (`pooling_job_harvests.quantity_kg`) gets the original harvest quantity instead of the negotiated volume. This feeds into `recalculateCostShares()` which uses pivot quantity for proportional cost allocation, causing incorrect cost splits on partial sales.

**Fix:** Use negotiated volume:
```php
$negotiation = $h->negotiations()->where('status', 'COMPLETED')->first();
'quantity_kg' => $negotiation ? (float) $negotiation->negotiated_volume : (float) $h->quantity_kg,
```

---

### H5. `rejectProposal()` Sets Harvest Status to `active` Without Checking Current Status

**File:** `app/Http/Controllers/PoolingJobController.php:476-477`

When a farmer rejects a pooling proposal, the harvest status is unconditionally set to `active`. If the harvest is `partially_sold` (with a completed B2B deal), this erases the partial sale state.

**Fix:**
```php
if ($harvest->status === 'assigned') {
    $harvest->status = 'active';
}
```

---

## Medium

### M1. `finalizeDeal()` Missing Audit Log

**File:** `app/Http/Controllers/NegotiationController.php:307-342`

The design spec requires `AuditLog::create()` for both `harvest_fully_sold` and `harvest_partially_sold` events. The implementation has no audit logging. For a financial transaction (B2B crop sale), this is a significant audit gap.

**Fix:** Add inside the transaction:
```php
AuditLog::create([
    'admin_id'    => $buyer->id,
    'action'      => $newRemaining <= 0 ? 'harvest_fully_sold' : 'harvest_partially_sold',
    'target_type' => 'harvest',
    'target_id'   => $harvest->id,
    'notes'       => "Buyer purchased {$negotiation->negotiated_volume}kg. Remaining: {$newRemaining}kg.",
]);
```

---

### M2. `HarvestController@update()` Doesn't Recalculate `remaining_quantity_kg` on Quantity Change

**File:** `app/Http/Controllers/HarvestController.php:270-281`

When a farmer edits `quantity_kg` for an `active` harvest, the code updates `quantity_kg` but not `remaining_quantity_kg`. Changing quantity to 500 when remaining is already 400 leaves a misleading state.

**Fix:** If the harvest is `active` with no deals:
```php
if ($harvest->status === 'active') {
    $validated['remaining_quantity_kg'] = $validated['quantity_kg'];
}
```

---

### M3. `cancelDeal()` Auto-Detaches From Pending Pooling Jobs Without Resetting Other Farmers' Acceptance

**File:** `app/Http/Controllers/NegotiationController.php:389-409`

After detaching and recalculating, remaining farmers' pivot `status` stays at `accepted`. Their previously-accepted cost shares may be wrong after the detachment. Should reset remaining farmers' pivot status to `pending`.

---

### M4. `PoolingJobController@logisticsCounter()` — Stale Pivot Data in Notifications

**File:** `app/Http/Controllers/PoolingJobController.php:713-726`

After `recalculateCostShares()`, the notification reads `$h->pivot->cost_share`. But `updateExistingPivot()` updates the DB, not the in-memory model. The notification shows the old cost share.

**Fix:** Add `$poolingJob->load('harvests');` after `recalculateCostShares()`.

---

### M5. `ResourcePoolingService@plan()` Loads All Negotiations, Not Just Completed

**File:** `app/Services/ResourcePoolingService.php:75`

```php
->with(['crop', 'cropVariety', 'farmer.farmerProfile', 'destination', 'negotiations'])
```

Loads all negotiations. Later `$harvest->negotiations->first()` assumes the first is completed. If negotiations are returned in creation order and the first is OPEN, the wrong negotiation is used.

**Fix:** Use scoped eager load:
```php
'negotiations' => fn($q) => $q->where('status', 'COMPLETED')
```

---

### M6. `HarvestController@edit()` Missing `partially_sold` in Edit Guard

**File:** `app/Http/Controllers/HarvestController.php:239`

```php
if (in_array($harvest->status, ['completed', 'cancelled', 'negotiating', 'sold', 'assigned'])) {
```

`partially_sold` is missing. A farmer can edit a partially-sold harvest's quantity, potentially setting it lower than already-sold volumes.

**Fix:** Add `'partially_sold'` to the blocked list.

---

### M7. `HarvestStatus` Constants Not Consistently Used

**File:** Multiple controllers

`HarvestStatus::LOCKED`, `BUYER_AVAILABLE`, `LOGISTICS_VISIBLE` exist but many controllers still use hardcoded string arrays (`NegotiationController:68`, `PoolingJobController:170`, `HarvestController:239`). The controller's blocked-status array also omits `in_progress` which `LOCKED` includes.

**Fix:** Replace all hardcoded arrays with `HarvestStatus` constants.

---

## Low

### L1. `start()` Doesn't Set `negotiating` for `partially_sold` Harvests

**File:** `app/Http/Controllers/NegotiationController.php:68-73`

Status is only changed from `active` to `negotiating`. For `partially_sold` harvests, the status stays `partially_sold`, allowing multiple simultaneous negotiations.

---

### L2. `agreeTerms()` Self-Agreement Check Uses Pattern Matching

**File:** `app/Http/Controllers/NegotiationController.php:235-241`

Relies on `message_text LIKE '[System Offer]%'`. Fragile if format changes or a free-text message matches the pattern.

---

### L3. `proposeTerms()` No Validation That Harvest Is Still Available

**File:** `app/Http/Controllers/NegotiationController.php:159-213`

Terms can be proposed on a harvest that is now `sold` with 0 remaining. The max volume check uses current remaining but doesn't check status.

---

### L4. `<meta http-equiv="refresh" content="30">` Disrupts User Interaction

**File:** `resources/views/buyer/crop-board.blade.php:2`

Auto-refresh every 30 seconds interrupts in-progress form submissions and resets scroll position. Consider AJAX polling or a manual refresh button.

---

### L5. `proposeTerms()` — `maxVolume` Doesn't Account for Other OPEN Negotiations

**File:** `app/Http/Controllers/NegotiationController.php:167`

Two buyers can each propose to buy 500kg of a 600kg remaining lot. Both pass validation but total committed exceeds remaining.

---

### L6. `AdminController@harvests()` Sort Order Missing `partially_sold`

**File:** `app/Http/Controllers/AdminController.php:164`

`partially_sold` not in `FIELD()` sort. These harvests appear in undefined order.

---

### L7. No CSRF Rate Limiting on `finalizeDeal` Route

**File:** `routes/web.php:275`

Unlike `propose` and `agree` (both `throttle:10,1`), `finalize` has no throttle middleware.

---

## Info

### I1. Crop `$fillable` Fix Applied ✅

`app/Models/Crop.php:17` — `baseline_price_per_kg` correctly added to `$fillable`.

### I2. `Harvest@boot()` Fallback Applied ✅

`app/Models/Harvest.php:75-87` — `remaining_quantity_kg` and `visibility` defaulted on creation.

### I3. Self-Agreement Guard Applied ✅

`app/Http/Controllers/NegotiationController.php:234-242` — Checks last `[System Offer]` sender.

### I4. Price Bounds on `logisticsCounter()` Applied ✅

`app/Http/Controllers/PoolingJobController.php` — ±75% bounds enforced.

### I5. `CheckRole` Middleware Fixed ✅

`app/Http/Middleware/CheckRole.php:11-24` — Now properly enforces role checks (no longer a no-op).

---

## Recommended Fix Priority

| Priority | Finding | Effort |
|----------|---------|--------|
| **P0** | C1 — `lockForUpdate` outside transaction | 5 min |
| **P0** | H1 — Wrong visibility on partial sale | 10 min |
| **P0** | H2 — Missing visibility filters | 10 min |
| **P1** | H3 — Missing lock in cancelDeal | 5 min |
| **P1** | H4 — Wrong pivot quantity in stops | 10 min |
| **P1** | H5 — Wrong status reset in rejectProposal | 5 min |
| **P1** | M6 — Missing edit guard for partially_sold | 2 min |
| **P2** | M1 — Missing audit log | 10 min |
| **P2** | M2 — Stale remaining on edit | 5 min |
| **P2** | M5 — Wrong eager load ordering | 2 min |
| **P2** | M7 — Inconsistent constant usage | 15 min |
| **P3** | All Low findings | varies |
