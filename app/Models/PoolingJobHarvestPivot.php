<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoolingJobHarvestPivot extends Model
{
    use HasFactory;

    protected $table = 'pooling_job_harvests';

    protected $fillable = [
        'pooling_job_id',
        'harvest_id',
        'pickup_order',
        'quantity_kg',
        'status',
        'cost_share',
        'paid_at',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(PoolingJob::class, 'pooling_job_id');
    }

    public function harvest(): BelongsTo
    {
        return $this->belongsTo(Harvest::class);
    }
}
