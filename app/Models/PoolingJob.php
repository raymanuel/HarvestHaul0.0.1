<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ═══════════════════════════════════════════════════════════════
 * MODEL: PoolingJob
 * ═══════════════════════════════════════════════════════════════
 * The central "job record" that ties together:
 *   - A logistics partner's truck
 *   - A driver assigned to drive
 *   - One or more harvests from different farmers
 *   - A planned route (geometry stored as JSON)
 *   - Pricing (reference + negotiated)
 *
 * LIFECYCLE (status flow):
 *   pending → confirmed → in_progress → completed
 *   (pending = created but not yet accepted by all parties)
 *   (confirmed = all parties agreed, driver assigned)
 *   (in_progress = driver has started the route)
 *   (completed = all pickups/drop-offs finished)
 *
 * PIVOT TABLE: pooling_job_harvests
 *   Stores per-harvest attributes for the job:
 *   - pickup_order    → stop sequence number (1 = first pickup)
 *   - quantity_kg     → how much cargo from this farm
 *   - distance_from_route → deviation from main route
 *   - cost_share      → this farmer's proportional freight cost
 *                       (computed at confirmation time)
 *
 * PRICING LOGIC:
 *   price_reference  → algo-generated estimate (distance × rate + weight × rate + base)
 *   negotiated_price → final agreed price after farmer/logistics negotiation
 *   cost_share       → per-farmer portion = (their_kg / total_kg) × negotiated_price
 * ═══════════════════════════════════════════════════════════════
 */
class PoolingJob extends Model
{
    use HasFactory;

    /**
     * Guarded columns — set only via direct assignment (not mass assignment).
     * These are system-computed fields that shouldn't be freely set via forms.
     */
    protected $guarded = [
        'logistics_profile_id',
        'truck_id',
        'driver_id',
        'status',
        'total_kg',
        'truck_capacity_kg',
        'farm_count',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'radius_km',
        'notes',
        'confirmed_at',
        'completed_at',
    ];

    /**
     * Type casts.
     * - Decimals ensure database precision is preserved when returned.
     * - route_geometry is stored as JSON text in DB → cast to PHP array on read.
     *   CRITICAL: Without this cast, passing $job->route_geometry to JS would
     *   throw an "Array to string conversion" error.
     */
    protected $casts = [
        'total_kg'          => 'decimal:2',
        'truck_capacity_kg' => 'decimal:2',
        'radius_km'         => 'decimal:2',
        'start_latitude'    => 'float', // float (not decimal) for faster math in spatial calculations
        'start_longitude'   => 'float',
        'end_latitude'      => 'float',
        'end_longitude'     => 'float',
        'confirmed_at'      => 'datetime',
        'completed_at'      => 'datetime',
        'route_geometry'    => 'array', // CRITICAL: JSON column → PHP array for JS rendering
    ];

    // ─────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────

    /** The logistics partner company that created this job. */
    public function logisticsProfile()
    {
        return $this->belongsTo(LogisticsProfile::class);
    }

    /** The specific truck assigned to carry this load. */
    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    /**
     * The driver (User with role='driver') operating the truck.
     * driver_id comes from the truck's assigned driver at confirmation time.
     */
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * All harvests bundled into this pooling job.
     * Many-to-many via `pooling_job_harvests` pivot table.
     * Pivot columns available on each $harvest->pivot:
     *   - pickup_order   (sort order for driver stop sequence)
     *   - quantity_kg    (how much of this harvest is in the job)
     *   - distance_from_route
     *   - cost_share     (this farmer's freight cost portion)
     * Results are ordered by pickup_order for driver route display.
     */
    public function harvests()
    {
        return $this->belongsToMany(Harvest::class, 'pooling_job_harvests')
                    ->withPivot('pickup_order', 'quantity_kg', 'distance_from_route', 'cost_share')
                    ->orderByPivot('pickup_order');
    }

    // ─────────────────────────────────────────────────────────
    // QUERY SCOPES
    // ─────────────────────────────────────────────────────────

    /**
     * Scope to a specific logistics partner.
     * Usage: PoolingJob::forPartner($profile->id)->get()
     */
    public function scopeForPartner($query, $logisticsProfileId)
    {
        return $query->where('logistics_profile_id', $logisticsProfileId);
    }

    /**
     * Active jobs = confirmed OR in_progress.
     * Used for live tracking and driver dashboard counts.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['confirmed', 'in_progress']);
    }

    // ─────────────────────────────────────────────────────────
    // COMPUTED ATTRIBUTES (Accessors)
    // ─────────────────────────────────────────────────────────

    /**
     * How full the truck is as a percentage.
     * Formula: (total_kg / truck_capacity_kg) × 100
     * Displayed on driver job card and logistics route view.
     * Returns 0 if truck_capacity_kg is not set.
     */
    public function getLoadPercentageAttribute(): float
    {
        if (!$this->truck_capacity_kg) return 0;
        return round(($this->total_kg / $this->truck_capacity_kg) * 100, 1);
    }
}
