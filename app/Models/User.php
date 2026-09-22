<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\AuditLog;
use App\Models\Message;
use App\Traits\HasFarmerRelations;
use App\Traits\HasDriverRelations;
use App\Traits\HasBuyerRelations;
use App\Traits\HasLogisticsRelations;

/**
 * ═══════════════════════════════════════════════════════════
 * MODEL: User
 * ═══════════════════════════════════════════════════════════
 * Central auth entity for the entire platform.
 * ROLES (stored in `role` column):
 *   - 'super_admin'        → platform access, verifies coops & buyers
 *   - 'coop_admin'         → manages the cooperative's people & operations
 *   - 'field_receiving'    → records receiving (weight/grade/price) at pickup
 *   - 'delivery_personnel' → drives haul pickups and buyer deliveries
 *   - 'farmer'             → submits haul requests, sees procurement
 *   - 'buyer'              → orders crops from cooperatives
 * ═══════════════════════════════════════════════════════════
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasFarmerRelations, HasDriverRelations, HasBuyerRelations, HasLogisticsRelations;

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

    protected $hidden = [
        'password',
        'remember_token',
        'email_otp',
        'email_otp_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_otp_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class, 'cooperative_id');
    }

    public function coopAdminOf()
    {
        return $this->hasOne(Cooperative::class, 'coop_admin_user_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'target_id');
    }

    public function haulRequests()
    {
        return $this->hasMany(HaulRequest::class, 'farmer_id');
    }

    public function receivingRecords()
    {
        return $this->hasMany(ReceivingRecord::class, 'farmer_id');
    }

    public function orders()
    {
        return $this->hasMany(BuyerOrder::class, 'buyer_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedUnreadMessages()
    {
        return $this->hasMany(Message::class, 'recipient_id')->whereNull('read_at');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN->value;
    }

    public function isCoopAdmin(): bool
    {
        return $this->role === UserRole::COOP_ADMIN->value;
    }

    public function isFieldPersonnel(): bool
    {
        return $this->role === UserRole::FIELD_RECEIVING->value;
    }

    public function isDeliveryPersonnel(): bool
    {
        return $this->role === UserRole::DELIVERY_PERSONNEL->value;
    }

    public function isFarmer(): bool
    {
        return $this->role === UserRole::FARMER->value;
    }

    public function isBuyer(): bool
    {
        return $this->role === UserRole::BUYER->value;
    }

    public function roleLabel(): string
    {
        return UserRole::tryFrom($this->role)?->label() ?? ucfirst($this->role);
    }
}