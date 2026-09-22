<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoolingJobHarvest extends Model
{
    protected $table = 'pooling_job_harvests';

    protected $fillable = [
        'pooling_job_id',
        'harvest_id',
        'pickup_order',
        'quantity_kg',
        'distance_from_route',
        'cost_share',
        'status',
        'payment_status',
        'receipt_path',
        'delivery_receipt_path',
        'loaded_quantity_kg',
        'loaded_volume_cubic_meters',
    ];

    protected $casts = [
        'pickup_order'        => 'integer',
        'quantity_kg'         => 'decimal:2',
        'distance_from_route' => 'decimal:4',
        'cost_share'          => 'decimal:2',
        'loaded_quantity_kg'  => 'decimal:2',
    ];

    public function poolingJob(): BelongsTo
    {
        return $this->belongsTo(PoolingJob::class);
    }

    public function harvest(): BelongsTo
    {
        return $this->belongsTo(Harvest::class);
    }
}
