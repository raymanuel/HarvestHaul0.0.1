<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DriverProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cooperative_id',
        'license_no',
        'vehicle_type',
        'phone',
        'employment_status',
        'status',
        'license_restriction',
        'last_shift_ended_at',
        'identity_verified',
        'id_photo_path',
        'selfie_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class, 'cooperative_id');
    }

    public function getStatusAttribute()
    {
        return $this->attributes['employment_status'] ?? null;
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['employment_status'] = $value;
    }
}