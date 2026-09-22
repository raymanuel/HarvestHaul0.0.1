<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogisticsProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'business_permit_no',
        'contact_number',
        'office_address',
        'phone',
        'is_verified',
        'business_permit_verified',
        'logistics_type',
        'cda_registration_no',
        'default_hauling_rate',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'is_verified'             => 'boolean',
        'business_permit_verified' => 'boolean',
        'default_hauling_rate'    => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
