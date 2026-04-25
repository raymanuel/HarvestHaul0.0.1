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

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'target_id');
    }
    // Relationship: A User HAS ONE FarmerProfile
    public function farmerProfile()
    {
        return $this->hasOne(FarmerProfile::class);
    }

    // Relationship: A User HAS ONE LogisticsProfile
    public function logisticsProfile()
    {
        return $this->hasOne(LogisticsProfile::class);
    }

    public function driverProfile()
    {
        return $this->hasOne(DriverProfile::class);
    }

    // A Farmer User HAS MANY Harvests they posted
    public function harvests()
    {
        return $this->hasMany(Harvest::class, 'user_id');
    }

    // A Driver User HAS MANY Harvests assigned to them for pickup
    public function assignedHarvests()
    {
        return $this->hasMany(Harvest::class, 'driver_id');
    }
    public function farmerDocuments()
    {
        return $this->hasMany(FarmerDocument::class, 'user_id');
    }
}
