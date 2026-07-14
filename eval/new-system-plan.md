# New System Plan: Independent Farmer B2B-First Visibility + Partial Sales

## Business Requirement

After farmer registration, if the farmer is independent (not under any cooperative), posts on the crop board should only be visible to commercial buyers. When a buyer and farmer negotiate and agree on price and volume, the post then becomes visible to commercial or independent logistics companies. If the buyer wants less than the full volume (e.g., 300kg of 500kg mango), the haul request must only be for the negotiated volume. The harvest post must update immediately to reflect the remaining quantity, since multiple buyers may negotiate on the same lot.

## Design Principles

1. `quantity_kg` is immutable — never modified after creation
2. `remaining_quantity_kg` decreases with each deal — single source of truth for availability
3. Destination stored per negotiation — each deal keeps its own drop-off coordinates
4. Explicit `visibility` column — one field to query, no condition chains
5. `partially_sold` added to status enum — clear state, no semantic overload

## Database Changes

### `harvests` table — 2 new columns

| Column | Type | Default | Purpose |
|--------|------|---------|---------|
| `visibility` | ENUM(`buyers_only`,`logistics_only`,`both`) | `both` | Controls who sees the post |
| `remaining_quantity_kg` | DECIMAL(10,2) | `quantity_kg` | Available qty (decreases per deal) |

### `negotiations` table — 3 new columns

| Column | Type | Purpose |
|--------|------|---------|
| `destination_address` | VARCHAR(500) nullable | This deal's drop-off address |
| `destination_latitude` | DECIMAL(8) nullable | This deal's drop-off lat |
| `destination_longitude` | DECIMAL(8) nullable | This deal's drop-off lng |

### Status enum update

```
pending | active | negotiating | partially_sold | sold | assigned | in_progress | completed | cancelled
```

### Migration data transform

```php
// Existing harvests: set remaining = original
DB::table('harvests')->whereNull('remaining_quantity_kg')->update([
    'remaining_quantity_kg' => DB::raw('quantity_kg'),
]);

// Set visibility based on farmer affiliation
DB::table('harvests')->whereNull('visibility')->update([
    'visibility' => DB::raw("CASE
        WHEN (SELECT affiliation_type FROM farmer_profiles WHERE farmer_profiles.user_id = harvests.user_id) = 'independent'
        THEN 'buyers_only'
        ELSE 'both'
    END"),
]);

// Update status enum to include partially_sold
DB::statement("ALTER TABLE harvests MODIFY COLUMN status ENUM('pending','active','negotiating','partially_sold','sold','assigned','in_progress','completed','cancelled') DEFAULT 'active'");
```

---

## File-by-File Changes

### 1. Migration

**New file**: `database/migrations/xxxx_add_partial_sale_support.php`

```php
public function up(): void
{
    Schema::table('harvests', function (Blueprint $table) {
        $table->enum('visibility', ['buyers_only', 'logistics_only', 'both'])->default('both')->after('status');
        $table->decimal('remaining_quantity_kg', 10, 2)->nullable()->after('quantity_kg');
    });

    Schema::table('negotiations', function (Blueprint $table) {
        $table->string('destination_address', 500)->nullable()->after('status');
        $table->decimal('destination_latitude', 8, 5)->nullable()->after('destination_address');
        $table->decimal('destination_longitude', 8, 5)->nullable()->after('destination_latitude');
    });

    // Existing harvests: set defaults
    DB::table('harvests')->whereNull('remaining_quantity_kg')->update([
        'remaining_quantity_kg' => DB::raw('quantity_kg'),
    ]);

    DB::table('harvests')->whereNull('visibility')->update([
        'visibility' => DB::raw("CASE
            WHEN (SELECT affiliation_type FROM farmer_profiles WHERE farmer_profiles.user_id = harvests.user_id) = 'independent'
            THEN 'buyers_only'
            ELSE 'both'
        END"),
    ]);

    DB::statement("ALTER TABLE harvests MODIFY COLUMN status ENUM('pending','active','negotiating','partially_sold','sold','assigned','in_progress','completed','cancelled') DEFAULT 'active'");
}
```

---

### 2. Harvest Model

**File**: `app/Models/Harvest.php`

```php
protected $fillable = [
    // ... existing fields ...
    'visibility',
    'remaining_quantity_kg',
];

protected $casts = [
    // ... existing casts ...
    'remaining_quantity_kg' => 'decimal:2',
];

// ─── SCOPES ───

/** Harvests visible on the B2B crop board to buyers. */
public function scopeVisibleToBuyers($query)
{
    return $query->whereIn('visibility', ['buyers_only', 'both'])
                 ->where('remaining_quantity_kg', '>', 0);
}

/** Harvests visible on the logistics routing map. */
public function scopeVisibleToLogistics($query)
{
    return $query->whereIn('visibility', ['logistics_only', 'both'])
                 ->whereIn('status', ['sold', 'partially_sold']);
}

// ─── ACCESSORS ───

/** Volume already committed to completed deals. */
public function getCommittedVolumeAttribute(): float
{
    return (float) $this->negotiations()
        ->where('status', 'COMPLETED')
        ->sum('negotiated_volume');
}

/** "300/500 kg sold" for UI display. */
public function getSaleProgressAttribute(): ?string
{
    if ($this->status !== 'partially_sold') return null;
    $original = (float) $this->quantity_kg;
    $remaining = (float) $this->remaining_quantity_kg;
    $sold = $original - $remaining;
    return number_format($sold) . '/' . number_format($original) . ' kg sold';
}

/** Get the completed negotiation for a specific buyer. */
public function getCompletedDealFor(int $buyerId): ?Negotiation
{
    return $this->negotiations()
        ->where('buyer_id', $buyerId)
        ->where('status', 'COMPLETED')
        ->first();
}
```

---

### 3. Negotiation Model

**File**: `app/Models/Negotiation.php`

```php
protected $fillable = [
    // ... existing fields ...
    'destination_address',
    'destination_latitude',
    'destination_longitude',
];

/** Get this deal's drop-off destination (not the harvest's). */
public function getDeliveryDestinationAttribute(): ?array
{
    if ($this->destination_address && $this->destination_latitude && $this->destination_longitude) {
        return [
            'address' => $this->destination_address,
            'latitude' => $this->destination_latitude,
            'longitude' => $this->destination_longitude,
        ];
    }
    // Fallback to harvest's original destination
    return $this->harvest->destination_coordinates;
}
```

---

### 4. HarvestController@store

**File**: `app/Http/Controllers/HarvestController.php` (line ~153)

After creating the harvest, set visibility:

```php
$isIndependent = Auth::user()->farmerProfile?->affiliation_type === 'independent';

$harvest->update([
    'visibility'            => $isIndependent ? 'buyers_only' : 'both',
    'remaining_quantity_kg' => $validated['quantity_kg'],
]);
```

Update success message (line ~192):

```php
$msg = $isIndependent
    ? 'Harvest post published. Your crop is now visible to commercial buyers on the B2B crop board.'
    : 'Harvest post published. You are now visible on the logistics map.';
```

---

### 5. NegotiationController@start

**File**: `app/Http/Controllers/NegotiationController.php` (line ~39-66)

```php
$harvest = Harvest::findOrFail($request->harvest_id);

// Check availability: active status + remaining qty
if ($harvest->status === 'negotiating') {
    return back()->with('error', 'This lot is currently under negotiation with another buyer.');
}

if ($harvest->status === 'partially_sold' && $harvest->remaining_quantity_kg <= 0) {
    return back()->with('error', 'This harvest lot is fully sold.');
}

if (!in_array($harvest->status, ['active', 'partially_sold'])) {
    return back()->with('error', 'This harvest lot is no longer available.');
}

if ((float) $harvest->remaining_quantity_kg <= 0) {
    return back()->with('error', 'No remaining quantity available on this lot.');
}

// ... existing duplicate negotiation check ...

// Lock for negotiation
$harvest->update(['status' => 'negotiating']);

$negotiation = Negotiation::create([
    'buyer_id'          => $buyer->id,
    'farmer_id'         => $harvest->user_id,
    'harvest_id'        => $harvest->id,
    'negotiated_price'  => null,
    'negotiated_volume' => $harvest->remaining_quantity_kg,  // uses remaining, not original
    'status'            => 'OPEN',
]);
```

---

### 6. NegotiationController@proposeTerms

**File**: `app/Http/Controllers/NegotiationController.php` (line ~152)

```php
$maxVolume = $negotiation->harvest->remaining_quantity_kg;  // was quantity_kg
$request->validate([
    'negotiated_price'  => 'required|numeric|min:0.01|max:999999.99',
    'negotiated_volume' => "required|numeric|min:0.01|max:{$maxVolume}",
]);
```

---

### 7. NegotiationController@finalizeDeal — Core Change

**File**: `app/Http/Controllers/NegotiationController.php` (line ~241-291)

```php
public function finalizeDeal(Request $request, Negotiation $negotiation)
{
    $buyer = Auth::user();

    if ($negotiation->buyer_id !== $buyer->id) {
        abort(403, 'Only the buyer can finalize this deal.');
    }
    if ($negotiation->status !== 'AGREED') {
        return back()->with('error', 'Both parties must agree to terms first.');
    }
    if ($buyer->status !== 'active') {
        return back()->with('error', 'Your account is not active.');
    }

    $request->validate([
        'destination_address'   => 'required|string|max:500',
        'destination_latitude'  => 'required|numeric|between:-90,90',
        'destination_longitude' => 'required|numeric|between:-180,180',
    ]);

    $harvest = $negotiation->harvest;

    return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $negotiation, $harvest, $buyer) {
        // Pessimistic lock — prevents double-sell race condition
        $locked = Harvest::where('id', $harvest->id)->lockForUpdate()->first();

        // Re-check remaining quantity under lock
        if ((float) $negotiation->negotiated_volume > (float) $locked->remaining_quantity_kg) {
            return back()->with('error', 'Insufficient remaining quantity. Another deal may have been finalized.');
        }

        $newRemaining = (float) $locked->remaining_quantity_kg - (float) $negotiation->negotiated_volume;

        // Determine new harvest status
        if ($newRemaining <= 0) {
            $newStatus = 'sold';
            $newVisibility = 'logistics_only';
        } else {
            $newStatus = 'partially_sold';
            $newVisibility = 'buyers_only'; // stays visible to buyers
        }

        $locked->update([
            'remaining_quantity_kg' => $newRemaining,
            'status'                => $newStatus,
            'visibility'            => $newVisibility,
        ]);

        // Store destination ON THIS NEGOTIATION (not harvest)
        $negotiation->update([
            'status'                => 'COMPLETED',
            'last_activity_at'      => now(),
            'destination_address'   => $request->destination_address,
            'destination_latitude'  => $request->destination_latitude,
            'destination_longitude' => $request->destination_longitude,
        ]);

        NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $buyer->id,
            'message_text'   => "[System Message] Deal finalized! {$negotiation->negotiated_volume}kg at ₱{$negotiation->negotiated_price}/kg. Drop-off: {$request->destination_address}",
        ]);

        \App\Models\AuditLog::create([
            'admin_id'    => $buyer->id,
            'action'      => $newStatus === 'sold' ? 'harvest_fully_sold' : 'harvest_partially_sold',
            'target_type' => 'harvest',
            'target_id'   => $locked->id,
            'notes'       => "Buyer {$buyer->name} purchased {$negotiation->negotiated_volume}kg. Remaining: {$newRemaining}kg.",
        ]);

        $msg = $newStatus === 'sold'
            ? 'B2B deal closed! Harvest fully sold. Now visible to logistics partners.'
            : "B2B deal closed! {$negotiation->negotiated_volume}kg sold. {$newRemaining}kg still available on the crop board.";

        return redirect()->route('buyer.negotiations')->with('success', $msg);
    });
}
```

---

### 8. NegotiationController@cancelDeal — New Method

**File**: `app/Http/Controllers/NegotiationController.php` (new method)

```php
public function cancelDeal(Negotiation $negotiation)
{
    $user = Auth::user();

    if ($negotiation->buyer_id !== $user->id && $negotiation->farmer_id !== $user->id) {
        abort(403);
    }

    if ($negotiation->status !== 'COMPLETED') {
        return back()->with('error', 'Only completed deals can be cancelled.');
    }

    // Block if assigned to active pooling job
    $harvest = $negotiation->harvest;
    if ($harvest->poolingJobs()->whereIn('status', ['pending', 'confirmed', 'in_progress'])->exists()) {
        return back()->with('error', 'Cannot cancel — harvest is assigned to an active logistics route.');
    }

    return \Illuminate\Support\Facades\DB::transaction(function () use ($negotiation, $harvest, $user) {
        $locked = Harvest::where('id', $harvest->id)->lockForUpdate()->first();

        $restoredQty = (float) $locked->remaining_quantity_kg + (float) $negotiation->negotiated_volume;

        if ($restoredQty >= (float) $locked->quantity_kg) {
            // Fully restored — back to original state
            $isIndependent = $user->farmerProfile?->affiliation_type === 'independent';
            $locked->update([
                'remaining_quantity_kg' => $restoredQty,
                'status'                => 'active',
                'visibility'            => $isIndependent ? 'buyers_only' : 'both',
            ]);
        } else {
            // Partial restore — still has other deals
            $locked->update([
                'remaining_quantity_kg' => $restoredQty,
                'status'                => 'partially_sold',
                'visibility'            => 'buyers_only',
            ]);
        }

        $negotiation->update([
            'status'               => 'CANCELLED',
            'last_activity_at'     => now(),
            'destination_address'  => null,
            'destination_latitude' => null,
            'destination_longitude'=> null,
        ]);

        NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $user->id,
            'message_text'   => "[System Message] Deal cancelled. {$negotiation->negotiated_volume}kg returned to available stock.",
        ]);

        \App\Models\AuditLog::create([
            'admin_id'    => $user->id,
            'action'      => 'deal_cancelled',
            'target_type' => 'harvest',
            'target_id'   => $locked->id,
            'notes'       => "Deal cancelled by {$user->name}. {$negotiation->negotiated_volume}kg restored. Remaining: {$restoredQty}kg.",
        ]);

        return back()->with('success', 'Deal cancelled. Harvest quantity restored.');
    });
}
```

---

### 9. BuyerController@scopedHarvestQuery

**File**: `app/Http/Controllers/BuyerController.php` (line ~186-211)

```php
private function scopedHarvestQuery()
{
    $user = Auth::user();

    $cooperativeId = null;
    if ($user->role === 'buyer' && $user->affiliation_type === 'cooperative') {
        $cooperativeId = $user->cooperative_id;
    } elseif ($user->role === 'logistics_partner' && $user->logisticsProfile?->isCooperative()) {
        $cooperativeId = $user->logisticsProfile->id;
    }

    if ($cooperativeId) {
        // Cooperative buyers: see their coop's harvests (unchanged)
        return Harvest::where('status', 'active')
            ->whereHas('farmer.farmerProfile', function ($q) use ($cooperativeId) {
                $q->where('is_verified', true)
                  ->where('affiliation_type', 'cooperative')
                  ->where('cooperative_id', $cooperativeId);
            });
    }

    // Independent buyers: buyers-only or both visibility, has remaining qty
    return Harvest::whereIn('status', ['active', 'partially_sold'])
        ->whereIn('visibility', ['buyers_only', 'both'])
        ->where('remaining_quantity_kg', '>', 0)
        ->whereHas('farmer.farmerProfile', function ($q) {
            $q->where('is_verified', true)
              ->where('affiliation_type', 'independent');
        });
}
```

---

### 10. RouteOptimizationController@index

**File**: `app/Http/Controllers/RouteOptimizationController.php` (line ~31-61)

```php
$farmers = User::where('role', 'farmer')
    ->whereHas('farmerProfile', function ($query) use ($logisticsProfile) {
        $query->whereNotNull('latitude')
              ->whereNotNull('longitude')
              ->where('is_verified', true);

        if ($logisticsProfile->logistics_type === 'cooperative') {
            $query->where('affiliation_type', 'cooperative')
                  ->where('cooperative_id', $logisticsProfile->id);
        } elseif ($logisticsProfile->logistics_type === 'company') {
            $query->where('affiliation_type', 'independent')
                  ->whereIn('visibility', ['logistics_only', 'both']);
        }
    })
    ->whereHas('harvests', function ($query) {
        $query->whereIn('status', ['sold', 'partially_sold'])
              ->whereIn('visibility', ['logistics_only', 'both']);
    })
    ->with([
        'farmerProfile',
        'harvests' => fn($query) => $query->whereIn('status', ['sold', 'partially_sold'])
                                           ->whereIn('visibility', ['logistics_only', 'both'])
                                           ->with(['crop', 'cropVariety', 'destination', 'negotiations' => fn($q) => $q->where('status', 'COMPLETED')]),
    ])
    ->get();
```

---

### 11. ResourcePoolingService

**File**: `app/Services/ResourcePoolingService.php`

**In `plan()` (line 70-75)** — Load negotiations with harvests:

```php
$harvests = Harvest::whereIn('id', $nearbyHarvestIds)
    ->whereIn('status', ['sold', 'partially_sold'])
    ->whereNotNull('latitude')
    ->whereNotNull('longitude')
    ->with(['crop', 'cropVariety', 'farmer.farmerProfile', 'destination',
            'negotiations' => fn($q) => $q->where('status', 'COMPLETED')])
    ->get();
```

**In knapsack loop (line 177)** — Use negotiated volume:

```php
$qty = (float) ($harvest->negotiations->first()->negotiated_volume ?? $harvest->quantity_kg);
```

**In cost allocation loop (line 177)** — Same:

```php
$qty = (float) ($harvest->negotiations->first()->negotiated_volume ?? $harvest->quantity_kg);
```

**In `confirm()` (line 350-357)** — Pivot uses negotiated volume:

```php
$negotiationQty = $harvest->negotiations()->where('status', 'COMPLETED')->first()?->negotiated_volume ?? $harvest->quantity_kg;
$job->harvests()->attach($stop['harvest_id'], [
    'pickup_order' => $stop['pickup_order'],
    'quantity_kg'  => $negotiationQty,
    'status'       => 'pending',
]);
```

---

### 12. PoolingJobController@confirm

**File**: `app/Http/Controllers/PoolingJobController.php` (line ~159-169)

Update `actualHarvestSum`:

```php
$actualHarvestSum = $harvests->sum(function ($h) {
    return $h->negotiations()->where('status', 'COMPLETED')->first()?->negotiated_volume ?? $h->quantity_kg;
});
```

---

### 13. DashboardController

**File**: `app/Http/Controllers/DashboardController.php` (line ~80-81)

```php
'activeHarvests' => $user->harvests()
    ->whereIn('status', ['active', 'partially_sold'])
    ->with(['crop', 'cropVariety', 'destination'])
    ->latest()->take(5)->get(),
'activeHarvestsCount' => $user->harvests()
    ->whereIn('status', ['active', 'partially_sold'])
    ->count(),
```

---

### 14. HarvestController@index

**File**: `app/Http/Controllers/HarvestController.php` (line ~42-53)

```php
$harvests = Auth::user()
    ->harvests()
    ->whereIn('status', ['active', 'partially_sold', 'negotiating'])
    ->with(['crop', 'cropVariety'])
    ->latest()
    ->get();
```

---

### 15. HarvestController@edit Guard

**File**: `app/Http/Controllers/HarvestController.php` (line ~236)

```php
if (in_array($harvest->status, ['completed', 'cancelled', 'negotiating', 'sold', 'assigned'])) {
    return back()->with('error', 'This post can no longer be modified.');
}
if ($harvest->status === 'partially_sold' && (float) $harvest->remaining_quantity_kg <= 0) {
    return back()->with('error', 'This post is fully sold and cannot be modified.');
}
```

---

### 16. HarvestController@destroy Guard

**File**: `app/Http/Controllers/HarvestController.php` (line ~292-305)

Add: block delete if `partially_sold` with active negotiations.

---

### 17. View Updates

**`resources/views/harvests/index.blade.php`** — Add remaining qty column and sale progress:

```php
<td class="px-6 py-4 text-slate-600 dark:text-slate-350 font-semibold">
    {{ number_format($harvest->quantity_kg, 2) }} kg
    @if($harvest->status === 'partially_sold')
        <div class="text-[10px] text-[#D4A520] font-bold mt-0.5">
            {{ $harvest->sale_progress }}
        </div>
    @endif
</td>
```

**`resources/views/farmers/farmer-view.blade.php`** — Same pattern in Active Posts table.

**`resources/views/buyer/crop-board.blade.php`** — Add badges:

```php
@if($harvest->status === 'negotiating')
    <span class="text-[9px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded">Under Negotiation</span>
@endif
@if($harvest->status === 'partially_sold')
    <span class="text-[9px] font-bold text-[#D4A520] bg-[#D4A520]/10 px-2 py-0.5 rounded">{{ $harvest->sale_progress }}</span>
@endif
```

---

## Complete Flow Summary

```
Independent Farmer Posts 500kg Mango
    |
    v
quantity_kg=500 (immutable), remaining=500, visibility=buyers_only, status=active
    |
    v
Crop Board (Buyers Only) -- "500 kg available"
    |
    +-- Buyer A starts negotiation -> status=negotiating
    |   Crop board: "Under Negotiation" badge
    |
    +-- Buyer A finalizes -> 300kg
    |   remaining = 500 - 300 = 200
    |   status = partially_sold
    |   visibility = buyers_only (stays)
    |   destination stored on negotiation A
    |
    +-- Crop Board: "200 kg available" + "300/500 kg sold" badge
    |
    +-- Buyer B negotiates -> 200kg
    |   status = negotiating
    |
    +-- Buyer B finalizes -> 200kg
    |   remaining = 0
    |   status = sold
    |   visibility = logistics_only
    |   destination stored on negotiation B
    |
    +-- Logistics routing map:
        Harvest visible (status=sold, visibility=logistics_only)
        Buyer A: 300kg -> Destination A
        Buyer B: 200kg -> Destination B
        Pooling service reads negotiated_volume from each negotiation
```

---

## Pros

1. **Immutable `quantity_kg`** — Zero risk of breaking existing consumers. Pooling service, cost ledger, driver views all safe without changes.

2. **Destination per negotiation** — Clean separation. Each deal keeps its own drop-off. No data loss. Logistics routes each haul correctly.

3. **Rollback flow exists** — `cancelDeal()` handles buyer flaking after finalization. Atomic with row lock. Audit logged.

4. **Harvest stays active when partially sold** — New buyers can immediately negotiate on the remainder. No dead state.

5. **Crop board UX is honest** — "Under Negotiation" badge and "300/500 kg sold" progress indicator. Buyers know exactly what's available.

6. **Audit trail complete** — `harvest_partially_sold`, `harvest_fully_sold`, `deal_cancelled` events all captured.

7. **Race condition fully guarded** — `lockForUpdate()` + re-check inside transaction. Two buyers finalizing simultaneously: one succeeds, other gets clear error.

8. **Minimal schema change** — 2 new columns on harvests, 3 on negotiations. No new tables. No removed columns.

9. **Cooperative farmers unaffected** — All new logic gated on `affiliation_type === 'independent'`. Cooperative flow identical.

10. **Backward compatible** — Migration sets defaults for all existing harvests. Zero breakage.

---

## Cons

1. **`partially_sold` enum requires codebase sweep** — Every `status === 'active'` query that should include partially-sold harvests needs updating to `whereIn('status', ['active', 'partially_sold'])`. Approximately 8-10 call sites across controllers and views.

2. **Destination field migration on downstream views** — Driver views, cost ledger, and pooling job views currently read `$harvest->destination_address`. They need to read from the negotiation's `delivery_destination` accessor instead. Approximately 3-4 views affected.

3. **`cancelDeal()` can't undo mid-transit** — If harvest is already assigned to a pooling job, cancel is blocked. Farmer must contact logistics manually. No automated dispute resolution.

4. **No real-time crop board refresh** — After `finalizeDeal()`, the approximately 100ms window between DB commit and page redirect means another buyer might briefly see stale quantity. Needs WebSocket broadcast for true real-time, which is out of scope.

5. **`remaining_quantity_kg` nullable edge case** — Seeders, admin creates, or any code path bypassing `HarvestController@store` will not set it. Queries filtering `where('remaining_quantity_kg', '>', 0)` silently exclude these rows. Needs a model `boot()` fallback or seeder fix.

6. **Negotiation table now stores delivery data** — `negotiations` table was a chat/pricing record, now it is also a delivery record. Schema is mixing concerns. If future requirements add delivery tracking, the table grows awkwardly.

7. **Pooling service reads from two models** — Knapsack weight comes from `negotiations.negotiated_volume` instead of a single harvest field. If a harvest somehow has no completed negotiation (edge: cancelled deal, new deal pending), the fallback to `quantity_kg` uses the full original amount, potentially overloading the truck.

---

## Files Modified (Summary)

| File | Change |
|------|--------|
| `database/migrations/xxxx_add_partial_sale_support.php` | New migration |
| `app/Models/Harvest.php` | Add fillable, casts, scopes, accessors |
| `app/Models/Negotiation.php` | Add fillable, delivery_destination accessor |
| `app/Http/Controllers/HarvestController.php` | Set visibility on store, update edit/destroy guards, update index query |
| `app/Http/Controllers/NegotiationController.php` | Update start/proposeTerms/finalizeDeal, add cancelDeal |
| `app/Http/Controllers/BuyerController.php` | Add visibility filter to scopedHarvestQuery |
| `app/Http/Controllers/RouteOptimizationController.php` | Add visibility filter |
| `app/Http/Controllers/PoolingJobController.php` | Use negotiated volume for validation |
| `app/Http/Controllers/DashboardController.php` | Include partially_sold in farmer queries |
| `app/Services/ResourcePoolingService.php` | Read negotiated volume from negotiation |
| `resources/views/harvests/index.blade.php` | Add remaining qty + sale progress display |
| `resources/views/farmers/farmer-view.blade.php` | Add remaining qty + sale progress display |
| `resources/views/buyer/crop-board.blade.php` | Add negotiation/partial-sale badges |

---

## Cons Resolutions

### Con 1: `partially_sold` enum requires codebase sweep

**Problem**: Every `status === 'active'` query needs updating to include `partially_sold`.

**Fix**: Add a shared scope on the Harvest model that all consumers use.

```php
// app/Models/Harvest.php

/** Harvests that are available for buyer negotiation (active or partially sold). */
public function scopeAvailableForBuyers($query)
{
    return $query->whereIn('status', ['active', 'partially_sold']);
}
```

Then every controller/view that currently does `where('status', 'active')` for buyer-facing queries replaces it with `availableForBuyers()`:

```php
// Before (8-10 scattered call sites)
Harvest::where('status', 'active')...

// After (single scope, one place to maintain)
Harvest::availableForBuyers()...
```

**Impact**: One scope definition. All call sites updated to use it. Future status additions only need updating in one place.

---

### Con 2: Destination field migration on downstream views

**Problem**: Driver views, cost ledger, pooling job views read `$harvest->destination_address`. They need the deal-specific destination.

**Fix**: Add a helper method on Harvest that resolves the correct destination from the completed negotiation.

```php
// app/Models/Harvest.php

/**
 * Get the resolved delivery destination for this harvest.
 * Checks completed negotiations first (deal-specific), falls back to harvest default.
 * Used by driver views, cost ledger, and pooling job views.
 */
public function getResolvedDestinationAddressAttribute(): ?string
{
    $completedDeal = $this->negotiations()->where('status', 'COMPLETED')->first();
    return $completedDeal->destination_address ?? $this->destination_address;
}

public function getResolvedDestinationLatitudeAttribute(): ?float
{
    $completedDeal = $this->negotiations()->where('status', 'COMPLETED')->first();
    return $completedDeal->destination_latitude ?? $this->destination_latitude;
}

public function getResolvedDestinationLongitudeAttribute(): ?float
{
    $completedDeal = $this->negotiations()->where('status', 'COMPLETED')->first();
    return $completedDeal->destination_longitude ?? $this->destination_longitude;
}
```

Then downstream views change one line each:

```php
// Before
{{ $harvest->destination_address }}

// After
{{ $harvest->resolved_destination_address }}
```

**Impact**: 3-4 views updated. Each is a single property rename. The accessor handles the logic centrally.

---

### Con 3: `cancelDeal()` can't undo mid-transit

**Problem**: If harvest is assigned to a pooling job, cancel is blocked. No automated dispute resolution.

**Fix**: Add a notification to the logistics partner when a deal is cancelled before transit, and a flag on the pooling job harvest pivot so logistics knows to re-check.

```php
// In cancelDeal(), after the DB transaction succeeds:

// Notify logistics partner if harvest was in a pending pooling job
$pendingJobs = $harvest->poolingJobs()->where('status', 'pending')->get();
foreach ($pendingJobs as $job) {
    // Detach harvest from pending jobs
    $job->harvests()->detach($harvest->id);

    // Recalculate job totals
    $job->load('harvests');
    if ($job->harvests->isEmpty()) {
        $job->status = 'cancelled';
        $job->save();
        if ($job->truck) {
            $job->truck->update(['status' => 'available']);
        }
    } else {
        $totalKg = $job->harvests->sum('pivot.quantity_kg');
        $job->total_kg = $totalKg;
        $job->farm_count = $job->harvests->count();
        $job->save();
    }

    \App\Models\Notification::create([
        'user_id' => $job->logisticsProfile->user_id,
        'title'   => 'Deal Cancelled — Harvest Removed from Route',
        'message' => "A deal for harvest #{$harvest->id} ({$harvest->crop_type}) was cancelled. Route #{$job->id} has been updated.",
        'link'    => route('pooling.index'),
    ]);
}
```

**Impact**: `cancelDeal()` now handles pre-transitPooling jobs automatically. Mid-transit (confirmed/in_progress) remains blocked by design — cargo is physically loaded.

---

### Con 4: No real-time crop board refresh

**Problem**: After `finalizeDeal()`, a ~100ms window exists where another buyer might see stale quantity.

**Fix**: Add a meta refresh header on the crop board page so it auto-reloads every 30 seconds. This is lightweight, no WebSocket needed.

```php
// In BuyerController@cropBoard()
return view('buyer.crop-board', compact('posts', 'negotiatingHarvestIds', 'negotiationRoomMap'))
    ->header('Refresh', '30');
```

Or as a meta tag in the Blade template:

```html
<meta http-equiv="refresh" content="30">
```

**Impact**: Crop board refreshes every 30 seconds automatically. Stale data window reduced from indefinite to 30 seconds. No server-side broadcast infrastructure needed.

---

### Con 5: `remaining_quantity_kg` nullable edge case

**Problem**: Seeders, admin creates, or any code path bypassing `HarvestController@store` won't set `remaining_quantity_kg`. Queries silently exclude these rows.

**Fix**: Add a model `boot()` method that sets `remaining_quantity_kg` on creation if not set.

```php
// app/Models/Harvest.php

protected static function boot()
{
    parent::boot();

    static::creating(function (Harvest $harvest) {
        if (is_null($harvest->remaining_quantity_kg)) {
            $harvest->remaining_quantity_kg = $harvest->quantity_kg;
        }
        if (is_null($harvest->visibility)) {
            $harvest->visibility = 'both';
        }
    });
}
```

Also update seeders to explicitly set the values:

```php
// database/seeders/HarvestSeeder.php
Harvest::create([
    // ... existing fields ...
    'remaining_quantity_kg' => $quantity,
    'visibility'            => 'both',
]);
```

**Impact**: Every harvest creation path (controller, seeder, admin, import) gets correct defaults automatically. No silent exclusions.

---

### Con 6: Negotiation table now stores delivery data

**Problem**: `negotiations` table was a chat/pricing record, now it also stores delivery coordinates. Schema is mixing concerns.

**Fix**: Accept this trade-off for now. The destination fields are nullable and only populated on finalization. If future requirements add complex delivery tracking (multi-stop, rescheduling, proof of delivery), extract to a `deal_deliveries` table at that point.

Document this decision:

```php
// app/Models/Negotiation.php

/**
 * Destination fields on this model store the buyer's drop-off coordinates
 * for this specific deal. This avoids overwriting the harvest's default
 * destination when multiple buyers purchase from the same lot.
 *
 * NOTE: If delivery tracking grows in complexity (multi-stop, rescheduling,
 * proof of delivery), extract these fields to a dedicated deal_deliveries table.
 */
```

**Impact**: No code change needed. The nullable columns add zero overhead when not used. Future migration is straightforward if needed.

---

### Con 7: Pooling service reads from two models

**Problem**: Knapsack weight reads from `negotiations.negotiated_volume` with a fallback to `quantity_kg`. If no completed negotiation exists, the fallback uses the full original amount.

**Fix**: Make the fallback safe by checking harvest status first. If a harvest reaches the pooling service, it should always have a completed negotiation. Add a guard.

```php
// app/Services/ResourcePoolingService.php

// In plan() after fetching harvests, add validation:
$harvests = $harvests->filter(function ($harvest) {
    $completedNegotiation = $harvest->negotiations->first();
    if (!$completedNegotiation) {
        \Illuminate\Support\Facades\Log::warning(
            "Harvest #{$harvest->id} reached pooling service without completed negotiation. Skipping."
        );
        return false;
    }
    return true;
});
```

Also update the fallback in knapsack/cost allocation to use `remaining_quantity_kg` instead of `quantity_kg`:

```php
// Before (dangerous fallback)
$qty = (float) ($harvest->negotiations->first()->negotiated_volume ?? $harvest->quantity_kg);

// After (safe fallback)
$negotiation = $harvest->negotiations->first();
$qty = $negotiation ? (float) $negotiation->negotiated_volume : 0;
```

If no negotiation exists, qty = 0, which means the harvest is excluded from knapsack selection (can't fit 0kg). This prevents truck overload.

**Impact**: Harvests without completed negotiations are logged and skipped. Fallback defaults to 0 instead of full original amount. No truck overload possible.

---

## Pre-Existing Bug Fixes (Prerequisites)

These bugs exist in the current codebase. They should be fixed alongside or before implementing the new plan.

### Bug Fix 1: Crop `$fillable` Missing `baseline_price_per_kg` (P0)

**File**: `app/Models/Crop.php:12-17`

**Problem**: `AdminController::updateBaselinePrice()` calls `$crop->update(['baseline_price_per_kg' => ...])` which silently fails because the field is not in `$fillable`. Pricing guidance on the farmer dashboard shows stale/zero values.

```php
// CURRENT (broken)
protected $fillable = [
    'crop_category_id',
    'name',
    'description',
    'status',
];

// FIX
protected $fillable = [
    'crop_category_id',
    'name',
    'description',
    'status',
    'baseline_price_per_kg',
];
```

---

### Bug Fix 2: `agreeTerms()` Allows Self-Agreement (P0)

**File**: `app/Http/Controllers/NegotiationController.php:203-236`

**Problem**: Either party can click "agree" and the status immediately becomes `AGREED`. There is no check that the OTHER party proposed the terms. A buyer could propose terms and immediately agree to their own terms.

**Fix**: Add a proposer check — the person who proposed the last terms cannot be the one who agrees.

```php
// In agreeTerms(), after existing checks:

$lastProposal = \App\Models\NegotiationMessage::where('negotiation_id', $negotiation->id)
    ->where('message_text', 'LIKE', '[System Offer]%')
    ->latest()
    ->first();

if ($lastProposal && $lastProposal->sender_id === $user->id) {
    return back()->with('error', 'You proposed these terms. The other party must agree first.');
}
```

---

### Bug Fix 3: `logisticsCounter()` Missing Price Bounds Check (P1)

**File**: `app/Http/Controllers/PoolingJobController.php:666-708`

**Problem**: `counterProposal()` enforces ±75% bounds around reference price, but `logisticsCounter()` has no bounds. Logistics can counter with exploitative prices.

**Fix**: Add the same bounds check at the top of `logisticsCounter()`:

```php
$referencePrice = (float) ($poolingJob->price_reference ?? 0);
if ($referencePrice > 0) {
    $minAllowed = $referencePrice * 0.25;
    $maxAllowed = $referencePrice * 1.75;
    if ($request->negotiated_price < $minAllowed || $request->negotiated_price > $maxAllowed) {
        return back()->with('error', 'Price must be between ₱' . number_format($minAllowed, 2) . ' and ₱' . number_format($maxAllowed, 2) . ' based on the reference price.');
    }
}
```

---

### Bug Fix 4: Stale Pivot Data in `logisticsCounter()` Notifications (P1)

**File**: `app/Http/Controllers/PoolingJobController.php:691-705`

**Problem**: After `recalculateCostShares()`, the code reads `$h->pivot->cost_share` for the notification message. But `updateExistingPivot()` updates the DB, not the in-memory model. The notification shows the OLD cost share.

**Fix**: Reload after recalculation:

```php
$this->recalculateCostShares($poolingJob);
$poolingJob->load('harvests');  // ADD THIS LINE

foreach ($poolingJob->harvests as $h) {
    // Now $h->pivot->cost_share has the correct value
    $farmerShare = $h->pivot->cost_share ?? 0;
    // ... notification creation ...
}
```

---

### Bug Fix 5: Unverified Logistics Can Plan Routes (P1)

**File**: `app/Http/Controllers/PoolingJobController.php:40-115`

**Problem**: `plan()` and `confirm()` only check `Auth::user()->logisticsProfile` exists. No `is_verified` check. Unverified logistics can plan routes, confirm jobs, and notify farmers.

**Fix**: Add verification check at the top of both methods:

```php
// At the top of plan() and confirm():
if (!Auth::user()->logisticsProfile?->is_verified) {
    return response()->json(['error' => 'Your account is pending verification. Route optimization is not available until approved by an administrator.'], 403);
}
```

---

### Bug Fix 6: Admin `toggleStatus()` Doesn't Handle `partially_sold` Harvests (P1)

**File**: `app/Http/Controllers/AdminController.php:108-135`

**Problem**: When archiving a farmer, only `status = 'active'` harvests are cancelled. `partially_sold` harvests (which have active deals) are NOT cancelled. The farmer's account becomes inactive but their deals remain open.

**Fix**: Update the query to include `partially_sold`:

```php
$activeHarvests = Harvest::where('user_id', $user->id)
    ->whereIn('status', ['active', 'partially_sold'])  // ADD partially_sold
    ->get();
```

And update the cancellation query:

```php
Harvest::where('user_id', $user->id)
    ->whereIn('status', ['active', 'partially_sold'])  // ADD partially_sold
    ->update(['status' => 'cancelled']);
```

---

### Bug Fix 7: `counterProposal()` Sets `cost_share` Directly (P2)

**File**: `app/Http/Controllers/PoolingJobController.php:574-576`

**Problem**: Farmer's counter-proposal sets their `cost_share` to the counter price directly. But `cost_share` should be proportional. Other farmers' shares are not recalculated.

**Fix**: After setting the counter price, recalculate all shares:

```php
$poolingJob->harvests()->updateExistingPivot($harvest->id, [
    'cost_share' => $request->counter_price,
    'status' => 'accepted'
]);

// Recalculate other farmers' shares proportionally
$this->recalculateCostShares($poolingJob);
$poolingJob->load('harvests');  // Refresh pivot data
```

---

## Plan-Introduced Risk Fixes

### Risk Fix 1: Add `HarvestStatus` Constants

**File**: `app/Models/HarvestStatus.php` (new file)

Status values are currently string literals scattered across 10+ files. Adding `partially_sold` increases the risk of typos and missed updates.

```php
<?php

namespace App\Models;

class HarvestStatus
{
    const PENDING = 'pending';
    const ACTIVE = 'active';
    const NEGOTIATING = 'negotiating';
    const PARTIALLY_SOLD = 'partially_sold';
    const SOLD = 'sold';
    const ASSIGNED = 'assigned';
    const IN_PROGRESS = 'in_progress';
    const COMPLETED = 'completed';
    const CANCELLED = 'cancelled';

    /** Harvests available for buyer negotiation. */
    const BUYER_AVAILABLE = [self::ACTIVE, self::PARTIALLY_SOLD];

    /** Harvests visible on the logistics routing map. */
    const LOGISTICS_VISIBLE = [self::SOLD, self::PARTIALLY_SOLD];

    /** Harvests that cannot be edited. */
    const LOCKED = [self::NEGOTIATING, self::SOLD, self::ASSIGNED, self::IN_PROGRESS, self::COMPLETED, self::CANCELLED];
}
```

Then replace string literals throughout the codebase:

```php
// Before
whereIn('status', ['active', 'partially_sold'])

// After
whereIn('status', HarvestStatus::BUYER_AVAILABLE)
```

---

### Risk Fix 2: Crop Board Shows Harvests With No Pickup Coordinates

**File**: `app/Http/Controllers/BuyerController.php` (in `scopedHarvestQuery()`)

**Problem**: Harvests with null lat/lng appear on the crop board. Buyers can negotiate on undeliverable lots.

**Fix**: Add to `scopedHarvestQuery()`:

```php
// In the independent buyer branch:
return Harvest::whereIn('status', HarvestStatus::BUYER_AVAILABLE)
    ->whereIn('visibility', ['buyers_only', 'both'])
    ->where('remaining_quantity_kg', '>', 0)
    ->whereNotNull('latitude')      // ADD
    ->whereNotNull('longitude')     // ADD
    ->whereHas('farmer.farmerProfile', function ($q) {
        $q->where('is_verified', true)
          ->where('affiliation_type', 'independent');
    });
```

---

### Risk Fix 3: Unverified Buyer Can Negotiate But Can't Finalize

**File**: `app/Http/Controllers/NegotiationController.php` (in `start()`)

**Problem**: `start()` doesn't check `buyerProfile->is_verified`. Buyer negotiates, agrees, then can't finalize. Dead end.

**Fix**: Add to `start()` after the role check:

```php
$buyerProfile = $buyer->buyerProfile;
if (!$buyerProfile || !$buyerProfile->is_verified) {
    return back()->with('error', 'Your buyer account must be verified before starting negotiations.');
}
```

---

### Risk Fix 4: Farmer Can Edit Harvest During Active Proposals

**File**: `app/Http/Controllers/HarvestController.php` (in `edit()`)

**Problem**: Edit guard blocks `negotiating` status but doesn't check for pending pooling proposals on `active` harvests.

**Fix**: Add proposal check:

```php
if ($harvest->poolingJobs()->whereIn('status', ['pending', 'confirmed'])->exists()) {
    return back()->with('error', 'Cannot edit while a logistics proposal is active.');
}
```

---

### Risk Fix 5: `cancelDeal()` Auto-Detach From Pending Pooling Jobs

**File**: `app/Http/Controllers/NegotiationController.php` (in `cancelDeal()`)

**Problem**: Current plan blocks cancel if harvest is in ANY active pooling job. But `pending` jobs should allow detach since no cargo is loaded.

**Fix**: Auto-detach from `pending` jobs, only block `confirmed`/`in_progress`:

```php
// In cancelDeal(), replace the pooling job check:

// Block if assigned to confirmed/in_progress jobs (cargo physically loaded)
$activeJobs = $harvest->poolingJobs()->whereIn('status', ['confirmed', 'in_progress'])->exists();
if ($activeJobs) {
    return back()->with('error', 'Cannot cancel — harvest is assigned to an active logistics route that is already confirmed.');
}

// Auto-detach from pending jobs (no cargo loaded yet)
$pendingJobs = $harvest->poolingJobs()->where('status', 'pending')->get();
foreach ($pendingJobs as $job) {
    $job->harvests()->detach($harvest->id);
    $job->load('harvests');
    if ($job->harvests->isEmpty()) {
        $job->status = 'cancelled';
        $job->save();
        $job->truck?->update(['status' => 'available']);
    } else {
        $job->total_kg = $job->harvests->sum('pivot.quantity_kg');
        $job->farm_count = $job->harvests->count();
        $job->save();
    }
    \App\Models\Notification::create([
        'user_id' => $job->logisticsProfile->user_id,
        'title'   => 'Deal Cancelled — Harvest Removed from Route',
        'message' => "A deal for harvest #{$harvest->id} ({$harvest->crop_type}) was cancelled. Route #{$job->id} has been updated.",
        'link'    => route('pooling.index'),
    ]);
}
```

---

## Updated Files Modified (Summary)

| File | Change |
|------|--------|
| `database/migrations/xxxx_add_partial_sale_support.php` | New migration (harvests + negotiations columns) |
| `app/Models/Harvest.php` | Add fillable, casts, scopes, accessors, boot() |
| `app/Models/HarvestStatus.php` | **NEW** — Status constants |
| `app/Models/Negotiation.php` | Add fillable, delivery_destination accessor |
| `app/Models/Crop.php` | Add `baseline_price_per_kg` to `$fillable` |
| `app/Http/Controllers/HarvestController.php` | Set visibility on store, update edit/destroy guards, update index query |
| `app/Http/Controllers/NegotiationController.php` | Update start/proposeTerms/finalizeDeal/agreeTerms, add cancelDeal |
| `app/Http/Controllers/BuyerController.php` | Add visibility filter + coordinate check to scopedHarvestQuery |
| `app/Http/Controllers/RouteOptimizationController.php` | Add visibility filter |
| `app/Http/Controllers/PoolingJobController.php` | Use negotiated volume, add price bounds to logisticsCounter, reload pivot after recalc, add verification check |
| `app/Http/Controllers/DashboardController.php` | Include partially_sold in farmer queries |
| `app/Http/Controllers/AdminController.php` | Handle partially_sold in toggleStatus |
| `app/Services/ResourcePoolingService.php` | Read negotiated volume, add guard for missing negotiations |
| `resources/views/harvests/index.blade.php` | Add remaining qty + sale progress display |
| `resources/views/farmers/farmer-view.blade.php` | Add remaining qty + sale progress display |
| `resources/views/buyer/crop-board.blade.php` | Add negotiation/partial-sale badges, meta refresh |

---

## Updated Cons (After All Fixes)

| # | Con | Resolution | Residual Risk |
|---|-----|-----------|---------------|
| 1 | `partially_sold` codebase sweep | `HarvestStatus` constants + `scopeAvailableForBuyers()` | Low — single scope, greppable |
| 2 | Destination migration on downstream views | `resolved_destination_*` accessors on Harvest | Low — 3-4 views, single property rename |
| 3 | `cancelDeal()` can't undo mid-transit | Auto-detach from `pending` jobs, block `confirmed`/`in_progress` | Low — by design, cargo is physical |
| 4 | No real-time crop board refresh | `<meta http-equiv="refresh" content="30">` | Low — 30s stale window acceptable |
| 5 | `remaining_quantity_kg` nullable edge case | `boot()` method with fallback defaults | Low — covers all creation paths |
| 6 | Negotiation table stores delivery data | Accept trade-off, document for future extraction | Low — nullable columns, zero overhead |
| 7 | Pooling service reads from two models | Guard to skip harvests without negotiations, fallback to 0 | Low — logged and excluded |
| 8 | Status string literals scattered | `HarvestStatus` constants class | Low — single file, future-proof |
| 9 | Unverified buyer can negotiate | Add `is_verified` check in `start()` | Low — one line |
| 10 | Farmer edits during active proposals | Add pooling job check to edit guard | Low — one condition |

---

## Updated Pros

1. **Immutable `quantity_kg`** — Zero risk of breaking existing consumers
2. **Destination per negotiation** — Clean separation, no data loss
3. **Rollback flow exists** — `cancelDeal()` with auto-detach from pending jobs
4. **Harvest stays active when partially sold** — New buyers can negotiate immediately
5. **Crop board UX is honest** — Badges and progress indicators
6. **Audit trail complete** — Partial sale, full sale, deal cancelled events
7. **Race condition fully guarded** — `lockForUpdate()` + re-check
8. **Minimal schema change** — 2 harvest columns, 3 negotiation columns
9. **Cooperative farmers unaffected** — Gated on `affiliation_type`
10. **Backward compatible** — Migration sets defaults
11. **Status constants** — Type-safe, single file to maintain
12. **Pre-existing bugs fixed** — Crop fillable, self-agreement, price bounds, stale pivot, verification checks

---

## Updated Cons

1. **Negotiation table stores delivery data** — Acceptable trade-off. Nullable columns, zero overhead. Documented for future extraction.

2. **30-second stale window on crop board** — Acceptable for the platform's scale. WebSocket would be ideal but out of scope.

3. **`cancelDeal()` can't undo confirmed/in-transit jobs** — By design. Cargo is physical. Manual dispute resolution required.
