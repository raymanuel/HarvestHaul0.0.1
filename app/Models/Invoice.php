<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'pooling_job_id',
        'logistics_profile_id',
        'invoice_number',
        'total_amount',
        'total_kg',
        'farm_count',
        'status',
        'pdf_path',
        'generated_at',
        'sent_at',
        'voided_at',
        'void_reason',
        'due_at',
        'paid_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_kg' => 'decimal:2',
        'generated_at' => 'datetime',
        'sent_at' => 'datetime',
        'voided_at' => 'datetime',
        'due_at' => 'date',
        'paid_at' => 'datetime',
    ];

    public function poolingJob(): BelongsTo
    {
        return $this->belongsTo(PoolingJob::class);
    }

    public function logisticsProfile(): BelongsTo
    {
        return $this->belongsTo(LogisticsProfile::class);
    }
}
