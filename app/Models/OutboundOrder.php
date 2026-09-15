<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OutboundOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_card_id', 'logistics_profile_id', 'status', 'tracking_token',
        'total_kg', 'total_amount', 'dispatched_at', 'confirmed_at', 'delivered_at', 'completed_at', 'notes',
    ];

    protected $casts = [
        'total_kg'      => 'decimal:2',
        'total_amount'  => 'decimal:2',
        'dispatched_at' => 'datetime',
        'confirmed_at'  => 'datetime',
        'delivered_at'  => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function customerCard()
    {
        return $this->belongsTo(CustomerCard::class);
    }

    public function logisticsProfile()
    {
        return $this->belongsTo(LogisticsProfile::class);
    }

    public function orderLines()
    {
        return $this->hasMany(OutboundOrderLine::class);
    }

    public function poolingJob()
    {
        return $this->hasOne(PoolingJob::class, 'outbound_order_id');
    }

    public function scopeForProfile($query, int $logisticsProfileId)
    {
        return $query->where('logistics_profile_id', $logisticsProfileId);
    }

    public function totalKg(): float
    {
        return (float) $this->orderLines->sum('quantity_kg');
    }

    public function subtotal(): float
    {
        return (float) $this->orderLines->sum('subtotal');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['confirmed', 'in_transit', 'awaiting_confirmation']);
    }
}