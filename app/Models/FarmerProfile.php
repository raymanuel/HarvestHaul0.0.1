<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FarmerProfile extends Model
{
    use HasFactory;
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
        'is_verified' => 'boolean',
        'affiliation_type' => 'string',
        'membership_requested_at' => 'datetime',
        'membership_decided_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class, 'cooperative_id');
    }

    public function isCooperativeMember(): bool
    {
        return $this->affiliation_type === 'cooperative'
            && $this->cooperative_id !== null;
    }
}