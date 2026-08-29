<?php

namespace App\Http\Controllers;

use App\Models\Harvest;
use App\Models\PoolingJob;
use Illuminate\Support\Facades\Auth;

class CapacityController extends Controller
{
    /**
     * Fleet Capacity
     * Shows how many trucks the current active harvest load requires,
     * based on the historical average kg per completed pooling job.
     */
    public function capacity()
    {
        $user = Auth::user();
        if ($user->role !== 'logistics_partner') abort(403);

        $profile = $user->logisticsProfile;
        if (!$profile) abort(403);

        // Completed jobs for this partner — compute avg kg/job at DB level
        $avgKgPerJob = PoolingJob::where('logistics_profile_id', $profile->id)
            ->where('status', 'completed')
            ->avg('total_kg');
        $avgKgPerJob = $avgKgPerJob ? round($avgKgPerJob, 1) : null;

        // Keep collection for view compatibility (used for count + display)
        $completedJobs = PoolingJob::where('logistics_profile_id', $profile->id)
            ->where('status', 'completed')
            ->take(200)
            ->get();

        // Active harvest pool visible to this partner
        $activeHarvestsKg = Harvest::where('status', 'active')
            ->whereHas('farmer.farmerProfile', function ($q) use ($profile) {
                $q->where('is_verified', true);
                if ($profile->logistics_type === 'cooperative') {
                    $q->where('affiliation_type', 'cooperative')
                      ->where('cooperative_id', $profile->id);
                } elseif ($profile->logistics_type === 'company') {
                    $q->where('affiliation_type', 'independent');
                }
            })
            ->sum('quantity_kg');

        // Truck fleet stats
        $totalTrucks     = $profile->trucks()->count();
        $availableTrucks = $profile->trucks()->where('status', 'available')->count();
        $avgTruckCap     = $profile->trucks()->avg('capacity_kg') ?? 0;

        // Estimate: trucks needed = active harvest kg / avg kg per job
        // Fallback: use avg truck capacity if no job history
        $divisor        = $avgKgPerJob ?? ($avgTruckCap ?: 1000);
        $trucksNeeded   = $activeHarvestsKg > 0 ? ceil($activeHarvestsKg / $divisor) : 0;
        $surplusShortage = $availableTrucks - $trucksNeeded;

        // Recent job history for chart/display
        $recentJobs = PoolingJob::where('logistics_profile_id', $profile->id)
            ->whereIn('status', ['completed', 'confirmed', 'in_progress'])
            ->with('truck')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($j) => [
                'id'        => $j->id,
                'status'    => $j->status->value,
                'total_kg'  => (float) $j->total_kg,
                'farms'     => $j->farm_count,
                'truck'     => $j->truck->truck_name ?? '—',
                'completed' => $j->completed_at?->format('M d'),
            ]);

        return view('logistics.capacity', compact(
            'avgKgPerJob', 'activeHarvestsKg', 'totalTrucks', 'availableTrucks',
            'avgTruckCap', 'trucksNeeded', 'surplusShortage', 'completedJobs',
            'recentJobs'
        ));
    }
}
