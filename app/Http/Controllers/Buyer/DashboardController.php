<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\BuyerOrder;
use App\Models\CropAvailability;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $profile = $user->buyerProfile;

        $openOrders = BuyerOrder::forBuyer($user->id)
            ->whereIn('status', [
                BuyerOrder::STATUS_SUBMITTED,
                BuyerOrder::STATUS_UNDER_REVIEW,
                BuyerOrder::STATUS_ACCEPTED,
                BuyerOrder::STATUS_PREPARING,
                BuyerOrder::STATUS_READY_FOR_DELIVERY,
                BuyerOrder::STATUS_OUT_FOR_DELIVERY,
            ])
            ->count();

        $recentOrders = BuyerOrder::forBuyer($user->id)
            ->with('cooperative')
            ->latest()
            ->limit(10)
            ->get();

        $availableListings = CropAvailability::availableForSale()
            ->with(['cooperative', 'crop', 'cropVariety', 'cropGrade'])
            ->latest()
            ->limit(10)
            ->get();

        return view('buyer.dashboard', compact(
            'profile',
            'openOrders',
            'recentOrders',
            'availableListings',
        ));
    }
}