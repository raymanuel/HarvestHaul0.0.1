<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'business_permit_no',
        'cda_registration_no',
        'phone',
        'is_verified',
        'logistics_type',
    ];

    protected $casts = [
        'is_verified'    => 'boolean',
        'logistics_type' => 'string',
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

    public function memberFarmers()
    {
        return $this->hasMany(FarmerProfile::class, 'cooperative_id');
    }

    public function isCooperative(): bool
    {
        return $this->logistics_type === 'cooperative';
    }

    public function isCompany(): bool
    {
        return $this->logistics_type === 'company';
    }
}
