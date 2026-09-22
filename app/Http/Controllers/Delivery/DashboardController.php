<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\HaulJob;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::user()->id;

        $haulJobs = HaulJob::where('delivery_personnel_id', $userId)
            ->whereIn('status', [HaulJob::STATUS_SCHEDULED, HaulJob::STATUS_PICKED_UP])
            ->with(['haulRequest.crop', 'haulRequest.farmer', 'truck'])
            ->orderBy('scheduled_at')
            ->get();

        $deliveries = Delivery::where('delivery_personnel_id', $userId)
            ->whereIn('status', [Delivery::STATUS_SCHEDULED, Delivery::STATUS_IN_TRANSIT])
            ->with(['order.buyer', 'truck'])
            ->orderBy('scheduled_at')
            ->get();

        return view('delivery.dashboard', compact('haulJobs', 'deliveries'));
    }
}