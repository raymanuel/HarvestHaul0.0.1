<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'logistics_profile_id', 'name', 'business_type', 'contact', 'address', 'latitude', 'longitude',
    ];

    protected $casts = [
        'latitude'  => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function logisticsProfile()
    {
        return $this->belongsTo(LogisticsProfile::class);
    }

    public function outboundOrders()
    {
        return $this->hasMany(OutboundOrder::class);
    }

    public function scopeForProfile($query, int $logisticsProfileId)
    {
        return $query->where('logistics_profile_id', $logisticsProfileId);
    }
}