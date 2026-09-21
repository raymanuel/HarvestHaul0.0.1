<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BuyerOrder extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY_FOR_DELIVERY = 'ready_for_delivery';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'buyer_id', 'cooperative_id', 'reference', 'status',
        'total_kg', 'total_amount', 'preferred_delivery_date',
        'delivery_address', 'notes',
        'accepted_at', 'rejected_at', 'rejection_reason', 'confirmed_at', 'cancelled_at',
    ];

    protected $casts = [
        'total_kg' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'preferred_delivery_date' => 'date',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function items()
    {
        return $this->hasMany(BuyerOrderItem::class);
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }

    public function scopeForBuyer($query, $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    public function scopeForCooperative($query, $cooperativeId)
    {
        return $query->where('cooperative_id', $cooperativeId);
    }
}