<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PoolingJob extends Model
{
    use HasFactory;

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
     * The attributes that should be cast.
     * Keeps high-precision mapping numbers and automatically serializes route paths.
     */
    protected $casts = [
        'total_kg'          => 'decimal:2',
        'truck_capacity_kg' => 'decimal:2',
        'radius_km'         => 'decimal:2',
        'start_latitude'    => 'float', // Advanced tip: switched to float for faster spatial math
        'start_longitude'   => 'float',
        'end_latitude'      => 'float',
        'end_longitude'     => 'float',
        'confirmed_at'      => 'datetime',
        'completed_at'      => 'datetime',
        'route_geometry'    => 'array', // CRITICAL: Fixes the Array-to-String conversion crash
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function logisticsProfile()
    {
        return $this->belongsTo(LogisticsProfile::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function harvests()
    {
        return $this->belongsToMany(Harvest::class, 'pooling_job_harvests')
                    ->withPivot('pickup_order', 'quantity_kg', 'distance_from_route')
                    ->orderByPivot('pickup_order');
    }

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    public function scopeForPartner($query, $logisticsProfileId)
    {
        return $query->where('logistics_profile_id', $logisticsProfileId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['confirmed', 'in_progress']);
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    public function getLoadPercentageAttribute(): float
    {
        if (!$this->truck_capacity_kg) return 0;
        return round(($this->total_kg / $this->truck_capacity_kg) * 100, 1);
    }
}
