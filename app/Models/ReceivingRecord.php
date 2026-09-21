<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceivingRecord extends Model
{
    public const STATUS_PENDING = 'pending_confirmation';
    public const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'haul_job_id', 'haul_request_id', 'cooperative_id', 'farmer_id',
        'crop_id', 'crop_variety_id', 'crop_grade_id',
        'actual_sacks', 'actual_weight_kg', 'buying_price_per_kg', 'total_amount',
        'remarks', 'recorded_by', 'recording_role', 'confirmed_by', 'confirmed_at', 'status',
    ];

    protected $casts = [
        'actual_weight_kg' => 'decimal:2',
        'buying_price_per_kg' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    public function haulJob()
    {
        return $this->belongsTo(HaulJob::class);
    }

    public function haulRequest()
    {
        return $this->belongsTo(HaulRequest::class);
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public function cropVariety()
    {
        return $this->belongsTo(CropVariety::class);
    }

    public function cropGrade()
    {
        return $this->belongsTo(CropGrade::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function availability()
    {
        return $this->hasOne(CropAvailability::class);
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }
}