<?php

namespace App\Http\Controllers;

use App\Models\PoolingJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CostLedgerController extends Controller
{
    /**
     * List all pooling jobs for this logistics partner (ledger index).
     */
    public function index()
    {
        $user    = Auth::user();
        $profile = $user->logisticsProfile;

        if (!$profile) abort(403);

        $jobs = PoolingJob::where('logistics_profile_id', $profile->id)
            ->with(['truck', 'harvests'])
            ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
            ->latest()
            ->paginate(15);

        return view('logistics.cost-ledger-index', compact('jobs'));
    }

    /**
     * Show the proportional cost breakdown for a pooling job.
     * Accessible by:
     *   - The logistics partner who owns the job
     *   - A farmer whose harvest is included in the job
     */
    public function show(PoolingJob $poolingJob)
    {
        $user = Auth::user();

        // Authorization: logistics owner OR participating farmer
        $isOwner = $user->role === 'logistics_partner'
            && $user->logisticsProfile
            && $poolingJob->logistics_profile_id === $user->logisticsProfile->id;

        $isFarmer = $user->role === 'farmer'
            && $poolingJob->harvests()->where('user_id', $user->id)->exists();

        if (!$isOwner && !$isFarmer) {
            abort(403, 'Unauthorized access to cost ledger.');
        }

        // Load all harvests with pivot data + farmer info
        $poolingJob->load([
            'harvests' => function ($q) {
                $q->with(['farmer', 'crop', 'cropVariety', 'destination']);
            },
            'logisticsProfile',
            'truck',
        ]);

        // Build per-farmer cost ledger entries
        $ledgerEntries = $poolingJob->harvests->map(function ($harvest) use ($poolingJob) {
            $harvestKg    = (float) $harvest->pivot->quantity_kg;
            $totalKg      = (float) $poolingJob->total_kg;
            $basePrice    = (float) ($poolingJob->negotiated_price ?? $poolingJob->price_reference ?? 0);
            $proportion   = $totalKg > 0 ? $harvestKg / $totalKg : 0;

            // Use stored cost_share if available, otherwise compute on-the-fly
            $costShare = $harvest->pivot->cost_share !== null
                ? (float) $harvest->pivot->cost_share
                : round($basePrice * $proportion, 2);

            return [
                'farmer_name'   => $harvest->farmer->name ?? '—',
                'crop'          => $harvest->crop->name ?? $harvest->crop_type ?? '—',
                'variety'       => $harvest->cropVariety->name ?? '—',
                'quantity_kg'   => $harvestKg,
                'proportion'    => round($proportion * 100, 1),
                'cost_share'    => $costShare,
                'destination'   => $harvest->destination->name ?? $harvest->destination_address ?? '—',
                'pickup_order'  => $harvest->pivot->pickup_order,
            ];
        })->sortBy('pickup_order')->values();

        $totalPrice  = (float) ($poolingJob->negotiated_price ?? $poolingJob->price_reference ?? 0);
        $sumOfShares = $ledgerEntries->sum('cost_share');

        return view('logistics.cost-ledger', compact(
            'poolingJob', 'ledgerEntries', 'totalPrice', 'sumOfShares', 'isOwner'
        ));
    }
}
