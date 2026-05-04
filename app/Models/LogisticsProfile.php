<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'business_permit_no',
        'phone',
        'is_verified',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function drivers()
    {
        return $this->hasMany(DriverProfile::class, 'partner_id');
    }
    public function trucks()
    {
        return $this->hasMany(Truck::class);
    }

    public function availableTrucks()
    {
        return $this->hasMany(Truck::class)->where('status', 'available');
    }
}
