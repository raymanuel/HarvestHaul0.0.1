<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DriverProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'partner_id', // The "Foreign Key" to their employer
        'license_number',
        'vehicle_type',
        'status'
    ];

    /**
     * Relationship: A Driver profile BELONGS TO a User account.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: A Driver BELONGS TO a Logistics Partner.
     * This allows us to do: $driver->partner->company_name
     */
    public function partner()
    {
        return $this->belongsTo(LogisticsProfile::class, 'partner_id');
    }
}
