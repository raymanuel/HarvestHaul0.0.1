<?php

namespace App\Http\Controllers;

use App\Services\Darfo12Service;
use Illuminate\Http\JsonResponse;

class MarketPriceController extends Controller
{
    public function getMarketPrice(string $cropName): JsonResponse
    {
        $price = app(Darfo12Service::class)->getLatestCropPrice($cropName);

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
