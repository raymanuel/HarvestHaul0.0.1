<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Harvest;

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
            ->get();

        $pendingLogisticsList = User::where('role', 'logistics_partner')
            ->whereHas('logisticsProfile', fn($q) => $q->where('is_verified', false))
            ->with('logisticsProfile')
            ->latest()
            ->get();

        $pendingFarmerDocsList = \App\Models\FarmerDocument::where('status', 'pending')
            ->with('user')
            ->latest()
            ->get();

        $pendingLogisticsDocsList = \App\Models\LogisticsDocument::where('status', 'pending')
            ->with('user')
            ->latest()
            ->get();

        return view('admin.admin-view', [
            'totalUsers'               => User::whereNot('role', 'admin')->count(),
            'totalFarmers'             => User::where('role', 'farmer')->count(),
            'totalLogistics'           => User::where('role', 'logistics_partner')->count(),
            'totalDrivers'             => User::where('role', 'driver')->count(),
            'pendingFarmers'           => $pendingFarmersList->count(),
            'pendingLogistics'         => $pendingLogisticsList->count(),
            'activeHarvests'           => Harvest::where('status', 'active')->count(),
            'pendingHarvests'          => Harvest::where('status', 'pending')->count(),
            'recentLogs'               => AuditLog::with('admin')->latest()->take(5)->get(),
            'pendingFarmersList'       => $pendingFarmersList,
            'pendingLogisticsList'     => $pendingLogisticsList,
            'pendingFarmerDocsList'    => $pendingFarmerDocsList,
            'pendingLogisticsDocsList' => $pendingLogisticsDocsList,
        ]);
    }

    // -------------------------------------------------------
    // User Management
    public function users()
    {
        $this->adminOnly();

        $users = User::with(['farmerProfile', 'logisticsProfile', 'driverProfile.partner.user'])
                     ->orderBy('role')
                     ->get();

        $cooperatives = \App\Models\LogisticsProfile::with('user')->orderBy('company_name')->get();

        return view('admin.users', compact('users', 'cooperatives'));
    }

    public function toggleStatus(Request $request, User $user)
    {
        $this->adminOnly();

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';

        // Archiving flow — check for active harvests
        if ($newStatus === 'inactive' && $user->role === 'farmer') {
            $activeHarvests = Harvest::where('user_id', $user->id)
                ->where('status', 'active')
                ->get();

            if ($activeHarvests->isNotEmpty() && !$request->boolean('force')) {
                return response()->json([
                    'requires_confirmation' => true,
                    'active_harvest_count'  => $activeHarvests->count(),
                    'user_name'             => $user->name,
                    'user_id'               => $user->id,
                ]);
            }

            // Force confirmed — cancel all active harvests
            if ($activeHarvests->isNotEmpty()) {
                Harvest::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->update(['status' => 'cancelled']);

                AuditLog::create([
                    'admin_id'    => Auth::id(),
                    'action'      => 'cancelled_harvests_on_archive',
                    'target_type' => 'farmer',
                    'target_id'   => $user->id,
                    'notes'       => "Cancelled {$activeHarvests->count()} active harvest listing(s) due to account archiving of {$user->name}.",
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
            ->orderByRaw("FIELD(status, 'active', 'pending', 'completed', 'cancelled')")
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.harvests', compact('harvests'));
    }

    // -------------------------------------------------------
    // Driver Management
    public function drivers()
    {
        $this->adminOnly();

        $drivers = User::where('role', 'driver')
            ->with('logisticsProfile')
            ->orderBy('status')
            ->get();

        return view('admin.drivers', compact('drivers'));
    }

    // -------------------------------------------------------
    // Farmer Verification
    public function farmers()
    {
        $this->adminOnly();

        $farmers = User::where('role', 'farmer')
                       ->with('farmerProfile')
                       ->get();

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
                        ->get();

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
    public function storeUser(Request $request)
    {
        $this->adminOnly();

        $rules = [
            'name'     => ['required', 'string', 'max:255', 'unique:users,name'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', 'in:admin,farmer,logistics_partner,driver,buyer'],
            'status'   => ['required', 'in:active,inactive'],
        ];

        // Add role-specific profile validation rules
        if ($request->role === 'farmer') {
            $rules['phone'] = ['required', 'string', 'max:20'];
            $rules['farm_location'] = ['required', 'string', 'max:255'];
            $rules['affiliation_type'] = ['required', 'in:cooperative,independent'];
            $rules['cooperative_id'] = ['required_if:affiliation_type,cooperative', 'nullable', 'exists:logistics_profiles,id'];
        } elseif ($request->role === 'logistics_partner') {
            $rules['phone'] = ['required', 'string', 'max:20'];
            $rules['company_name'] = ['required', 'string', 'max:255'];
            $rules['business_permit_no'] = ['required', 'string', 'max:255'];
            $rules['logistics_type'] = ['required', 'in:cooperative,company'];
            $rules['cda_registration_no'] = ['required_if:logistics_type,cooperative', 'nullable', 'string', 'max:255'];
        } elseif ($request->role === 'driver') {
            $rules['phone'] = ['required', 'string', 'max:20'];
            $rules['license_number'] = ['required', 'string', 'max:50', 'unique:driver_profiles,license_no'];
            $rules['partner_id'] = ['required', 'exists:logistics_profiles,id'];
        } elseif ($request->role === 'buyer') {
            $rules['phone'] = ['required', 'string', 'max:20'];
            $rules['affiliation_type'] = ['required', 'in:cooperative,independent'];
            $rules['cooperative_id'] = ['required_if:affiliation_type,cooperative', 'nullable', 'exists:logistics_profiles,id'];
        }

        $validated = $request->validate($rules);

        return \DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name'             => $validated['name'],
                'email'            => $validated['email'],
                'password'         => \Hash::make($validated['password']),
                'role'             => $validated['role'],
                'status'           => $validated['status'],
                'phone'            => $validated['phone'] ?? null,
                'affiliation_type' => $validated['role'] === 'buyer' ? ($validated['affiliation_type'] ?? 'independent') : 'independent',
                'cooperative_id'   => ($validated['role'] === 'buyer' && ($validated['affiliation_type'] ?? 'independent') === 'cooperative') ? $validated['cooperative_id'] : null,
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
                    'is_verified'      => true, // Admin created is auto-verified
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

    public function updateUser(Request $request, User $user)
    {
        $this->adminOnly();

        $rules = [
            'name'     => ['required', 'string', 'max:255', 'unique:users,name,' . $user->id],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'role'     => ['required', 'in:admin,farmer,logistics_partner,driver,buyer'],
            'status'   => ['required', 'in:active,inactive'],
        ];

        // Add role-specific profile validation rules
        if ($request->role === 'farmer') {
            $rules['phone'] = ['required', 'string', 'max:20'];
            $rules['farm_location'] = ['required', 'string', 'max:255'];
            $rules['affiliation_type'] = ['required', 'in:cooperative,independent'];
            $rules['cooperative_id'] = ['required_if:affiliation_type,cooperative', 'nullable', 'exists:logistics_profiles,id'];
        } elseif ($request->role === 'logistics_partner') {
            $rules['phone'] = ['required', 'string', 'max:20'];
            $rules['company_name'] = ['required', 'string', 'max:255'];
            $rules['business_permit_no'] = ['required', 'string', 'max:255'];
            $rules['logistics_type'] = ['required', 'in:cooperative,company'];
            $rules['cda_registration_no'] = ['required_if:logistics_type,cooperative', 'nullable', 'string', 'max:255'];
        } elseif ($request->role === 'driver') {
            $rules['phone'] = ['required', 'string', 'max:20'];
            $rules['license_number'] = ['required', 'string', 'max:50', 'unique:driver_profiles,license_no,' . ($user->driverProfile?->id ?? 'NULL')];
            $rules['partner_id'] = ['required', 'exists:logistics_profiles,id'];
        } elseif ($request->role === 'buyer') {
            $rules['phone'] = ['required', 'string', 'max:20'];
            $rules['affiliation_type'] = ['required', 'in:cooperative,independent'];
            $rules['cooperative_id'] = ['required_if:affiliation_type,cooperative', 'nullable', 'exists:logistics_profiles,id'];
        }

        $validated = $request->validate($rules);

        return \DB::transaction(function () use ($validated, $request, $user) {
            $oldRole = $user->role;
            $oldStatus = $user->status;
            $newStatus = $validated['status'];

            // If changing to inactive/archived, apply standard safety checks (same as toggleStatus)
            if ($newStatus === 'inactive' && $oldStatus === 'active' && $user->role === 'farmer') {
                $activeHarvests = Harvest::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->get();

                if ($activeHarvests->isNotEmpty() && !$request->boolean('force')) {
                    return back()->with('error', "Farmer {$user->name} has active harvests. Archive via the toggle button first to handle confirmation.");
                }

                if ($activeHarvests->isNotEmpty()) {
                    Harvest::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->update(['status' => 'cancelled']);
                }
            }

            // Update user core fields
            $updateData = [
                'name'             => $validated['name'],
                'email'            => $validated['email'],
                'role'             => $validated['role'],
                'status'           => $validated['status'],
                'phone'            => $validated['phone'] ?? null,
                'affiliation_type' => $validated['role'] === 'buyer' ? ($validated['affiliation_type'] ?? 'independent') : 'independent',
                'cooperative_id'   => ($validated['role'] === 'buyer' && ($validated['affiliation_type'] ?? 'cooperative') === 'cooperative') ? $validated['cooperative_id'] : null,
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
        // Average negotiated price per crop from completed negotiations
        $cropPricingTrends = \App\Models\Negotiation::where('status', 'COMPLETED')
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

        // Weekly price aggregation (last 12 weeks)
        $weeklyPrices = \App\Models\Negotiation::where('status', 'COMPLETED')
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

        // ── Logistics Efficiency ──
        $fleetMetrics = \App\Models\PoolingJob::where('status', 'completed')
            ->select(
                \DB::raw('COUNT(*) as total_trips'),
                \DB::raw('ROUND(AVG(JULIANDAY(completed_at) - JULIANDAY(confirmed_at)), 2) as avg_trip_days'),
            )
            ->first();

        $totalFuelLogs = \App\Models\FuelLog::count();
        $totalFuelCost = \App\Models\FuelLog::sum('cost');
        $totalFuelLiters = \App\Models\FuelLog::sum('fuel_liters');
        $avgKpl = $totalFuelLiters > 0
            ? round(\App\Models\FuelLog::selectRaw('(MAX(odometer_reading) - MIN(odometer_reading)) as distance')->value('distance') / $totalFuelLiters, 2)
            : 0;

        // ── Baseline Price Management ──
        $crops = \App\Models\Crop::orderBy('name')->get();

        return view('admin.analytics', compact(
            'cropPricingTrends', 'weeklyPrices', 'fleetMetrics',
            'totalFuelLogs', 'totalFuelCost', 'totalFuelLiters', 'avgKpl',
            'crops'
        ));
    }

    // -------------------------------------------------------
    // Update baseline crop price (Admin Override)
    public function updateBaselinePrice(Request $request, \App\Models\Crop $crop)
    {
        $this->adminOnly();

        $request->validate([
            'baseline_price_per_kg' => 'required|numeric|min:0.01|max:99999.99',
        ]);

        $oldPrice = $crop->baseline_price_per_kg;
        $crop->update(['baseline_price_per_kg' => $request->baseline_price_per_kg]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'updated_baseline_price',
            'target_type' => 'crop',
            'target_id'   => $crop->id,
            'notes'       => "Baseline price for '{$crop->name}' changed from ₱" . number_format($oldPrice ?? 0, 2) . " to ₱" . number_format($request->baseline_price_per_kg, 2) . "/kg.",
        ]);

        return back()->with('success', "Baseline price for '{$crop->name}' updated to ₱" . number_format($request->baseline_price_per_kg, 2) . "/kg.");
    }
}
