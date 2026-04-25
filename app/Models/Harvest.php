<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Harvest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'driver_id',
        'crop_category_id',
        'crop_id',
        'crop_variety_id',
        'crop_type',        // legacy — remove after cleanup migration
        'variety',          // legacy — remove after cleanup migration
        'quantity_kg',
        'unit',
        'status',
        'notes',
        'harvest_date',
        'quality_grade',
        'packaging_type',
        'latitude',
        'longitude',
        'cluster_id',
    ];

    protected $casts = [
        'quantity_kg'  => 'decimal:2',
        'latitude'     => 'decimal:8',
        'longitude'    => 'decimal:8',
        'harvest_date' => 'date',
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function farmer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function cropCategory()
    {
        return $this->belongsTo(CropCategory::class, 'crop_category_id');
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class, 'crop_id');
    }

    public function cropVariety()
    {
        return $this->belongsTo(CropVariety::class, 'crop_variety_id');
    }

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('driver_id');
    }

    public function scopeWithLocation($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }
}
