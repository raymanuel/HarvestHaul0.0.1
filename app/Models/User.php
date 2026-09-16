<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\AuditLog;
use App\Models\LogisticsProfile;
use App\Traits\HasFarmerRelations;
use App\Traits\HasDriverRelations;
use App\Traits\HasLogisticsRelations;
use App\Traits\HasBuyerRelations;

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
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasFarmerRelations, HasDriverRelations, HasLogisticsRelations, HasBuyerRelations;

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
        'phone',
        'affiliation_type',
        'cooperative_id',
    ];

    /**
     * Hidden from JSON serialization — never expose password, remember token, or OTP to API responses.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_otp',
        'email_otp_expires_at',
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
            'email_otp_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // Role-specific relations are provided by traits: HasFarmerRelations,
    // HasDriverRelations, HasLogisticsRelations, HasBuyerRelations.
    // ─────────────────────────────────────────────────────────

    public function cooperative()
    {
        return $this->belongsTo(LogisticsProfile::class, 'cooperative_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'target_id');
    }

    public function negotiations()
    {
        return $this->hasMany(\App\Models\Negotiation::class);
    }

    public function isBuyer()
    {
        return $this->role === 'buyer';
    }
}
