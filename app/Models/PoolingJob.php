<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

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
        'status'            => 'string',
        'total_kg'          => 'decimal:2',
        'truck_capacity_kg' => 'decimal:2',
        'farm_count'        => 'integer',
        'start_latitude'    => 'decimal:8',
        'start_longitude'   => 'decimal:8',
        'end_latitude'      => 'decimal:8',
        'end_longitude'     => 'decimal:8',
        'radius_km'         => 'decimal:2',
        'confirmed_at'      => 'datetime',
        'completed_at'      => 'datetime',
    ];

    public function logisticsProfile(): BelongsTo
    {
        return $this->belongsTo(LogisticsProfile::class);
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function harvests(): BelongsToMany
    {
        return $this->belongsToMany(Harvest::class, 'pooling_job_harvests')
                    ->withPivot(['pickup_order', 'quantity_kg', 'distance_from_route', 'status', 'cost_share'])
                    ->withTimestamps();
    }
}
