<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Negotiation extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'farmer_id',
        'harvest_id',
        'negotiated_price',
        'negotiated_volume',
        'status',
        'destination_address',
        'destination_latitude',
        'destination_longitude',
        'last_activity_at',
        'buyer_last_read_at',
        'farmer_last_read_at',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'buyer_last_read_at' => 'datetime',
        'farmer_last_read_at' => 'datetime',
        'destination_latitude' => 'decimal:8',
        'destination_longitude' => 'decimal:8',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function harvest()
    {
        return $this->belongsTo(Harvest::class, 'harvest_id');
    }

    public function messages()
    {
        return $this->hasMany(NegotiationMessage::class);
    }
}
