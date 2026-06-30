<?php

namespace App\Http\Controllers;

use App\Models\Harvest;
use App\Models\PoolingJob;
use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PredictorController extends Controller
{
    /**
     * Farmer Yield Predictor
     * Estimates next harvest window based on past harvest dates per crop type.
     * Uses simple average-cycle heuristic — no ML required.
     */
    public function farmerPredict()
    {
        $user = Auth::user();
        if ($user->role !== 'farmer') abort(403);

        // Pull this farmer's completed harvests ordered by date
        $harvests = Harvest::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'active'])
            ->with(['crop', 'cropVariety'])
            ->whereNotNull('harvest_date')
            ->orderBy('harvest_date', 'desc')
            ->get();

        // Group by crop and compute average cycle days between harvests
        $predictions = $harvests
            ->groupBy('crop_id')
            ->map(function ($cropHarvests, $cropId) {
                $sorted = $cropHarvests->sortBy('harvest_date')->values();
                $cropName = $sorted->first()->crop->name ?? 'Unknown Crop';

                // Compute average days between consecutive harvests
                $gaps = [];
                for ($i = 1; $i < $sorted->count(); $i++) {
                    $prev = \Carbon\Carbon::parse($sorted[$i - 1]->harvest_date);
                    $curr = \Carbon\Carbon::parse($sorted[$i]->harvest_date);
                    $gaps[] = abs($curr->diffInDays($prev));
                }

                $avgCycleDays = count($gaps) > 0 ? round(array_sum($gaps) / count($gaps)) : null;
                $lastHarvest  = \Carbon\Carbon::parse($sorted->last()->harvest_date);
                $nextEstimate = $avgCycleDays ? $lastHarvest->copy()->addDays($avgCycleDays) : null;

                // Total yield history
                $totalYield   = $cropHarvests->sum(fn($h) => (float) $h->quantity_kg);
                $avgYield     = $cropHarvests->count() > 0
                    ? round($totalYield / $cropHarvests->count(), 1)
                    : 0;

                return [
                    'crop'           => $cropName,
                    'harvest_count'  => $sorted->count(),
                    'last_harvest'   => $lastHarvest->format('M d, Y'),
                    'avg_cycle_days' => $avgCycleDays,
                    'next_estimate'  => $nextEstimate?->format('M d, Y'),
                    'next_estimate_raw' => $nextEstimate,
                    'days_until'     => $nextEstimate ? max(0, (int) now()->diffInDays($nextEstimate, false)) : null,
                    'avg_yield_kg'   => $avgYield,
                    'total_yield_kg' => round($totalYield, 1),
                ];
            })
            ->values();

        // Active listings
        $activeCount    = Harvest::where('user_id', $user->id)->where('status', 'active')->count();
        $completedCount = Harvest::where('user_id', $user->id)->where('status', 'completed')->count();

        return view('farmers.predictor', compact(
            'predictions', 'activeCount', 'completedCount'
        ));
    }

    /**
     * Logistics Fleet Predictor
     * Estimates how many trucks needed given current active harvests
     * vs historical average kg per completed pooling job.
     */
    public function logisticsPredict()
    {
        $user = Auth::user();
        if ($user->role !== 'logistics_partner') abort(403);

        $profile = $user->logisticsProfile;
        if (!$profile) abort(403);

        // Completed jobs for this partner — compute avg kg/job
        $completedJobs = PoolingJob::where('logistics_profile_id', $profile->id)
            ->where('status', 'completed')
            ->get();

        $avgKgPerJob = $completedJobs->count() > 0
            ? round($completedJobs->avg(fn($j) => (float) $j->total_kg), 1)
            : null;

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
                'status'    => $j->status,
                'total_kg'  => (float) $j->total_kg,
                'farms'     => $j->farm_count,
                'truck'     => $j->truck->truck_name ?? '—',
                'completed' => $j->completed_at?->format('M d'),
            ]);

        return view('logistics.predictor', compact(
            'avgKgPerJob', 'activeHarvestsKg', 'totalTrucks', 'availableTrucks',
            'avgTruckCap', 'trucksNeeded', 'surplusShortage', 'completedJobs',
            'recentJobs'
        ));
    }
}
