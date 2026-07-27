<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CropPriceHistory extends Model
{
    use HasFactory;

    protected $table = 'crop_price_history';

    protected $fillable = [
        'crop_id',
        'commodity_name',
        'commodity_category',
        'source',
        'source_date',
        'price_per_kg',
        'low_price',
        'high_price',
        'common_price',
    ];

    protected $casts = [
        'source_date' => 'date',
        'price_per_kg' => 'float',
        'low_price' => 'float',
        'high_price' => 'float',
        'common_price' => 'float',
    ];

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    /**
     * Get the latest market price for a crop by name (LIKE match).
     */
    public static function getLatestForCrop(string $cropName): ?self
    {
        $latestDate = static::where('source', 'da_rfo12')->max('source_date');
        if (!$latestDate) {
            return null;
        }

        return static::where('source', 'da_rfo12')
            ->where('source_date', $latestDate)
            ->where('commodity_name', 'LIKE', '%' . $cropName . '%')
            ->first();
    }
}
