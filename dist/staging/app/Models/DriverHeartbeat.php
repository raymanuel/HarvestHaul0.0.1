<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverHeartbeat extends Model
{
    protected $fillable = [
        'driver_id',
        'logistics_profile_id',
        'latitude',
        'longitude',
        'reported_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'reported_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function logisticsProfile(): BelongsTo
    {
        return $this->belongsTo(LogisticsProfile::class);
    }
}
