<?php

namespace App\Http\Controllers;

use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\PoolingJob;
use App\Models\Negotiation;
use App\Models\NegotiationStatus;
use App\Models\Truck;
use App\Models\DriverProfile;
use App\Models\Invoice;
use App\Models\InvoiceStatus;
use App\Models\FuelLog;
use App\Models\User;
use App\Models\WeatherLog;
use App\Models\OutboundOrder;

use App\Http\Controllers\Admin\AdminDashboardController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // ─── Weather Data (shared across all dashboards) ──────
        $weatherData = $this->getWeatherForUser($user);

        // Initialize default counter fallback metrics
        $activeHarvestCount = 0;

        $availableHarvests = collect();
        $activeDispatchRuns = collect();
        $latestProposals = collect();
        $availableTrucks = 0;
        $totalTrucks = 0;
        $availableDrivers = 0;
        $totalDrivers = 0;
        $pendingInvoiceCount = 0;
        $overdueInvoiceCount = 0;
        $activeCustomerOrderCount = 0;

        if ($user->role === 'logistics_partner' && $logisticsProfile = $user->logisticsProfile) {

            // Harvests awaiting engagement (active / negotiating / partially_sold).
            // Sold and booked harvests are already committed and are not "available to pick up".
            $availableHarvests = Harvest::whereIn('status', [
                HarvestStatus::ACTIVE,
                HarvestStatus::NEGOTIATING,
                HarvestStatus::PARTIALLY_SOLD,
            ])
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

            $totalTrucks = Truck::where('logistics_profile_id', $logisticsProfile->id)->count();
            $availableTrucks = Truck::where('logistics_profile_id', $logisticsProfile->id)->where('status', 'available')->count();

            $totalDrivers = DriverProfile::where('partner_id', $logisticsProfile->id)->count();

            $activeDriverUserIds = PoolingJob::whereIn('status', ['pending', 'confirmed', 'in_progress'])
                ->whereNotNull('driver_id')
                ->pluck('driver_id');
            $availableDrivers = DriverProfile::where('partner_id', $logisticsProfile->id)
                ->where('employment_status', 'active')
                ->whereNotIn('user_id', $activeDriverUserIds)
                ->count();

            $pendingInvoiceCount = Invoice::where('logistics_profile_id', $logisticsProfile->id)
                ->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::OVERDUE])
                ->count();
            $overdueInvoiceCount = Invoice::where('logistics_profile_id', $logisticsProfile->id)
                ->where('status', InvoiceStatus::OVERDUE)
                ->count();

            $activeCustomerOrderCount = $logisticsProfile->isCooperative() ? OutboundOrder::forProfile($logisticsProfile->id)
                ->whereIn('status', ['confirmed', 'in_transit', 'awaiting_confirmation'])
                ->count() : 0;
        }

        /**
         * Driver dashboard metrics.
         * Scoped strictly to jobs assigned to the authenticated driver's user ID.
         */
        $driverJobs = collect();
        $completedToday = 0;
        $shiftReady = true;
        $shiftRestRemaining = '';
        $fuelThisWeekLiters = 0;
        $fuelThisWeekCost = 0;

        if ($user->role === 'driver') {
            $driverJobs = PoolingJob::where('driver_id', $user->id)
                ->whereIn('status', ['confirmed', 'in_progress'])
                ->with(['truck', 'harvests.crop', 'harvests.farmer', 'harvests.destination'])
                ->latest()
                ->take(10)
                ->get();

            $completedToday = PoolingJob::where('driver_id', $user->id)
                ->where('status', 'completed')
                ->whereDate('updated_at', today())
                ->count();

            $driverProfile = $user->driverProfile;
            if ($driverProfile?->last_shift_ended_at) {
                $restEnd = $driverProfile->last_shift_ended_at->copy()->addHours(8);
                if (now()->lt($restEnd)) {
                    $shiftReady = false;
                    $remaining = now()->diff($restEnd);
                    $shiftRestRemaining = $remaining->h . 'h ' . $remaining->i . 'm';
                }
            }

            $weekStart = now()->startOfWeek();
            $fuelThisWeekLiters = (float) FuelLog::where('driver_id', $user->id)
                ->where('created_at', '>=', $weekStart)
                ->sum('fuel_liters');
            $fuelThisWeekCost = (float) FuelLog::where('driver_id', $user->id)
                ->where('created_at', '>=', $weekStart)
                ->sum('cost');
        }

        // Farmer dashboard metrics — load once, derive counts from collections
        $activeHarvests = collect();
        $pendingProposals = collect();
        $monthlyRevenue = 0;
        $unreadMessagesCount = 0;

        if ($user->role === 'farmer') {
            $activeHarvests = $user->harvests()->whereIn('status', [...HarvestStatus::buyerAvailable(), HarvestStatus::NEGOTIATING])->with(['crop', 'cropVariety', 'destination'])->latest()->take(3)->get();
            $pendingProposals = PoolingJob::whereHas('harvests', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('status', 'pending')->with(['logisticsProfile', 'truck', 'harvests.crop'])->latest()->take(5)->get();

            $monthlyRevenue = (float) Negotiation::where('farmer_id', $user->id)
                ->where('status', NegotiationStatus::COMPLETED)
                ->whereMonth('last_activity_at', now()->month)
                ->whereYear('last_activity_at', now()->year)
                ->sum(DB::raw('negotiated_price * negotiated_volume'));

            $unreadMessagesCount = Negotiation::where('farmer_id', $user->id)
                ->whereIn('status', [NegotiationStatus::OPEN, NegotiationStatus::AGREED])
                ->whereColumn('last_activity_at', '>', 'farmer_last_read_at')
                ->count();
        }

        return match ($user->role) {
            'farmer' => view('farmers.farmer-view', [
                'activeHarvests' => $activeHarvests,
                'activeHarvestsCount' => $activeHarvests->count(),
                'pendingProposals' => $pendingProposals,
                'pendingProposalsCount' => $pendingProposals->count(),
                'monthlyRevenue' => $monthlyRevenue,
                'unreadMessagesCount' => $unreadMessagesCount,
                'weatherData' => $weatherData,
            ]),

            'logistics_partner' => view('logistics.logistics-view', [
                'activeHarvestCount' => $activeHarvestCount,
                'availableHarvests' => $availableHarvests,
                'activeDispatchRuns' => $activeDispatchRuns,
                'latestProposals' => $latestProposals,
                'logisticsIsCooperative' => $logisticsProfile->isCooperative(),
                'weatherData' => $weatherData,
                'availableTrucks' => $availableTrucks,
                'totalTrucks' => $totalTrucks,
                'availableDrivers' => $availableDrivers,
                'totalDrivers' => $totalDrivers,
                'pendingInvoiceCount' => $pendingInvoiceCount,
                'overdueInvoiceCount' => $overdueInvoiceCount,
                'activeCustomerOrderCount' => $activeCustomerOrderCount,
            ]),

            'admin' => app(AdminDashboardController::class)->index(),

            'driver' => view('driver.driver-view', [
                'jobs' => $driverJobs,
                'completedToday' => $completedToday,
                'shiftReady' => $shiftReady,
                'shiftRestRemaining' => $shiftRestRemaining,
                'fuelThisWeekLiters' => $fuelThisWeekLiters,
                'fuelThisWeekCost' => $fuelThisWeekCost,
            ]),

            'buyer' => app(BuyerController::class)->dashboard(),

            default => abort(403),
        };
    }

    public function fullPrices()
    {
        return view('prices.full');
    }

    /**
     * Latest useful weather for a given user. Preferences by proximity when the
     * user has coordinates (farmer farm / logistics office); otherwise falls back
     * to the most recent log anywhere. Returns a WeatherLog instance or null.
     */
    private function getWeatherForUser($user): ?WeatherLog
    {
        try {
            $lat = null;
            $lng = null;

            if ($user->role === 'farmer' && $user->farmerProfile?->latitude && $user->farmerProfile?->longitude) {
                $lat = (float) $user->farmerProfile->latitude;
                $lng = (float) $user->farmerProfile->longitude;
            } elseif ($user->role === 'logistics_partner' && $user->logisticsProfile?->latitude && $user->logisticsProfile?->longitude) {
                $lat = (float) $user->logisticsProfile->latitude;
                $lng = (float) $user->logisticsProfile->longitude;
            }

            $query = WeatherLog::orderByDesc('checked_at');

            if ($lat !== null && $lng !== null) {
                $query->whereBetween('latitude', [$lat - 0.5, $lat + 0.5])
                    ->whereBetween('longitude', [$lng - 0.5, $lng + 0.5]);
            }

            return $query->first();
        } catch (\Throwable $e) {
            Log::warning('Could not load weather data.', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
