<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'crop_id', 'crop_variety_id',
        'low_price_per_kg', 'high_price_per_kg', 'common_price_per_kg', 'dpi_price_per_kg',
        'source', 'price_date', 'recorded_by',
    ];

    protected $casts = [
        'price_date' => 'date',
    ];

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public function cropVariety()
    {
        return $this->belongsTo(CropVariety::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public static function latestFor(int $cropId, ?int $cropVarietyId = null): ?self
    {
        if ($cropVarietyId) {
            $specific = static::where('crop_id', $cropId)
                ->where('crop_variety_id', $cropVarietyId)
                ->orderByDesc('price_date')
                ->first();

            if ($specific) {
                return $specific;
            }
        }

        return static::where('crop_id', $cropId)
            ->whereNull('crop_variety_id')
            ->orderByDesc('price_date')
            ->first();
    }

    public static function latestForEachCrop()
    {
        return static::with(['crop.category', 'cropVariety'])
            ->orderByDesc('price_date')
            ->orderByDesc('id')
            ->get()
            ->unique(fn ($price) => $price->crop_id.'-'.$price->crop_variety_id)
            ->values();
    }
}
