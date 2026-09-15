<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutboundOrderLine extends Model
{
    use HasFactory;

    protected $fillable = ['outbound_order_id', 'crop_type', 'quantity_kg', 'rate_per_kg', 'subtotal'];

    protected $casts = [
        'quantity_kg' => 'decimal:2',
        'rate_per_kg' => 'decimal:2',
        'subtotal'    => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(OutboundOrder::class, 'outbound_order_id');
    }
}