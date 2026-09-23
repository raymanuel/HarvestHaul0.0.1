<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HaulJobStop extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'haul_job_id', 'haul_request_id', 'buyer_order_id', 'sequence_no',
        'status', 'failure_reason', 'planned_arrival_at', 'actual_arrival_at', 'delay_notified_at',
        'picked_up_at', 'delivered_at', 'pod_photo_path',
    ];

    protected $casts = [
        'planned_arrival_at' => 'datetime',
        'actual_arrival_at' => 'datetime',
        'delay_notified_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Whether the automated delay check has flagged this stop as running
     * late (planned_arrival_at passed with no arrival recorded yet).
     */
    public function isFlaggedLate(): bool
    {
        return $this->delay_notified_at !== null;
    }

    public function haulJob()
    {
        return $this->belongsTo(HaulJob::class);
    }

    public function haulRequest()
    {
        return $this->belongsTo(HaulRequest::class);
    }

    public function buyerOrder()
    {
        return $this->belongsTo(BuyerOrder::class);
    }

    public function receivingRecords()
    {
        return $this->hasMany(ReceivingRecord::class, 'haul_request_id', 'haul_request_id');
    }

    public function isDeliveryStop(): bool
    {
        return $this->buyer_order_id !== null;
    }
}
