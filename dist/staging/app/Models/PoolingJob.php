<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PoolingJob extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Allow-list for mass assignment (Laravel best practice).
     * System-computed fields like id, created_at, updated_at are protected by default.
     */
    protected $fillable = [
        'logistics_profile_id',
        'truck_id',
        'driver_id',
        'buyer_id',
        'status',
        'truck_capacity_kg',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'radius_km',
        'notes',
        'delivery_deadline',
        'accepted_at',
        'price_reference',
        'negotiated_price',
        'hauling_rate_per_kg',
        'planned_distance_km',
        'actual_distance_km',
        'end_odometer_reading',
        'proposal_expires_at',
        'negotiation_rounds',
        'weather_condition',
        'weather_temperature',
        'weather_wind_speed',
        'weather_icon',
        'weather_checked_at',
        'weather_advisory',
        'farm_distances',
        'road_distance_km',
        'terrain',
        'rate_source',
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
        'start_latitude'        => 'float',
        'start_longitude'       => 'float',
        'end_latitude'          => 'float',
        'end_longitude'         => 'float',
        'confirmed_at'          => 'datetime',
        'accepted_at'           => 'datetime',
        'completed_at'          => 'datetime',
        'end_odometer_reading'  => 'decimal:2',
        'planned_distance_km'   => 'decimal:2',
        'actual_distance_km'    => 'decimal:2',
        'hauling_rate_per_kg'   => 'decimal:2',
        'proposal_expires_at'   => 'datetime',
        'negotiation_rounds'    => 'integer',
        'route_geometry'        => 'array',
        'farm_distances'        => 'array',
        'road_distance_km'      => 'decimal:2',
        'terrain'               => 'string',
        'rate_source'           => 'string',
        'status'                => PoolingJobStatus::class,
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

    /** The buyer who placed or is awaiting this order. */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * True when this user holds a COMPLETED negotiation on any harvest in the job.
     * Multi-buyer routes (buyer_id = null) rely on this instead of job->buyer_id.
     */
    public function isBuyer(\App\Models\User $user): bool
    {
        return \App\Models\Negotiation::where('buyer_id', $user->id)
            ->where('status', \App\Models\NegotiationStatus::COMPLETED)
            ->whereIn('harvest_id', $this->harvests->pluck('id'))
            ->exists();
    }

    /**
     * Latest GPS tracking record for this job.
     */
    public function latestTracking()
    {
        return $this->hasOne(TrackingRecord::class, 'pooling_job_id')->latest('posted_at');
    }

    /**
     * All harvests bundled into this pooling job.
     * Many-to-many via `pooling_job_harvests` pivot table.
     * Pivot columns available on each $harvest->pivot:
     *   - pickup_order   (sort order for driver stop sequence)
     *   - quantity_kg    (how much of this harvest is in the job)
     *   - cost_share     (this farmer's freight cost portion)
     *   - status         (pending/accepted/rejected)
     *   - payment_status, receipt_path, loaded_quantity_kg, etc.
     * Results are ordered by pickup_order for driver route display.
     */
    public function harvests()
    {
        return $this->belongsToMany(Harvest::class, 'pooling_job_harvests')
                    ->using(PoolingJobHarvest::class)
                    ->withPivot('pickup_order', 'quantity_kg', 'cost_share', 'amount_paid', 'status', 'payment_status', 'receipt_path', 'loaded_quantity_kg', 'loaded_volume_cubic_meters', 'delivery_receipt_path', 'load_photo_path', 'actual_quantity_kg', 'farmer_qty_confirmed', 'crop_confirmed', 'arrived_at', 'loaded_at', 'delivered_at', 'buyer_confirmed_at')
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
        if ((float) $this->truck_capacity_kg <= 0) return 0;
        return round(((float) $this->total_kg / (float) $this->truck_capacity_kg) * 100, 1);
    }

    /**
     * Invoices generated for this pooling job.
     */
    public function invoices()
    {
        return $this->hasMany(\App\Models\Invoice::class);
    }
}
