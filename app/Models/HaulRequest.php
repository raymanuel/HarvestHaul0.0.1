<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HaulRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'farmer_id', 'cooperative_id',
        'crop_id', 'crop_variety_id', 'packaging_type_id',
        'estimated_sacks', 'estimated_weight_kg',
        'harvest_date', 'preferred_pickup_date', 'pickup_window_start', 'pickup_window_end',
        'pickup_location', 'pickup_location_lat', 'pickup_location_lng', 'notes', 'status',
    ];

    protected $casts = [
        'estimated_weight_kg' => 'decimal:2',
        'harvest_date' => 'date',
        'preferred_pickup_date' => 'date',
        'pickup_window_start' => 'datetime:H:i',
        'pickup_window_end' => 'datetime:H:i',
    ];

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public function cropVariety()
    {
        return $this->belongsTo(CropVariety::class);
    }

    public function packagingType()
    {
        return $this->belongsTo(PackagingType::class);
    }

    public function haulJob()
    {
        return $this->hasOne(HaulJob::class);
    }

    public function receivingRecord()
    {
        return $this->hasOneThrough(ReceivingRecord::class, HaulJob::class, 'haul_request_id', 'haul_job_id');
    }

    public function scopeForCooperative($query, $cooperativeId)
    {
        return $query->where('cooperative_id', $cooperativeId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}