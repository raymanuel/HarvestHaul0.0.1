<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobDelayState extends Model
{
    protected $fillable = [
        'pooling_job_id',
        'alert_type',
        'severity',
        'triggered_at',
        'resolved_at',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function poolingJob()
    {
        return $this->belongsTo(PoolingJob::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at');
    }
}
