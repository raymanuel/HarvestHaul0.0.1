<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Truck extends Model
{
    use HasFactory;

    protected $fillable = [
        'cooperative_id',
        'driver_id',
        'plate_number',
        'truck_name',
        'vehicle_type',
        'capacity_kg',
        'capacity_volume_cubic_m',
        'status',
        'notes',
    ];

    protected $casts = [
        'capacity_kg' => 'decimal:2',
        'capacity_volume_cubic_m' => 'decimal:2',
    ];

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function haulJobs()
    {
        return $this->hasMany(HaulJob::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeForCooperative($query, $cooperativeId)
    {
        return $query->where('cooperative_id', $cooperativeId);
    }
}