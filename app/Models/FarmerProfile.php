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

    public function harvests()
    {
        return $this->hasMany(Harvest::class, 'user_id', 'user_id');
    }

    // Convenience: only active harvests — used by RouteOptimizationController
    public function activeHarvests()
    {
        return $this->hasMany(Harvest::class, 'user_id', 'user_id')
                    ->where('status', 'active');
    }
}
