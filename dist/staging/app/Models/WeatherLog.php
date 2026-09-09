<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherLog extends Model
{
    protected $fillable = [
        'pooling_job_id',
        'latitude',
        'longitude',
        'condition',
        'description',
        'icon',
        'temperature',
        'feels_like',
        'humidity',
        'wind_speed',
        'wind_gust',
        'visibility',
        'advisory',
        'is_severe',
        'forecast_json',
        'checked_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'temperature' => 'float',
        'feels_like' => 'float',
        'humidity' => 'float',
        'wind_speed' => 'float',
        'wind_gust' => 'float',
        'visibility' => 'integer',
        'is_severe' => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function poolingJob(): BelongsTo
    {
        return $this->belongsTo(PoolingJob::class);
    }
}
