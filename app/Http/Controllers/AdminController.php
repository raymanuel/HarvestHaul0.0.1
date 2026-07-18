<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\Negotiation;
use App\Services\Darfo12Service;
use App\Http\Requests\AdminStoreUserRequest;
use App\Http\Requests\AdminUpdateUserRequest;
use App\Http\Requests\UpdateBaselinePriceRequest;

class AdminController extends Controller
{
    private function adminOnly()
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return true;
        }
        abort(403, 'Unauthorized');
    }

    // -------------------------------------------------------
    // Dashboard Overview
    public function index()
    {
        $this->adminOnly();

        $pendingFarmersList = User::where('role', 'farmer')
            ->whereHas('farmerProfile', fn($q) => $q->where('is_verified', false))
            ->with('farmerProfile')
            ->latest()
            ->take(20)
            ->get();

        $pendingLogisticsList = User::where('role', 'logistics_partner')
            ->whereHas('logisticsProfile', fn($q) => $q->where('is_verified', false))
            ->with('logisticsProfile')
            ->latest()
            ->take(20)
            ->get();

        $pendingFarmerDocsList = \App\Models\FarmerDocument::where('status', 'pending')
            ->with('user')
            ->latest()
            ->take(20)
            ->get();

        $pendingLogisticsDocsList = \App\Models\LogisticsDocument::where('status', 'pending')
            ->with('user')
            ->latest()
            ->take(20)
            ->get();

        $pendingBuyersList = User::where('role', 'buyer')
            ->whereHas('buyerProfile', fn($q) => $q->where('is_verified', false))
            ->with('buyerProfile')
            ->latest()
            ->take(20)
            ->get();

        $userCounts = User::whereNot('role', 'admin')
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $harvestCounts = Harvest::whereIn('status', ['active'])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // ─── DA Price Data ──────
        $daService = app(Darfo12Service::class);
        ['latestDate' => $latestDaDate, 'daPrices' => $daPrices, 'priceTrends' => $priceTrends, 'scraperStatus' => $scraperStatus] = $daService->getDashboardData();

        return view('admin.admin-view', [
            'totalUsers'               => $userCounts->sum(),
            'totalFarmers'             => $userCounts->get('farmer', 0),
            'totalLogistics'           => $userCounts->get('logistics_partner', 0),
            'totalDrivers'             => $userCounts->get('driver', 0),
            'totalBuyers'              => $userCounts->get('buyer', 0),
            'pendingFarmers'           => $pendingFarmersList->count(),
            'pendingLogistics'         => $pendingLogisticsList->count(),
            'pendingBuyers'            => $pendingBuyersList->count(),
            'activeHarvests'           => $harvestCounts->get('active', 0),
            'pendingHarvests'          => $harvestCounts->get('pending', 0),
            'recentLogs'               => AuditLog::with('admin')->latest()->take(5)->get(),
            'pendingFarmersList'       => $pendingFarmersList,
            'pendingLogisticsList'     => $pendingLogisticsList,
            'pendingBuyersList'        => $pendingBuyersList,
            'pendingFarmerDocsList'    => $pendingFarmerDocsList,
            'pendingLogisticsDocsList' => $pendingLogisticsDocsList,
            'daPrices'                 => $daPrices,
            'priceTrends'              => $priceTrends,
            'latestDaDate'             => $latestDaDate,
            'scraperStatus'            => $scraperStatus,
        ]);
    }

    // -------------------------------------------------------
    // User Management
    public function users()
    {
        $this->adminOnly();

        $users = User::with(['farmerProfile', 'logisticsProfile', 'driverProfile.partner.user'])
                     ->orderBy('role')
                     ->paginate(50);

        $cooperatives = \App\Models\LogisticsProfile::with('user')->orderBy('company_name')->take(100)->get();

        return view('admin.users', compact('users', 'cooperatives'));
    }

    public function toggleStatus(Request $request, User $user)
    {
        $this->adminOnly();

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';

        // Archiving flow — check for active harvests (including partially_sold)
        if ($newStatus === 'inactive' && $user->role === 'farmer') {
            $activeHarvestIds = Harvest::where('user_id', $user->id)
                ->whereIn('status', HarvestStatus::buyerAvailable())
                ->pluck('id');

            if ($activeHarvestIds->isNotEmpty() && !$request->boolean('force')) {
                return response()->json([
                    'requires_confirmation' => true,
                    'active_harvest_count'  => $activeHarvestIds->count(),
                    'user_name'             => $user->name,
                    'user_id'               => $user->id,
                ]);
            }

            // Force confirmed — cancel all active and partially_sold harvests
            if ($activeHarvestIds->isNotEmpty()) {
                $harvestIds = $activeHarvestIds->toArray();

                Harvest::whereIn('id', $harvestIds)->update(['status' => 'cancelled']);

                // Cancel any OPEN/AGREED negotiations on these harvests
                Negotiation::whereIn('harvest_id', $harvestIds)
                    ->whereIn('status', ['OPEN', 'AGREED'])
                    ->update(['status' => 'CANCELLED']);

                AuditLog::create([
                    'admin_id'    => Auth::id(),
                    'action'      => 'cancelled_harvests_on_archive',
                    'target_type' => 'farmer',
                    'target_id'   => $user->id,
                    'notes'       => "Cancelled {$activeHarvestIds->count()} active harvest post(s) and their negotiations due to account archiving of {$user->name}.",
                ]);
            }
        }

        $user->update(['status' => $newStatus]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => $newStatus === 'inactive' ? 'archived_user' : 'reactivated_user',
            'target_type' => $user->role,
            'target_id'   => $user->id,
            'notes'       => "User {$user->name} status changed to {$newStatus}.",
        ]);

        // JSON response for AJAX, redirect for normal POST
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'new_status' => $newStatus]);
        }

        return back()->with('success', "User {$user->name} marked as {$newStatus}.");
    }

    // -------------------------------------------------------
    // Harvest Oversight
    public function harvests()
    {
        $this->adminOnly();

        $harvests = Harvest::with(['farmer', 'crop', 'cropVariety', 'cropCategory'])
            ->orderByRaw("FIELD(status, 'active', 'completed', 'cancelled')")
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.harvests', compact('harvests'));
    }

    // -------------------------------------------------------
    // Driver Management
    public function drivers()
    {
        $this->adminOnly();

        $drivers = User::where('role', 'driver')
            ->with(['logisticsProfile', 'driverProfile'])
            ->orderBy('status')
            ->paginate(50);

        return view('admin.drivers', compact('drivers'));
    }

    public function verifyDriverIdentity(User $user)
    {
        $this->adminOnly();

        if ($user->role !== 'driver') {
            return back()->with('error', 'User is not a driver.');
        }

        $user->driverProfile()->update(['identity_verified' => true]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'verified_driver_identity',
            'target_type' => 'driver',
            'target_id'   => $user->id,
            'notes'       => "Driver identity verified for {$user->name}.",
        ]);

        \App\Models\Notification::create([
            'user_id' => $user->id,
            'title' => 'Identity Verified',
            'message' => 'Your identity has been verified by the administrator.',
            'link' => route('dashboard'),
        ]);

        return back()->with('success', "{$user->name}'s identity has been verified.");
    }

    public function rejectDriverIdentity(User $user)
    {
        $this->adminOnly();

        if ($user->role !== 'driver') {
            return back()->with('error', 'User is not a driver.');
        }

        $user->driverProfile()->update(['identity_verified' => false]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'rejected_driver_identity',
            'target_type' => 'driver',
            'target_id'   => $user->id,
            'notes'       => "Driver identity verification rejected for {$user->name}.",
        ]);

        \App\Models\Notification::create([
            'user_id' => $user->id,
            'title' => 'Identity Verification Rejected',
            'message' => 'Your identity verification was rejected. Please upload a clearer ID photo.',
            'link' => route('dashboard'),
        ]);

        return back()->with('success', "{$user->name}'s identity verification has been rejected.");
    }

    // -------------------------------------------------------
    // Farmer Verification
    public function farmers()
    {
        $this->adminOnly();

        $farmers = User::where('role', 'farmer')
                       ->with('farmerProfile')
                       ->paginate(50);

        return view('admin.farmers', compact('farmers'));
    }

    public function verifyFarmer(User $user)
    {
        $this->adminOnly();

        $user->farmerProfile()->update(['is_verified' => true]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'verified_farmer',
            'target_type' => 'farmer',
            'target_id'   => $user->id,
            'notes'       => "Farmer profile approved for {$user->name}.",
        ]);

        \App\Models\Notification::create([
            'user_id' => $user->id,
            'title' => 'Profile Verified',
            'message' => 'Your farmer profile has been verified by the administrator.',
            'link' => route('dashboard'),
        ]);

        return back()->with('success', "{$user->name} has been verified.");
    }

    public function rejectFarmer(User $user)
    {
        $this->adminOnly();

        $user->farmerProfile()->update(['is_verified' => false]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'rejected_farmer',
            'target_type' => 'farmer',
            'target_id'   => $user->id,
            'notes'       => "Farmer profile rejected for {$user->name}.",
        ]);

        \App\Models\Notification::create([
            'user_id' => $user->id,
            'title' => 'Profile Verification Rejected',
            'message' => 'Your farmer profile verification was rejected by the administrator.',
            'link' => route('dashboard'),
        ]);

        return back()->with('success', "{$user->name} has been rejected.");
    }

    // -------------------------------------------------------
    // Logistics Partner Verification
    public function logistics()
    {
        $this->adminOnly();

        $partners = User::where('role', 'logistics_partner')
                        ->with('logisticsProfile')
                        ->paginate(50);

        return view('admin.logistics', compact('partners'));
    }

    public function verifyLogistics(User $user)
    {
        $this->adminOnly();

        $user->logisticsProfile()->update(['is_verified' => true]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'verified_logistics',
            'target_type' => 'logistics_partner',
            'target_id'   => $user->id,
            'notes'       => "Logistics partner approved for {$user->name}.",
        ]);

        \App\Models\Notification::create([
            'user_id' => $user->id,
            'title' => 'Profile Verified',
            'message' => 'Your logistics partner profile has been verified by the administrator.',
            'link' => route('dashboard'),
        ]);

        return back()->with('success', "{$user->name} has been verified.");
    }

    public function rejectLogistics(User $user)
    {
        $this->adminOnly();

        $user->logisticsProfile()->update(['is_verified' => false]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'rejected_logistics',
            'target_type' => 'logistics_partner',
            'target_id'   => $user->id,
            'notes'       => "Logistics partner rejected for {$user->name}.",
        ]);

        \App\Models\Notification::create([
            'user_id' => $user->id,
            'title' => 'Profile Verification Rejected',
            'message' => 'Your logistics partner profile verification was rejected by the administrator.',
            'link' => route('dashboard'),
        ]);

        return back()->with('success', "{$user->name} has been rejected.");
    }

    // -------------------------------------------------------
    // Buyer Verification
    public function buyers()
    {
        $this->adminOnly();

        $buyers = User::where('role', 'buyer')
                        ->with('buyerProfile')
                        ->paginate(50);

        return view('admin.buyers', compact('buyers'));
    }

    public function verifyBuyer(User $user)
    {
        $this->adminOnly();

        $user->buyerProfile()->update(['is_verified' => true]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'verified_buyer',
            'target_type' => 'buyer',
            'target_id'   => $user->id,
            'notes'       => "Buyer profile approved for {$user->name}.",
        ]);

        \App\Models\Notification::create([
            'user_id' => $user->id,
            'title' => 'Profile Verified',
            'message' => 'Your buyer profile has been verified by the administrator.',
            'link' => route('dashboard'),
        ]);

        return back()->with('success', "{$user->name} has been verified.");
    }

    public function rejectBuyer(User $user)
    {
        $this->adminOnly();

        $user->buyerProfile()->update(['is_verified' => false]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'rejected_buyer',
            'target_type' => 'buyer',
            'target_id'   => $user->id,
            'notes'       => "Buyer profile rejected for {$user->name}.",
        ]);

        \App\Models\Notification::create([
            'user_id' => $user->id,
            'title' => 'Profile Verification Rejected',
            'message' => 'Your buyer profile verification was rejected by the administrator.',
            'link' => route('dashboard'),
        ]);

        return back()->with('success', "{$user->name} has been rejected.");
    }

    // -------------------------------------------------------
    public function storeUser(AdminStoreUserRequest $request)
    {
        $this->adminOnly();
        $validated = $request->validated();

        return \DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name'             => $validated['name'],
                'email'            => $validated['email'],
                'password'         => \Hash::make($validated['password']),
                'role'             => $validated['role'],
                'status'           => $validated['status'],
                'affiliation_type' => match ($validated['role']) {
                    'farmer'            => $validated['affiliation_type'] ?? 'independent',
                    'buyer'             => $validated['affiliation_type'] ?? 'independent',
                    'logistics_partner' => $validated['logistics_type'] === 'cooperative' ? 'cooperative' : 'independent',
                    default             => 'independent',
                },
                'cooperative_id'   => match ($validated['role']) {
                    'farmer' => ($validated['affiliation_type'] ?? 'independent') === 'cooperative' ? $validated['cooperative_id'] : null,
                    'buyer'  => ($validated['affiliation_type'] ?? 'independent') === 'cooperative' ? $validated['cooperative_id'] : null,
                    default  => null,
                },
            ]);

            $user->email_verified_at = now();
            $user->save();

            // Create Profile
            if ($validated['role'] === 'farmer') {
                $user->farmerProfile()->create([
                    'phone'            => $validated['phone'],
                    'farm_location'    => $validated['farm_location'],
                    'latitude'         => $request->input('latitude', 6.9),
                    'longitude'        => $request->input('longitude', 125.0),
                    'is_verified'      => true,
                    'affiliation_type' => $validated['affiliation_type'],
                    'cooperative_id'   => $validated['affiliation_type'] === 'cooperative' ? $validated['cooperative_id'] : null,
                ]);
            } elseif ($validated['role'] === 'logistics_partner') {
                $user->logisticsProfile()->create([
                    'phone'               => $validated['phone'],
                    'company_name'        => $validated['company_name'],
                    'business_permit_no'  => $validated['business_permit_no'],
                    'logistics_type'      => $validated['logistics_type'],
                    'cda_registration_no' => $validated['logistics_type'] === 'cooperative' ? $validated['cda_registration_no'] : null,
                    'is_verified'         => true,
                ]);
            } elseif ($validated['role'] === 'buyer') {
                $user->buyerProfile()->create([
                    'phone'       => $validated['phone'],
                    'is_verified' => true,
                ]);
            } elseif ($validated['role'] === 'driver') {
                $user->driverProfile()->create([
                    'phone'          => $validated['phone'],
                    'partner_id'     => $validated['partner_id'],
                    'license_no'     => $validated['license_number'],
                    'vehicle_type'   => $request->input('vehicle_type'),
                    'status'         => 'active',
                ]);
            }

            AuditLog::create([
                'admin_id'    => Auth::id(),
                'action'      => 'created_user',
                'target_type' => $user->role,
                'target_id'   => $user->id,
                'notes'       => "Admin created user {$user->name} with role {$user->role}.",
            ]);

            return redirect()->route('admin.users')->with('success', "User {$user->name} created successfully.");
        });
    }

    public function updateUser(AdminUpdateUserRequest $request, User $user)
    {
        $this->adminOnly();
        $validated = $request->validated();

        return \DB::transaction(function () use ($validated, $request, $user) {
            $oldRole = $user->role;
            $oldStatus = $user->status;
            $newStatus = $validated['status'];

            // If changing to inactive/archived, apply standard safety checks
            if ($newStatus === 'inactive' && $oldStatus === 'active' && $user->role === 'farmer') {
                $activeHarvests = Harvest::where('user_id', $user->id)
                    ->whereIn('status', HarvestStatus::buyerAvailable())
                    ->get();

                if ($activeHarvests->isNotEmpty() && !$request->boolean('force')) {
                    return back()->with('error', "Farmer {$user->name} has active harvests. Archive via the toggle button first to handle confirmation.");
                }

                if ($activeHarvests->isNotEmpty()) {
                    $harvestIds = $activeHarvests->pluck('id')->toArray();

                    Harvest::whereIn('id', $harvestIds)->update(['status' => 'cancelled']);

                    Negotiation::whereIn('harvest_id', $harvestIds)
                        ->whereIn('status', ['OPEN', 'AGREED'])
                        ->update(['status' => 'CANCELLED']);
                }
            }

            // Update user core fields
            $updateData = [
                'name'             => $validated['name'],
                'email'            => $validated['email'],
                'role'             => $validated['role'],
                'status'           => $validated['status'],
                'affiliation_type' => match ($validated['role']) {
                    'farmer'            => $validated['affiliation_type'] ?? 'independent',
                    'buyer'             => $validated['affiliation_type'] ?? 'independent',
                    'logistics_partner' => $validated['logistics_type'] === 'cooperative' ? 'cooperative' : 'independent',
                    default             => 'independent',
                },
                'cooperative_id'   => match ($validated['role']) {
                    'farmer' => ($validated['affiliation_type'] ?? 'independent') === 'cooperative' ? $validated['cooperative_id'] : null,
                    'buyer'  => ($validated['affiliation_type'] ?? 'independent') === 'cooperative' ? $validated['cooperative_id'] : null,
                    default  => null,
                },
            ];

            if (!empty($validated['password'])) {
                $updateData['password'] = \Hash::make($validated['password']);
            }

            $user->update($updateData);

            // Manage Profiles. If role changed, delete old profile and create new!
            if ($oldRole !== $validated['role']) {
                $user->farmerProfile()?->delete();
                $user->logisticsProfile()?->delete();
                $user->driverProfile()?->delete();
                $user->buyerProfile()?->delete();
            }

            if ($validated['role'] === 'farmer') {
                $user->farmerProfile()->updateOrCreate([], [
                    'phone'            => $validated['phone'],
                    'farm_location'    => $validated['farm_location'],
                    'latitude'         => $request->input('latitude', $user->farmerProfile?->latitude ?? 6.9),
                    'longitude'        => $request->input('longitude', $user->farmerProfile?->longitude ?? 125.0),
                    'is_verified'      => true,
                    'affiliation_type' => $validated['affiliation_type'],
                    'cooperative_id'   => $validated['affiliation_type'] === 'cooperative' ? $validated['cooperative_id'] : null,
                ]);
            } elseif ($validated['role'] === 'logistics_partner') {
                $user->logisticsProfile()->updateOrCreate([], [
                    'phone'               => $validated['phone'],
                    'company_name'        => $validated['company_name'],
                    'business_permit_no'  => $validated['business_permit_no'],
                    'logistics_type'      => $validated['logistics_type'],
                    'cda_registration_no' => $validated['logistics_type'] === 'cooperative' ? $validated['cda_registration_no'] : null,
                    'is_verified'         => true,
                ]);
            } elseif ($validated['role'] === 'buyer') {
                $user->buyerProfile()->updateOrCreate([], [
                    'phone'       => $validated['phone'],
                    'is_verified' => true,
                ]);
            } elseif ($validated['role'] === 'driver') {
                $user->driverProfile()->updateOrCreate([], [
                    'phone'          => $validated['phone'],
                    'partner_id'     => $validated['partner_id'],
                    'license_no'     => $validated['license_number'],
                    'vehicle_type'   => $request->input('vehicle_type'),
                    'status'         => 'active',
                ]);
            }

            AuditLog::create([
                'admin_id'    => Auth::id(),
                'action'      => 'updated_user',
                'target_type' => $user->role,
                'target_id'   => $user->id,
                'notes'       => "Admin updated user {$user->name}. Role: {$oldRole} -> {$user->role}. Status: {$oldStatus} -> {$user->status}.",
            ]);

            if ($validated['role'] === 'admin' && $oldRole !== 'admin') {
                AuditLog::create([
                    'admin_id'    => Auth::id(),
                    'action'      => 'promoted_to_admin',
                    'target_type' => 'user',
                    'target_id'   => $user->id,
                    'notes'       => "Admin promoted user {$user->name} ({$oldRole}) to administrator.",
                ]);
            } elseif ($oldRole === 'admin' && $validated['role'] !== 'admin') {
                AuditLog::create([
                    'admin_id'    => Auth::id(),
                    'action'      => 'demoted_from_admin',
                    'target_type' => 'user',
                    'target_id'   => $user->id,
                    'notes'       => "Admin demoted administrator {$user->name} to {$validated['role']}.",
                ]);
            }

            return redirect()->route('admin.users')->with('success', "User {$user->name} updated successfully.");
        });
    }

    // Audit Logs
    public function auditLogs()
    {
        $this->adminOnly();

        $logs = AuditLog::with(['admin'])->latest()->paginate(20);

        return view('admin.audit-logs', compact('logs'));
    }

    // -------------------------------------------------------
    // Platform Analytics Dashboard
    public function analytics()
    {
        $this->adminOnly();

        // ── Crop Pricing Trends ──
        $cropPricingTrends = \Cache::remember('admin.crop_pricing_trends', 300, function () {
            return \App\Models\Negotiation::where('status', 'COMPLETED')
                ->whereNotNull('negotiated_price')
                ->join('harvests', 'negotiations.harvest_id', '=', 'harvests.id')
                ->join('crops', 'harvests.crop_id', '=', 'crops.id')
                ->select(
                    'crops.name as crop_name',
                    \DB::raw('ROUND(AVG(negotiations.negotiated_price), 2) as avg_price'),
                    \DB::raw('MIN(negotiations.negotiated_price) as min_price'),
                    \DB::raw('MAX(negotiations.negotiated_price) as max_price'),
                    \DB::raw('COUNT(negotiations.id) as deal_count'),
                )
                ->groupBy('crops.name')
                ->orderByDesc('deal_count')
                ->get();
        });

        // Weekly price aggregation (last 12 weeks)
        $weeklyPrices = \Cache::remember('admin.weekly_prices', 300, function () {
            return \App\Models\Negotiation::where('status', 'COMPLETED')
                ->whereNotNull('negotiated_price')
                ->where('negotiations.created_at', '>=', now()->subWeeks(12))
                ->join('harvests', 'negotiations.harvest_id', '=', 'harvests.id')
                ->join('crops', 'harvests.crop_id', '=', 'crops.id')
                ->select(
                    'crops.name as crop_name',
                    \DB::raw('strftime("%Y-%W", negotiations.created_at) as week'),
                    \DB::raw('ROUND(AVG(negotiations.negotiated_price), 2) as avg_price'),
                )
                ->groupBy('crops.name', 'week')
                ->orderBy('week')
                ->get()
                ->groupBy('crop_name');
        });

        // ── Logistics Efficiency ──
        $fleetMetrics = \Cache::remember('admin.fleet_metrics', 300, function () {
            return \App\Models\PoolingJob::where('status', 'completed')
                ->select(
                    \DB::raw('COUNT(*) as total_trips'),
                    \DB::raw('ROUND(AVG(JULIANDAY(completed_at) - JULIANDAY(confirmed_at)), 2) as avg_trip_days'),
                )
                ->first();
        });

        $totalFuelLogs = \Cache::remember('admin.total_fuel_logs', 300, fn() => \App\Models\FuelLog::count());
        $totalFuelCost = \Cache::remember('admin.total_fuel_cost', 300, fn() => \App\Models\FuelLog::sum('cost'));
        $totalFuelLiters = \Cache::remember('admin.total_fuel_liters', 300, fn() => \App\Models\FuelLog::sum('fuel_liters'));
        $avgKpl = $totalFuelLiters > 0
            ? round(\App\Models\FuelLog::selectRaw('(MAX(odometer_reading) - MIN(odometer_reading)) as distance')->value('distance') / $totalFuelLiters, 2)
            : 0;

        // ── Baseline Price Management ──
        $crops = \Cache::remember('admin.crops_list', 600, fn() => \App\Models\Crop::orderBy('name')->get());

        return view('admin.analytics', compact(
            'cropPricingTrends', 'weeklyPrices', 'fleetMetrics',
            'totalFuelLogs', 'totalFuelCost', 'totalFuelLiters', 'avgKpl',
            'crops'
        ));
    }

    // -------------------------------------------------------
    // Data Export (CSV)
    public function exportUsers()
    {
        $this->adminOnly();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users-export-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Email', 'Role', 'Status', 'Phone', 'Created At']);

            User::with(['farmerProfile', 'logisticsProfile', 'driverProfile'])
                ->orderBy('id')
                ->chunk(500, function ($users) use ($handle) {
                    foreach ($users as $user) {
                        fputcsv($handle, [
                            $user->id, $user->name, $user->email, $user->role,
                            $user->status, $user->phone, $user->created_at,
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportHarvests()
    {
        $this->adminOnly();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="harvests-export-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Farmer', 'Crop', 'Variety', 'Quantity (kg)', 'Status', 'Created At']);

            Harvest::with(['farmer', 'crop'])
                ->orderBy('id')
                ->chunk(500, function ($harvests) use ($handle) {
                    foreach ($harvests as $h) {
                        fputcsv($handle, [
                            $h->id, $h->farmer->name ?? '—', $h->crop->name ?? $h->crop_type ?? '—',
                            $h->variety ?? '—', $h->quantity_kg, $h->status, $h->created_at,
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // -------------------------------------------------------
    // Update baseline crop price (Admin Override)
    public function updateBaselinePrice(UpdateBaselinePriceRequest $request, \App\Models\Crop $crop)
    {
        $this->adminOnly();
        $validated = $request->validated();

        $oldPrice = $crop->baseline_price_per_kg;
        $crop->update(['baseline_price_per_kg' => $validated['baseline_price_per_kg']]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'updated_baseline_price',
            'target_type' => 'crop',
            'target_id'   => $crop->id,
            'notes'       => "Baseline price for '{$crop->name}' changed from ₱" . number_format($oldPrice ?? 0, 2) . " to ₱" . number_format($validated['baseline_price_per_kg'], 2) . "/kg.",
        ]);

        return back()->with('success', "Baseline price for '{$crop->name}' updated to ₱" . number_format($validated['baseline_price_per_kg'], 2) . "/kg.");
    }
}
