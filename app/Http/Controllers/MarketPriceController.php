<?php

namespace App\Http\Controllers;

use App\Models\CropPriceHistory;
use Illuminate\Http\JsonResponse;

class MarketPriceController extends Controller
{
    public function getMarketPrice(string $cropName): JsonResponse
    {
        $price = CropPriceHistory::getLatestForCrop($cropName);

        if (!$price) {
            return response()->json(null);
        }

        return response()->json([
            'commodity' => $price->commodity_name,
            'low'       => $price->low_price,
            'high'      => $price->high_price,
            'common'    => $price->common_price,
            'dpi'       => $price->price_per_kg,
            'date'      => $price->source_date->format('M d, Y'),
        ]);
    }
}
