<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CropVariety extends Model
{
    use HasFactory;

    protected $fillable = [
        'crop_id',
        'name',
        'description',
        'status',
        'price_per_kg',
    ];

    protected $casts = [
        // status is an enum string, no cast needed
    ];

    // A variety belongs to one crop
    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    // A variety has many harvest listings
    public function harvests()
    {
        return $this->hasMany(Harvest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
