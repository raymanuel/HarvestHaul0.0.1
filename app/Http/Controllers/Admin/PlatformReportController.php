<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuyerOrder;
use App\Models\BuyerProfile;
use App\Models\Cooperative;
use App\Models\HaulJob;
use App\Models\User;
use App\Models\UserRole;

class PlatformReportController extends Controller
{
    /**
     * Platform-level report (spec Module 19 / nav plan "Platform Reports")
     * — aggregate counts across every cooperative, kept deliberately to
     * summaries per the plan doc ("doesn't necessarily need to see every
     * operational detail"). Per-cooperative transaction detail stays
     * inside each coop's own Reports; this is a live snapshot, not a
     * period-bound report like the coop-side ones.
     */
    public function index()
    {
        $totalCooperatives = Cooperative::count();
        $approvedCooperatives = Cooperative::where('status', Cooperative::STATUS_APPROVED)->count();
        $totalFarmers = User::where('role', UserRole::FARMER->value)->count();
        $activeBuyers = BuyerProfile::where('status', BuyerProfile::STATUS_APPROVED)->count();
        $completedOrders = BuyerOrder::where('status', BuyerOrder::STATUS_COMPLETED)->count();
        $completedTrips = HaulJob::where('status', HaulJob::STATUS_COMPLETED)->count();
        $transactionVolume = (float) BuyerOrder::where('status', BuyerOrder::STATUS_COMPLETED)->sum('total_amount');

        return view('admin.reports.index', compact(
            'totalCooperatives', 'approvedCooperatives', 'totalFarmers',
            'activeBuyers', 'completedOrders', 'completedTrips', 'transactionVolume'
        ));
    }
}
