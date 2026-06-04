<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\FarmerProfile;
use App\Models\LogisticsProfile;
use App\Models\Harvest;

/**
 * ═══════════════════════════════════════════════════════════════
 * MODEL: User
 * ═══════════════════════════════════════════════════════════════
 * Central auth entity for the entire platform.
 * ROLES (stored in `role` column):
 *   - 'admin'            → full platform access
 *   - 'farmer'           → posts harvests, receives pooling proposals
 *   - 'logistics_partner'→ owns trucks/drivers, creates pooling jobs
 *   - 'driver'           → assigned to pooling jobs, streams GPS
 *
 * FLOW:
 *   User registers → admin verifies → role-specific dashboard loads
 *   Each role gets a separate profile model (FarmerProfile, etc.)
 * ═══════════════════════════════════════════════════════════════
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Mass-assignable fields.
     * `role` determines which dashboard/middleware applies.
     * `status` = 'active' | 'inactive' — inactive users are force-logged out by EnsureAccountIsActive middleware.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * Hidden from JSON serialization — never expose password or remember token to API responses.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Automatic type casting.
     * `password` → bcrypt hashed on write.
     * `email_verified_at` → Carbon datetime object.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // Each User can have exactly ONE role-specific profile.
    // The profile holds extended attributes (farm_location, company_name, etc.)
    // ─────────────────────────────────────────────────────────

    /**
     * Audit trail: admin actions recorded against target users.
     * Used by the admin audit log panel.
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'target_id');
    }

    /**
     * Farmer extended profile (farm_location, barangay, cooperative_id, is_verified, etc.)
     * NULL if the user is not a farmer.
     */
    public function farmerProfile()
    {
        return $this->hasOne(FarmerProfile::class);
    }

    /**
     * Logistics partner profile (company_name, logistics_type, is_verified, etc.)
     * NULL if the user is not a logistics partner.
     */
    public function logisticsProfile()
    {
        return $this->hasOne(LogisticsProfile::class);
    }

    /**
     * Driver profile (license_number, assigned truck, etc.)
     * NULL if the user is not a driver.
     */
    public function driverProfile()
    {
        return $this->hasOne(DriverProfile::class);
    }

    /**
     * All harvests POSTED by this farmer user.
     * Foreign key: harvests.user_id → users.id
     */
    public function harvests()
    {
        return $this->hasMany(Harvest::class, 'user_id');
    }

    /**
     * Harvests ASSIGNED to this user as driver for pickup.
     * Foreign key: harvests.driver_id → users.id
     */
    public function assignedHarvests()
    {
        return $this->hasMany(Harvest::class, 'driver_id');
    }

    /** Compliance documents uploaded by this farmer for admin verification. */
    public function farmerDocuments()
    {
        return $this->hasMany(FarmerDocument::class, 'user_id');
    }

    /** Compliance documents uploaded by this logistics partner for admin verification. */
    public function logisticsDocuments()
    {
        return $this->hasMany(LogisticsDocument::class, 'user_id');
    }
}
