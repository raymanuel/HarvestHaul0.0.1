<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CropCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        // status is an enum string, no cast needed
    ];

    // A category has many crops
    public function crops()
    {
        return $this->hasMany(Crop::class);
    }

    // Convenience: all varieties under this category (through crops)
    public function varieties()
    {
        return $this->hasManyThrough(CropVariety::class, Crop::class);
    }

    // Scope — only active categories
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
