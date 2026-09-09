<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pooling_job_id',
        'driver_id',
        'latitude',
        'longitude',
        'speed_kmh',
        'bearing',
        'accuracy_meters',
        'posted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'speed_kmh' => 'float',
        'bearing' => 'float',
        'accuracy_meters' => 'float',
        'posted_at' => 'datetime',
    ];

    /**
     * Get the pooling job this tracking record belongs to.
     */
    public function poolingJob(): BelongsTo
    {
        return $this->belongsTo(PoolingJob::class);
    }

    /**
     * Get the driver (user) who posted this coordinate.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
