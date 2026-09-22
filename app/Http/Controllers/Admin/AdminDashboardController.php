<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuyerProfile;
use App\Models\Cooperative;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $pendingCooperatives = Cooperative::whereIn('status', [
            Cooperative::STATUS_PENDING,
            Cooperative::STATUS_UNDER_REVIEW,
        ])->count();

        $approvedCooperatives = Cooperative::where('status', Cooperative::STATUS_APPROVED)->count();
        $pendingBuyers = BuyerProfile::where('status', BuyerProfile::STATUS_PENDING)->count();

        $recentCooperatives = Cooperative::whereIn('status', [
            Cooperative::STATUS_PENDING,
            Cooperative::STATUS_UNDER_REVIEW,
        ])->latest()->limit(5)->get();

        $attention = collect();

        if ($pendingCooperatives > 0) {
            $attention->push([
                'label' => 'Cooperative applications waiting for review',
                'count' => $pendingCooperatives,
                'href' => route('admin.cooperatives.index'),
            ]);
        }

        if ($pendingBuyers > 0) {
            $attention->push([
                'label' => 'Buyer accounts waiting for verification',
                'count' => $pendingBuyers,
                'href' => route('admin.buyers.index'),
            ]);
        }

        return view('admin.dashboard', compact(
            'pendingCooperatives',
            'approvedCooperatives',
            'pendingBuyers',
            'recentCooperatives',
            'attention',
        ));
    }
}