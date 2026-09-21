<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HaulJob extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const JOB_TYPE_PICKUP = 'pickup';
    public const JOB_TYPE_DELIVERY = 'delivery';

    protected $fillable = [
        'haul_request_id', 'cooperative_id', 'job_type',
        'delivery_personnel_id', 'field_personnel_id', 'truck_id',
        'pickup_date', 'scheduled_at', 'status', 'completed_at',
        'route_distance_km', 'route_duration_min', 'route_geometry',
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'route_distance_km' => 'decimal:2',
        'route_duration_min' => 'decimal:2',
        'route_geometry' => 'array',
    ];

    public function haulRequest()
    {
        return $this->belongsTo(HaulRequest::class);
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function deliveryPersonnel()
    {
        return $this->belongsTo(User::class, 'delivery_personnel_id');
    }

    public function fieldPersonnel()
    {
        return $this->belongsTo(User::class, 'field_personnel_id');
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function stops()
    {
        return $this->hasMany(HaulJobStop::class)->orderBy('sequence_no');
    }

    public function receivingRecords()
    {
        return $this->hasMany(ReceivingRecord::class);
    }

    public function tracking()
    {
        return $this->morphMany(TrackingRecord::class, 'job');
    }

    public function latestPosition(): ?TrackingRecord
    {
        return $this->tracking()->latest('posted_at')->first();
    }

    public function isActiveForTracking(): bool
    {
        return in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_PICKED_UP], true);
    }

    public function isDelivery(): bool
    {
        return $this->job_type === self::JOB_TYPE_DELIVERY;
    }
}