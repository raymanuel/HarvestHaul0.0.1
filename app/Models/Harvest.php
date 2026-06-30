<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ═══════════════════════════════════════════════════════════════
 * MODEL: Harvest
 * ═══════════════════════════════════════════════════════════════
 * Represents a single crop lot posted by a farmer for transport.
 * This is the core "cargo unit" of the platform.
 *
 * LIFECYCLE (status flow):
 *   active → assigned → in_progress → completed
 *              ↑
 *   Set to 'assigned' when a PoolingJob is confirmed and this
 *   harvest is attached to the job's route.
 *
 * SPATIAL DATA:
 *   latitude/longitude   → pickup location (farmer's farm)
 *   destination_latitude/longitude → drop-off market/buyer
 *   These coordinates feed into the ResourcePoolingService
 *   routing algorithms.
 *
 * RELATIONSHIPS:
 *   Harvest ─ belongs to → User (farmer)
 *   Harvest ─ belongs to → Crop, CropVariety, CropCategory
 *   Harvest ─ belongs to → Destination (market/buyer)
 *   Harvest ─ many-to-many → PoolingJob (via pooling_job_harvests pivot)
 * ═══════════════════════════════════════════════════════════════
 */
class Harvest extends Model
{
    use HasFactory;

    /**
     * Fillable columns.
     * Note: destination can be stored two ways:
     *   (1) destination_id → FK to destinations table (structured)
     *   (2) destination_address + lat/lng → free-text fallback
     */
    protected $fillable = [
        'user_id',
        'driver_id',
        'crop_category_id',
        'crop_id',
        'crop_variety_id',
        'crop_type',       // legacy free-text fallback if crop_id not set
        'variety',         // legacy free-text fallback if crop_variety_id not set
        'quantity_kg',
        'unit',
        'status',          // active | assigned | in_progress | completed
        'notes',
        'harvest_date',
        'quality_grade',
        'packaging_type',
        'latitude',        // pickup GPS lat
        'longitude',       // pickup GPS lng
        'cluster_id',      // optional grouping for nearby farms
        // Destination fields — one of two methods used:
        'destination_id',           // FK to destinations table
        'destination_address',      // free-text fallback
        'destination_latitude',     // destination GPS lat
        'destination_longitude',    // destination GPS lng
    ];

    /**
     * Type casts — ensures coordinates are returned as float/decimal
     * and harvest_date as a Carbon date object for formatting.
     */
    protected $casts = [
        'quantity_kg'            => 'decimal:2',
        'latitude'               => 'decimal:8',
        'longitude'              => 'decimal:8',
        'destination_latitude'   => 'decimal:8',
        'destination_longitude'  => 'decimal:8',
        'harvest_date'           => 'date',
    ];

    // ─────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────

    /** The farmer (User) who posted this harvest. */
    public function farmer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The driver assigned to physically collect this harvest. */
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /** Category grouping (e.g., "Root Crops", "Fruits"). */
    public function cropCategory()
    {
        return $this->belongsTo(CropCategory::class, 'crop_category_id');
    }

    /** Specific crop type (e.g., "Banana", "Cassava"). */
    public function crop()
    {
        return $this->belongsTo(Crop::class, 'crop_id');
    }

    /** Specific variety within a crop (e.g., "Lakatan", "Cardava"). */
    public function cropVariety()
    {
        return $this->belongsTo(CropVariety::class, 'crop_variety_id');
    }

    /** Structured drop-off destination from the destinations table. */
    public function destination()
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }

    // ─────────────────────────────────────────────────────────
    // QUERY SCOPES
    // Use: Harvest::active()->get() or Harvest::withLocation()->get()
    // ─────────────────────────────────────────────────────────

    /** Only returns harvests currently listed on the marketplace. */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /** Returns harvests not yet approved/active (farmer draft state). */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /** Returns harvests not yet linked to any driver. */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('driver_id');
    }

    /** Returns only harvests that have GPS pickup coordinates (required for routing). */
    public function scopeWithLocation($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    /** Returns only harvests with a defined destination (required for drop-off routing). */
    public function scopeWithDestination($query)
    {
        return $query->whereNotNull('destination_latitude')
                     ->whereNotNull('destination_longitude');
    }

    // ─────────────────────────────────────────────────────────
    // COMPUTED ATTRIBUTES (Accessors)
    // ─────────────────────────────────────────────────────────

    /**
     * Human-readable destination label.
     * Prefers the structured destinations.name if available;
     * falls back to free-text destination_address, then '—'.
     * Used in driver job card and cost ledger display.
     */
    public function getDestinationLabelAttribute(): string
    {
        if ($this->destination_id && $this->destination) {
            return $this->destination->name;
        }

        return $this->destination_address ?? '—';
    }

    /**
     * Returns destination as a [latitude, longitude] array.
     * Used by the ResourcePoolingService haversine distance calculator.
     * Returns null if no destination coordinates are set.
     */
    public function getDestinationCoordinatesAttribute(): ?array
    {
        if ($this->destination_latitude && $this->destination_longitude) {
            return [
                'latitude'  => $this->destination_latitude,
                'longitude' => $this->destination_longitude,
            ];
        }

        return null;
    }
}
