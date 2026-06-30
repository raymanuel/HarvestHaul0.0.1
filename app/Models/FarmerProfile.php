<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'farm_location',
        'is_verified',
        'latitude',
        'longitude',
        'affiliation_type',
        'cooperative_id',
    ];

    protected $casts = [
        'is_verified'      => 'boolean',
        'affiliation_type' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function harvests()
    {
        return $this->hasMany(Harvest::class, 'user_id', 'user_id');
    }

    public function activeHarvests()
    {
        return $this->hasMany(Harvest::class, 'user_id', 'user_id')
                    ->where('status', 'active');
    }

    public function cooperative()
    {
        return $this->belongsTo(LogisticsProfile::class, 'cooperative_id');
    }

    public function isCooperativeMember(): bool
    {
        return $this->affiliation_type === 'cooperative'
            && $this->cooperative_id !== null;
    }

    public function isIndependent(): bool
    {
        return $this->affiliation_type === 'independent';
    }
}
