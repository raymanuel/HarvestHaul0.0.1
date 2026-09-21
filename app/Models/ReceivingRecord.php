<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceivingRecord extends Model
{
    public const STATUS_PENDING = 'pending_confirmation';
    public const STATUS_PRICED = 'priced';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'haul_job_id', 'haul_request_id', 'cooperative_id', 'farmer_id',
        'crop_id', 'crop_variety_id', 'crop_grade_id',
        'actual_sacks', 'actual_weight_kg', 'buying_price_per_kg', 'total_amount',
        'remarks', 'recorded_by', 'recording_role', 'confirmed_by', 'confirmed_at',
        'cancelled_by', 'cancelled_at', 'cancellation_reason', 'status',
        'facility_received_weight_kg', 'facility_verified_at', 'facility_verified_by',
        'variance_kg', 'variance_status', 'variance_notes',
    ];

    protected $casts = [
        'actual_weight_kg' => 'decimal:2',
        'buying_price_per_kg' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'facility_received_weight_kg' => 'decimal:2',
        'facility_verified_at' => 'datetime',
        'variance_kg' => 'decimal:2',
    ];

    public function haulJob()
    {
        return $this->belongsTo(HaulJob::class);
    }

    public function haulRequest()
    {
        return $this->belongsTo(HaulRequest::class);
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public function cropVariety()
    {
        return $this->belongsTo(CropVariety::class);
    }

    public function cropGrade()
    {
        return $this->belongsTo(CropGrade::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function availability()
    {
        return $this->hasOne(CropAvailability::class);
    }

    public function payments()
    {
        return $this->hasMany(FarmerPayment::class);
    }

    public function facilityVerifier()
    {
        return $this->belongsTo(User::class, 'facility_verified_by');
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isPriced(): bool
    {
        return $this->status === self::STATUS_PRICED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function totalPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function balanceDue(): float
    {
        return round((float) $this->total_amount - $this->totalPaid(), 2);
    }

    /** 'pending'|'partial'|'paid' — a farmer-payment status separate from procurement status. */
    public function paymentStatus(): string
    {
        if (! $this->isConfirmed() || $this->total_amount === null) {
            return 'pending';
        }

        $paid = $this->totalPaid();
        if ($paid <= 0) {
            return 'pending';
        }

        return $paid >= (float) $this->total_amount ? 'paid' : 'partial';
    }
}