<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Models\HaulJob;
use App\Models\ReceivingRecord;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $cooperativeId = Auth::user()->cooperative_id;

        $jobsToday = HaulJob::where('cooperative_id', $cooperativeId)
            ->whereDate('pickup_date', today())
            ->with(['haulRequest.crop', 'haulRequest.farmer'])
            ->get();

        $pendingRecords = ReceivingRecord::where('cooperative_id', $cooperativeId)
            ->where('status', ReceivingRecord::STATUS_PENDING)
            ->count();

        return view('field.dashboard', compact('jobsToday', 'pendingRecords'));
    }
}