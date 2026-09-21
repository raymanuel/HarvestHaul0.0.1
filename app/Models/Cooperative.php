<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cooperative extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_REQUIRES_REVISION = 'requires_revision';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name', 'type', 'province', 'city', 'municipality', 'barangay', 'street_address',
        'contact_number', 'official_email', 'year_established', 'business_activities',
        'cda_registration_number', 'registration_date',
        'cert_document_path', 'articles_document_path', 'bylaws_document_path',
        'rep_name', 'rep_position', 'rep_contact', 'rep_email',
        'rep_id_type', 'rep_id_number', 'rep_id_document_path', 'rep_authorization_document_path',
        'status', 'reviewer_id', 'reviewed_at', 'rejection_reason', 'admin_notes',
        'coop_admin_user_id', 'latitude', 'longitude',
    ];

    protected $casts = [
        'year_established' => 'integer',
        'registration_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function coopAdminUser()
    {
        return $this->belongsTo(User::class, 'coop_admin_user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function farmers()
    {
        return $this->hasMany(FarmerProfile::class);
    }

    public function memberFarmers()
    {
        return $this->farmers()->where('membership_status', 'approved');
    }

    public function deliveryPersonnel()
    {
        return $this->hasMany(DriverProfile::class);
    }

    public function fieldPersonnel()
    {
        return $this->hasMany(User::class)->where('role', UserRole::FIELD_RECEIVING->value);
    }

    public function members()
    {
        return $this->hasMany(User::class);
    }

    public function trucks()
    {
        return $this->hasMany(Truck::class);
    }

    public function haulRequests()
    {
        return $this->hasMany(HaulRequest::class);
    }

    public function buyerOrders()
    {
        return $this->hasMany(BuyerOrder::class);
    }

    public function haulJobs()
    {
        return $this->hasMany(HaulJob::class);
    }

    public function availabilities()
    {
        return $this->hasMany(CropAvailability::class);
    }

    public function orders()
    {
        return $this->hasMany(BuyerOrder::class);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }


    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    public function isRequiresRevision(): bool
    {
        return $this->status === self::STATUS_REQUIRES_REVISION;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_REQUIRES_REVISION => 'Needs Revision',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_SUSPENDED => 'Suspended',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function fullAddress(): string
    {
        return trim(collect([
            $this->street_address, $this->barangay, $this->municipality, $this->city, $this->province,
        ])->filter()->implode(', '));
    }
}