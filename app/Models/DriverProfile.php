<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\PoolingJob;

class DriverProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'partner_id', // The "Foreign Key" to their employer
        'license_no',
        'license_number',
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

    /**
     * Accessor & Mutator for license_number mapped to license_no
     */
    public function getLicenseNumberAttribute()
    {
        return $this->attributes['license_no'] ?? null;
    }

    public function setLicenseNumberAttribute($value)
    {
        $this->attributes['license_no'] = $value;
    }

    /**
     * Count of active pooling jobs currently assigned to this driver.
     * "Active" means confirmed or in_progress.
     */
    public function activePoolingJobsCount(): int
    {
        return PoolingJob::where('driver_id', $this->user_id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->count();
    }

    /**
     * Accessor & Mutator for status mapped to employment_status
     */
    public function getStatusAttribute()
    {
        return $this->attributes['employment_status'] ?? null;
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['employment_status'] = $value;
    }
}
