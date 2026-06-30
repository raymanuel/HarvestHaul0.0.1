<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Crop extends Model
{
    use HasFactory;

    protected $fillable = [
        'crop_category_id',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        // status is an enum string, no cast needed
    ];

    // A crop belongs to one category
    public function category()
    {
        return $this->belongsTo(CropCategory::class, 'crop_category_id');
    }

    // A crop has many varieties
    public function varieties()
    {
        return $this->hasMany(CropVariety::class);
    }

    // A crop has many harvest listings
    public function harvests()
    {
        return $this->hasMany(Harvest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
