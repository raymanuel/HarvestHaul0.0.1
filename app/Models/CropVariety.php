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
        'is_active',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
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
        return $query->where('is_active', true);
    }
}
