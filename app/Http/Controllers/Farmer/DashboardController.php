<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\HaulRequest;
use App\Models\ReceivingRecord;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $haulRequests = HaulRequest::where('farmer_id', $user->id)
            ->with(['crop', 'cropVariety', 'packagingType'])
            ->latest()
            ->limit(10)
            ->get();

        $openHaulRequests = HaulRequest::where('farmer_id', $user->id)->pending()->count();

        $confirmedRecords = ReceivingRecord::where('farmer_id', $user->id)
            ->where('status', ReceivingRecord::STATUS_CONFIRMED)
            ->with(['crop', 'cropGrade'])
            ->latest()
            ->limit(10)
            ->get();

        $totalPayout = (float) ReceivingRecord::where('farmer_id', $user->id)
            ->where('status', ReceivingRecord::STATUS_CONFIRMED)
            ->sum('total_amount');

        $totalPaid = (float) \App\Models\FarmerPayment::whereHas('receivingRecord', function ($q) use ($user) {
            $q->where('farmer_id', $user->id)->where('status', ReceivingRecord::STATUS_CONFIRMED);
        })->sum('amount');
        $totalBalance = round($totalPayout - $totalPaid, 2);

        return view('farmer.dashboard', compact(
            'user',
            'haulRequests',
            'openHaulRequests',
            'confirmedRecords',
            'totalPayout',
            'totalPaid',
            'totalBalance',
        ));
    }
}