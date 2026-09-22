<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'buyer_order_id', 'cooperative_id',
        'delivery_personnel_id', 'truck_id',
        'delivery_date', 'scheduled_at', 'status', 'completed_at',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(BuyerOrder::class, 'buyer_order_id');
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function deliveryPersonnel()
    {
        return $this->belongsTo(User::class, 'delivery_personnel_id');
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function tracking()
    {
        return $this->morphMany(TrackingRecord::class, 'job');
    }
}