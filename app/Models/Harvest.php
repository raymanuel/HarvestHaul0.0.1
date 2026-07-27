<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Harvest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'driver_id',
        'crop_category_id',
        'crop_id',
        'crop_variety_id',
        'crop_type',
        'variety',
        'quantity_kg',
        'remaining_quantity_kg',
        'unit',
        'status',
        'visibility',
        'notes',
        'harvest_date',
        'quality_grade',
        'packaging_type',
        'suggested_price_per_kg',
        'crop_photos',
        'latitude',
        'longitude',
        'cluster_id',
        'destination_id',
        'destination_address',
        'destination_latitude',
        'destination_longitude',
    ];

    protected $casts = [
        'quantity_kg'              => 'decimal:2',
        'suggested_price_per_kg'   => 'decimal:2',
        'remaining_quantity_kg'    => 'decimal:2',
        'latitude'                 => 'decimal:8',
        'longitude'                => 'decimal:8',
        'destination_latitude'     => 'decimal:8',
        'destination_longitude'    => 'decimal:8',
        'harvest_date'             => 'date',
        'crop_photos'              => 'array',
        'status'                   => HarvestStatus::class,
    ];

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

    /** Pooling jobs this harvest is attached to. */
    public function poolingJobs()
    {
        return $this->belongsToMany(PoolingJob::class, 'pooling_job_harvests', 'harvest_id', 'pooling_job_id');
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

    /** Harvests available for buyer negotiation (active or partially sold). */
    public function scopeAvailableForBuyers($query)
    {
        return $query->whereIn('status', ['active', 'partially_sold']);
    }

    /** Harvests visible on the logistics routing map (sold or partially sold). */
    public function scopeVisibleToLogistics($query)
    {
        return $query->whereIn('status', ['sold', 'partially_sold']);
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

    /**
     * Bounding box pre-filter for proximity queries.
     * Returns harvests within the approximate rectangular bounds of $radiusKm
     * around the given coordinates. Apply Haversine in PHP for precise distance.
     */
    public function scopeNearby($query, float $lat, float $lng, float $radiusKm)
    {
        $latOffset = $radiusKm / 111.32;
        $lngOffset = $radiusKm / (111.32 * cos(deg2rad($lat)));

        return $query->whereBetween('latitude', [$lat - $latOffset, $lat + $latOffset])
                     ->whereBetween('longitude', [$lng - $lngOffset, $lng + $lngOffset]);
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

    // ─────────────────────────────────────────────────────────
    // NEGOTIATION RELATIONSHIP
    // ─────────────────────────────────────────────────────────

    /** B2B negotiations for this harvest. */
    public function negotiations()
    {
        return $this->hasMany(Negotiation::class, 'harvest_id');
    }

    /** Completed negotiation (cached per instance to avoid N+1). */
    private ?Negotiation $completedNegotiation = null;

    public function getCompletedNegotiation(): ?Negotiation
    {
        if (!isset($this->completedNegotiation)) {
            $this->completedNegotiation = $this->negotiations()
                ->where('status', 'COMPLETED')
                ->first();
        }
        return $this->completedNegotiation;
    }

    // ─────────────────────────────────────────────────────────
    // RESOLVED DESTINATION ACCESSORS
    // Check completed negotiations first (deal-specific), fall back to harvest default.
    // Uses cached getCompletedNegotiation() to avoid N+1 (was 3 queries per row).
    // ─────────────────────────────────────────────────────────

    public function getResolvedDestinationAddressAttribute(): ?string
    {
        $completedDeal = $this->getCompletedNegotiation();
        return $completedDeal?->destination_address ?? $this->destination_address;
    }

    public function getResolvedDestinationLatitudeAttribute(): ?float
    {
        $completedDeal = $this->getCompletedNegotiation();
        return $completedDeal?->destination_latitude ?? $this->destination_latitude;
    }

    public function getResolvedDestinationLongitudeAttribute(): ?float
    {
        $completedDeal = $this->getCompletedNegotiation();
        return $completedDeal?->destination_longitude ?? $this->destination_longitude;
    }

    /**
     * Get the sale progress percentage.
     * Returns null if quantity_kg is 0.
     */
    public function getSaleProgressAttribute(): ?float
    {
        if (!$this->quantity_kg || $this->quantity_kg <= 0) {
            return null;
        }

        $sold = (float) $this->quantity_kg - (float) ($this->remaining_quantity_kg ?? $this->quantity_kg);
        return round(($sold / (float) $this->quantity_kg) * 100, 1);
    }
}
