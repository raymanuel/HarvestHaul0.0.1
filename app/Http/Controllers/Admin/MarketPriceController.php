<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\CropVariety;
use App\Models\MarketPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketPriceController extends Controller
{
    public function index()
    {
        return view('admin.market-prices.index', [
            'latest' => MarketPrice::latestForEachCrop(),
            'history' => MarketPrice::with(['crop', 'cropVariety', 'recorder'])->orderByDesc('price_date')->orderByDesc('id')->limit(50)->get(),
            'crops' => Crop::active()->orderBy('name')->get(),
            'varieties' => CropVariety::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'crop_id' => 'required|exists:crops,id',
            'crop_variety_id' => 'nullable|exists:crop_varieties,id',
            'low_price_per_kg' => 'required|numeric|min:0',
            'high_price_per_kg' => 'required|numeric|gte:low_price_per_kg',
            'common_price_per_kg' => 'required|numeric|min:0',
            'dpi_price_per_kg' => 'nullable|numeric|min:0',
            'source' => 'nullable|string|max:64',
            'price_date' => 'required|date|before_or_equal:today',
        ]);

        MarketPrice::create($data + [
            'source' => $data['source'] ?? 'DA RFO12',
            'recorded_by' => Auth::id(),
        ]);

        return back()->with('success', 'Market price recorded.');
    }

    public function destroy(MarketPrice $marketPrice)
    {
        $marketPrice->delete();

        return back()->with('success', 'Market price entry removed.');
    }
}
