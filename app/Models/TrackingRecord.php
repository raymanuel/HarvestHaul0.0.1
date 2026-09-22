<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TrackingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_type',
        'job_id',
        'driver_id',
        'latitude',
        'longitude',
        'speed_kmh',
        'bearing',
        'accuracy_meters',
        'posted_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'speed_kmh' => 'float',
        'bearing' => 'float',
        'accuracy_meters' => 'float',
        'posted_at' => 'datetime',
    ];

    /**
     * The haul job or delivery this coordinate belongs to.
     */
    public function job(): MorphTo
    {
        return $this->morphTo('job', 'job_type', 'job_id');
    }

    /**
     * The delivery personnel (user) who posted this coordinate.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}