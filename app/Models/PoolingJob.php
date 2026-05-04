<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PoolingJob extends Model
{
    use HasFactory;

    protected $fillable = [
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

    protected $casts = [
        'total_kg'          => 'decimal:2',
        'truck_capacity_kg' => 'decimal:2',
        'radius_km'         => 'decimal:2',
        'start_latitude'    => 'decimal:8',
        'start_longitude'   => 'decimal:8',
        'end_latitude'      => 'decimal:8',
        'end_longitude'     => 'decimal:8',
        'confirmed_at'      => 'datetime',
        'completed_at'      => 'datetime',
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
