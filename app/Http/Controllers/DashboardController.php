<?php

namespace App\Http\Controllers;

use App\Models\CropPriceHistory;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\PoolingJob;
use App\Models\ScraperStatus;
use App\Models\User;
use App\Services\Darfo12Service;
use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // ─── DA Price Data (shared across all dashboards) ──────
        $daService = app(Darfo12Service::class);
        ['latestDate' => $latestDaDate, 'daPrices' => $daPrices, 'priceTrends' => $priceTrends, 'scraperStatus' => $scraperStatus] = $daService->getDashboardData();

        // Initialize default counter fallback metrics
        $activeHarvestCount = 0;

        $availableHarvests = collect();
        $activeDispatchRuns = collect();
        $latestProposals = collect();

        if ($user->role === 'logistics_partner' && $logisticsProfile = $user->logisticsProfile) {

            $availableHarvests = Harvest::whereIn('status', HarvestStatus::logisticsVisible())
                ->whereHas('farmer.farmerProfile', function ($query) use ($logisticsProfile) {
                    $query->where('is_verified', true);

                    if ($logisticsProfile->logistics_type === 'cooperative') {
                        $query->where('affiliation_type', 'cooperative')
                            ->where('cooperative_id', $logisticsProfile->id);
                    } elseif ($logisticsProfile->logistics_type === 'company') {
                        $query->where('affiliation_type', 'independent');
                    }
                })
                ->with(['farmer.farmerProfile', 'crop', 'cropVariety', 'destination'])
                ->latest()
                ->take(5)
                ->get();

            $activeHarvestCount = $availableHarvests->count();

            $activeDispatchRuns = PoolingJob::where('logistics_profile_id', $logisticsProfile->id)
                ->whereIn('status', ['confirmed', 'in_progress'])
                ->with(['driver', 'truck', 'harvests.crop'])
                ->latest()
                ->take(3)
                ->get();

            $latestProposals = PoolingJob::where('logistics_profile_id', $logisticsProfile->id)
                ->where('status', 'pending')
                ->with(['truck', 'harvests.crop', 'harvests.cropVariety', 'harvests.farmer.farmerProfile'])
                ->latest()
                ->take(3)
                ->get();
        }

        /**
         * Driver dashboard metrics.
         * Scoped strictly to jobs assigned to the authenticated driver's user ID.
         */
        $driverJobs = collect();
        $completedJobs = 0;

        if ($user->role === 'driver') {
            $driverJobs = PoolingJob::where('driver_id', $user->id)
                ->whereIn('status', ['confirmed', 'in_progress'])
                ->with(['truck', 'harvests.crop', 'harvests.farmer', 'harvests.destination'])
                ->latest()
                ->take(10)
                ->get();

            $completedJobs = PoolingJob::where('driver_id', $user->id)
                ->where('status', 'completed')
                ->count();
        }

        // Farmer dashboard metrics — load once, derive counts from collections
        $activeHarvests = collect();
        $pendingProposals = collect();
        $activeShipments = collect();

        if ($user->role === 'farmer') {
            $activeHarvests = $user->harvests()->whereIn('status', [...HarvestStatus::buyerAvailable(), HarvestStatus::NEGOTIATING])->with(['crop', 'cropVariety', 'destination'])->latest()->take(3)->get();
            $pendingProposals = PoolingJob::whereHas('harvests', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('status', 'pending')->with(['logisticsProfile', 'truck', 'harvests.crop'])->latest()->take(5)->get();
            $activeShipments = PoolingJob::whereHas('harvests', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('status', 'in_progress')->with(['driver', 'truck', 'harvests.crop'])->latest()->take(5)->get();
        }

        return match ($user->role) {
            'farmer' => view('farmers.farmer-view', [
                'activeHarvests' => $activeHarvests,
                'activeHarvestsCount' => $activeHarvests->count(),
                'pendingProposals' => $pendingProposals,
                'pendingProposalsCount' => $pendingProposals->count(),
                'activeShipments' => $activeShipments,
                'activeShipmentsCount' => $activeShipments->count(),
                'daPrices' => $daPrices,
                'priceTrends' => $priceTrends,
                'latestDaDate' => $latestDaDate,
                'scraperStatus' => $scraperStatus,
            ]),

            'logistics_partner' => view('logistics.logistics-view', [
                'activeHarvestCount' => $activeHarvestCount,
                'availableHarvests' => $availableHarvests,
                'activeDispatchRuns' => $activeDispatchRuns,
                'latestProposals' => $latestProposals,
                'daPrices' => $daPrices,
                'priceTrends' => $priceTrends,
                'latestDaDate' => $latestDaDate,
                'scraperStatus' => $scraperStatus,
            ]),

            'admin' => app(AdminDashboardController::class)->index(),

            'driver' => view('driver.driver-view', [
                'jobs' => $driverJobs,
                'completedJobs' => $completedJobs,
            ]),

            'buyer' => app(BuyerController::class)->dashboard(),

            default => abort(403),
        };
    }

    public function fullPrices()
    {
        $daService = app(Darfo12Service::class);
        ['latestDate' => $latestDaDate, 'daPrices' => $daPrices, 'priceTrends' => $priceTrends, 'scraperStatus' => $scraperStatus] = $daService->getDashboardData();

        return view('prices.full', [
            'daPrices' => $daPrices,
            'priceTrends' => $priceTrends,
            'latestDate' => $latestDaDate,
            'scraperStatus' => $scraperStatus,
        ]);
    }

    public function refreshPrices()
    {
        // Guard against concurrent refreshes double-running the OCR pipeline.
        $lock = Cache::lock('darfo12.scrape', 600);

        if (! $lock->get()) {
            return redirect()->route('prices.full')->with('error', 'A price refresh is already in progress. Please wait a moment and try again.');
        }

        try {
            $previousDate = CropPriceHistory::where('source', 'da_rfo12')->max('source_date');

            Artisan::call('crops:scrape:darfo12');

            // Outcome derives from the status row the command just wrote, not from re-reading stored prices.
            $run = ScraperStatus::where('scraper_name', 'darfo12')->latest()->first();

            if (! $run || $run->status === 'failed') {
                return redirect()->route('prices.full')->with('error', 'Could not reach the DA RFO12 source. Existing prices are unchanged.');
            }

            $sourceDate = $run->source_date;

            if ($run->status === 'success' && $run->records_matched > 0 && ($previousDate === null || $sourceDate > $previousDate)) {
                return redirect()->route('prices.full')->with('success', 'Prices updated from the DA RFO12 source. Data as of '.Carbon::parse($sourceDate)->format('M j, Y').'.');
            }

            $asOf = $previousDate ? Carbon::parse($previousDate)->format('M j, Y') : 'now';

            return redirect()->route('prices.full')->with('warning', 'No new data from the DA RFO12 source yet. Prices remain as of '.$asOf.'.');
        } catch (\Throwable $e) {
            Log::error('Price refresh failed.', ['error' => $e->getMessage()]);

            return redirect()->route('prices.full')->with('error', 'Price refresh could not run (database unavailable or busy). Please try again shortly.');
        } finally {
            $lock->release();
        }
    }
}
