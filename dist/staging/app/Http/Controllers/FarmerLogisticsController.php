<?php

namespace App\Http\Controllers;

use App\Models\HarvestStatus;
use Illuminate\Support\Facades\Auth;

class FarmerLogisticsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $harvests = $user->harvests()
            ->whereIn('status', [
                HarvestStatus::SOLD,
                HarvestStatus::BOOKED,
                HarvestStatus::ASSIGNED,
                HarvestStatus::IN_PROGRESS,
                HarvestStatus::COMPLETED,
            ])
            ->with([
                'crop',
                'cropVariety',
                'poolingJobs.driver',
                'poolingJobs.truck',
                'negotiations.buyer',
            ])
            ->latest()
            ->paginate(20);

        return view('farmers.my-logistics', compact('harvests'));
    }
}
