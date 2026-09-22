<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyerOrderItem extends Model
{
    protected $fillable = [
        'buyer_order_id', 'crop_availability_id',
        'crop_id', 'crop_variety_id', 'crop_grade_id',
        'quantity_kg', 'rate_per_kg', 'subtotal',
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:2',
        'rate_per_kg' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(BuyerOrder::class, 'buyer_order_id');
    }

    public function availability()
    {
        return $this->belongsTo(CropAvailability::class, 'crop_availability_id');
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
}