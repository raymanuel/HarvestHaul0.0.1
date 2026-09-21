<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CropAvailability extends Model
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_SOLD_OUT = 'sold_out';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'cooperative_id', 'receiving_record_id',
        'crop_id', 'crop_variety_id', 'crop_grade_id',
        'quantity_kg', 'sold_kg', 'selling_price_per_kg', 'status',
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:2',
        'sold_kg' => 'decimal:2',
        'selling_price_per_kg' => 'decimal:2',
    ];

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function receivingRecord()
    {
        return $this->belongsTo(ReceivingRecord::class);
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

    public function scopeAvailableForSale($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function getRemainingKgAttribute()
    {
        return max(0, (float) $this->quantity_kg - (float) $this->sold_kg);
    }
}