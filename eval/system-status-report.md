# HarvestHaul System Status Report

**Date:** 2026-07-13
**Version:** 0.0.1 (Partial Sale System)
**Scope:** Implementation status, known issues, risks, and test coverage

---

## Implementation Status

### Partial Sale / Buyer Visibility System — COMPLETE ✅

All 16 implementation tasks from `eval/new-system-plan.md` have been completed:

| # | Task | Status |
|---|------|--------|
| 1 | Migration (harvests + negotiations columns) | ✅ |
| 2 | `HarvestStatus` constants class | ✅ |
| 3 | Harvest model (fillable, casts, boot, scopes, accessors) | ✅ |
| 4 | Negotiation model (fillable, destination) | ✅ |
| 5 | Crop model (`baseline_price_per_kg` in fillable) | ✅ |
| 6 | `NegotiationController` (start, proposeTerms, agreeTerms, finalizeDeal, cancelDeal) | ✅ |
| 7 | `BuyerController` (scopedHarvestQuery with visibility) | ✅ |
| 8 | `PoolingJobController` (negotiated volume, price bounds, verification) | ✅ |
| 9 | `HarvestController` (visibility, remaining_quantity_kg, edit/destroy guards) | ✅ |
| 10 | `DashboardController` (partially_sold in queries) | ✅ |
| 11 | `AdminController` (partially_sold in toggleStatus, updateUser) | ✅ |
| 12 | `RouteOptimizationController` (LOGISTICS_VISIBLE filter) | ✅ |
| 13 | `ResourcePoolingService` (negotiated volume, guard) | ✅ |
| 14 | Views (harvests/index, farmers/farmer-view, buyer/crop-board) | ✅ |
| 15 | Routes (cancel route) | ✅ |
| 16 | Pre-existing bug fixes (crop fillable, self-agreement, price bounds, stale pivot) | ✅ |

### Pre-Existing Bug Fixes — COMPLETE ✅

| Bug | Status |
|-----|--------|
| Crop `$fillable` missing `baseline_price_per_kg` | ✅ Fixed |
| `agreeTerms()` allows self-agreement | ✅ Fixed |
| `logisticsCounter()` missing price bounds | ✅ Fixed |
| Stale pivot data in `logisticsCounter()` notifications | ✅ Fixed |
| Admin `toggleStatus()` doesn't handle `partially_sold` | ✅ Fixed |

---

## Known Issues — P0 (Must Fix Before Deploy)

| # | Issue | File | Risk |
|---|-------|------|------|
| **P0-1** | `lockForUpdate()` outside `DB::transaction()` — race condition allows double-sell | `NegotiationController.php:297-307` | Two buyers can finalize the same lot simultaneously |
| **P0-2** | `finalizeDeal()` sets `visibility = 'logistics_only'` on partial sale — breaks partial sale flow | `NegotiationController.php:332` | Partially-sold harvests disappear from crop board, no further buyers |
| **P0-3** | `scopedHarvestQuery()` missing `visibility` and `remaining_quantity_kg` filters | `BuyerController.php:198-211` | Buyers see fully-sold harvests and logistics-only harvests |

---

## Known Issues — P1 (Should Fix Before Deploy)

| # | Issue | File | Risk |
|---|-------|------|------|
| **P1-1** | `cancelDeal()` missing `lockForUpdate()` — race condition on quantity restore | `NegotiationController.php:363-410` | Concurrent cancels produce wrong remaining quantity |
| **P1-2** | Stops use original quantity instead of negotiated volume | `PoolingJobController.php:185` | Pivot table overstates loaded quantity, wrong cost shares |
| **P1-3** | `rejectProposal()` unconditionally sets status to `active` | `PoolingJobController.php:476-477` | Partially-sold harvest loses its status |
| **P1-4** | `HarvestController@edit()` missing `partially_sold` in guard | `HarvestController.php:239` | Farmer can edit quantity after partial sale |
| **P1-5** | `finalizeDeal()` missing audit log | `NegotiationController.php:307-342` | No audit trail for B2B financial transactions |

---

## Known Issues — P2 (Backlog)

| # | Issue | File |
|---|-------|------|
| P2-1 | `HarvestController@update()` doesn't recalculate `remaining_quantity_kg` | `HarvestController.php:270` |
| P2-2 | `ResourcePoolingService` loads all negotiations, not just completed | `ResourcePoolingService.php:75` |
| P2-3 | `HarvestStatus` constants not consistently used across controllers | Multiple |
| P2-4 | `start()` doesn't set `negotiating` for `partially_sold` harvests | `NegotiationController.php:68` |
| P2-5 | `cancelDeal()` doesn't reset other farmers' pivot status | `NegotiationController.php:389` |
| P2-6 | `logisticsCounter()` stale pivot in notifications | `PoolingJobController.php:713` |

---

## Architecture Summary

### Database Schema (New Columns)

| Table | Column | Type | Purpose |
|-------|--------|------|---------|
| `harvests` | `visibility` | ENUM(`buyers_only`,`logistics_only`,`both`) | Controls who sees the post |
| `harvests` | `remaining_quantity_kg` | DECIMAL(10,2) nullable | Available qty per lot |
| `negotiations` | `destination_address` | VARCHAR(500) nullable | Deal-specific drop-off |
| `negotiations` | `destination_latitude` | DECIMAL(10,8) nullable | Drop-off lat |
| `negotiations` | `destination_longitude` | DECIMAL(11,8) nullable | Drop-off lng |

### Status State Machine

```
pending → active → negotiating → [AGREED] → COMPLETED → (sold | partially_sold)
                   negotiating → CANCELLED → active (or partially_sold stays)
partially_sold → negotiating → ... (same flow)
sold → logistics_only (visible to logistics partners)
```

### Key Design Decisions

1. **`quantity_kg` is immutable** — never modified after creation
2. **`remaining_quantity_kg` decreases with each deal** — single source of truth
3. **Destination stored per negotiation** — each deal keeps its own drop-off coordinates
4. **`partially_sold` keeps `buyers_only` visibility** — new buyers can negotiate on remainder
5. **`cancelDeal()` auto-detaches from `pending` pooling jobs** — blocks `confirmed`/`in_progress`

---

## Test Coverage

### Automated Tests

**No automated tests exist.** The codebase has no `tests/` directory content beyond Laravel's default `TestCase.php`. All verification has been manual.

### Manual Test Scenarios (Recommended)

| Scenario | Priority | Status |
|----------|----------|--------|
| Independent farmer posts → buyer sees on crop board | P0 | Not tested |
| Buyer starts negotiation → status changes to `negotiating` | P0 | Not tested |
| Buyer proposes terms → farmer sees notification | P0 | Not tested |
| Both agree → buyer finalizes with destination | P0 | Not tested |
| Partial sale: remaining qty decremented, crop board updated | P0 | Not tested |
| Second buyer negotiates on partially-sold lot | P0 | Not tested |
| Full sale: harvest moves to logistics map | P0 | Not tested |
| Cancel deal: quantity restored, status reverted | P1 | Not tested |
| Concurrent finalization: race condition guard | P1 | Not tested |
| Cooperative farmer: visibility = `both` | P1 | Not tested |
| Logistics routing: uses negotiated volume | P1 | Not tested |
| Admin archive: partially-sold harvests handled | P2 | Not tested |

---

## Migration Risk Assessment

### ENUM Migration Conflict — FIXED ✅

Migration `2026_07_13_083400` was overwriting the ENUM without `partially_sold`. Fixed by adding `partially_sold` to the ENUM list.

### Data Integrity

- **Backward compatible** — Migration sets defaults for all existing harvests
- **`remaining_quantity_kg` nullable** — `boot()` fallback handles null values
- **`visibility` defaults to `both`** — `boot()` fallback handles null values

---

## Recommendation

**Do not deploy until P0 issues are resolved.** The three P0 issues (lockForUpdate placement, wrong visibility on partial sale, missing visibility filters) fundamentally break the partial sale feature and create race conditions that can cause data corruption.

Estimated fix effort for all P0 + P1 issues: **~2 hours**.
