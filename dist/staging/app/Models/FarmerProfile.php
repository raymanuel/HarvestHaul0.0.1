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
        'membership_status',
        'membership_requested_at',
        'membership_decided_at',
    ];

    protected $casts = [
        'is_verified'      => 'boolean',
        'affiliation_type'         => 'string',
        'membership_requested_at'  => 'datetime',
        'membership_decided_at'    => 'datetime',
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

    // NOTE: scopeNearby() will be provided by the shared HasNearbyScope trait
    // once app/Concerns/HasNearbyScope.php is created by another task.

    public function scopePendingMembership($query)
    {
        return $query->where('membership_status', 'pending');
    }

    public function scopeApprovedMembership($query)
    {
        return $query->where('membership_status', 'approved');
    }

    public function scopeRejectedMembership($query)
    {
        return $query->where('membership_status', 'rejected');
    }
}
