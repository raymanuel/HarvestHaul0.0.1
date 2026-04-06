<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmerProfile extends Model
{
    // Allow these to be saved via create()
    protected $fillable = [
        'user_id',
        'phone',
        'farm_location',
        'is_verified',
        'latitude',
        'longitude'
    ];

    // Link back to the User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
