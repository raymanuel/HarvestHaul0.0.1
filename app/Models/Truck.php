<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Truck extends Model
{
    use HasFactory;

    protected $fillable = [
        'logistics_profile_id',
        'driver_id',
        'plate_number',
        'truck_name',
        'capacity_kg',
        'status',
        'notes',
    ];

    protected $casts = [
        'capacity_kg' => 'decimal:2',
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function logisticsProfile()
    {
        return $this->belongsTo(LogisticsProfile::class);
    }

    // The default driver assigned to this truck
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function poolingJobs()
    {
        return $this->hasMany(PoolingJob::class);
    }

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeForPartner($query, $logisticsProfileId)
    {
        return $query->where('logistics_profile_id', $logisticsProfileId);
    }

    public function scopeWithDriver($query)
    {
        return $query->whereNotNull('driver_id');
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('driver_id');
    }
}
