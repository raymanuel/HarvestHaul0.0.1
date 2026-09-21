<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\BuyerOrder;
use App\Models\FarmerProfile;
use App\Models\HaulJob;
use App\Models\HaulRequest;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $cooperative = Auth::user()->cooperative;

        $pendingMembers = FarmerProfile::where('cooperative_id', $cooperative->id)
            ->where('membership_status', 'pending')
            ->count();

        $pendingHaulRequests = HaulRequest::forCooperative($cooperative->id)->pending()->count();

        $scheduledJobs = HaulJob::where('cooperative_id', $cooperative->id)
            ->where('status', HaulJob::STATUS_SCHEDULED)
            ->count();

        $pendingOrders = BuyerOrder::forCooperative($cooperative->id)
            ->whereIn('status', [BuyerOrder::STATUS_SUBMITTED, BuyerOrder::STATUS_UNDER_REVIEW])
            ->count();

        $attention = collect();

        if ($pendingMembers > 0) {
            $attention->push([
                'label' => 'Farmer membership requests to review',
                'count' => $pendingMembers,
            ]);
        }

        if ($pendingHaulRequests > 0) {
            $attention->push([
                'label' => 'Farmer haul requests waiting to be scheduled',
                'count' => $pendingHaulRequests,
            ]);
        }

        if ($pendingOrders > 0) {
            $attention->push([
                'label' => 'Buyer orders waiting for your decision',
                'count' => $pendingOrders,
            ]);
        }

        return view('coop.dashboard', compact(
            'cooperative',
            'pendingMembers',
            'pendingHaulRequests',
            'scheduledJobs',
            'pendingOrders',
            'attention',
        ));
    }
}