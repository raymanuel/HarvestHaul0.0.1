<?php

namespace App\Http\Controllers;

use App\Models\MarketPrice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class WelcomeController extends Controller
{
    public function index()
    {
        return view('welcome', [
            // Public, unauthenticated page — must still render before migrations
            // have run (fresh install) rather than 500 on a missing table.
            'marketPrices' => Schema::hasTable('market_prices')
                ? MarketPrice::latestForEachCrop()->take(6)
                : new Collection(),
        ]);
    }
}
