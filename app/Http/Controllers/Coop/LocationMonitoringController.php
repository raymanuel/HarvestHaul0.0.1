<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\HaulJob;
use Illuminate\Support\Facades\Auth;

class LocationMonitoringController extends Controller
{
    public function index()
    {
        $jobs = HaulJob::with(['truck', 'stops'])
            ->where('cooperative_id', Auth::user()->cooperative_id)
            ->whereIn('status', [HaulJob::STATUS_SCHEDULED, HaulJob::STATUS_PICKED_UP])
            ->orderBy('scheduled_at')
            ->get();

        return view('coop.tracking.index', compact('jobs'));
    }

    public function show(HaulJob $haulJob)
    {
        $this->authorizeCoop($haulJob);

        $haulJob->load(['truck', 'deliveryPersonnel', 'cooperative', 'stops.haulRequest.farmer', 'stops.buyerOrder.buyer']);

        return view('coop.tracking.show', compact('haulJob'));
    }

    public function location(HaulJob $haulJob)
    {
        $this->authorizeCoop($haulJob);

        return $this->positionJson($haulJob);
    }

    private function authorizeCoop(HaulJob $haulJob): void
    {
        if ($haulJob->cooperative_id !== Auth::user()->cooperative_id) {
            abort(403, 'This trip does not belong to your cooperative.');
        }
    }

    public static function positionJson(HaulJob $haulJob)
    {
        $position = $haulJob->latestPosition();

        if (! $position) {
            return response()->json(['has_position' => false]);
        }

        return response()->json([
            'has_position' => true,
            'lat' => (float) $position->latitude,
            'lng' => (float) $position->longitude,
            'speed_kmh' => $position->speed_kmh,
            'posted_at' => $position->posted_at->toIso8601String(),
            'posted_at_human' => $position->posted_at->diffForHumans(),
        ]);
    }
}
