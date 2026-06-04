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

        return view('admin.admin-view', [
            'totalUsers'          => User::whereNot('role', 'admin')->count(),
            'totalFarmers'        => User::where('role', 'farmer')->count(),
            'totalLogistics'      => User::where('role', 'logistics_partner')->count(),
            'totalDrivers'        => User::where('role', 'driver')->count(),
            'pendingFarmers'      => User::where('role', 'farmer')
                                        ->whereHas('farmerProfile', fn($q) => $q->where('is_verified', false))
                                        ->count(),
            'pendingLogistics'    => User::where('role', 'logistics_partner')
                                        ->whereHas('logisticsProfile', fn($q) => $q->where('is_verified', false))
                                        ->count(),
            'activeHarvests'      => Harvest::where('status', 'active')->count(),
            'pendingHarvests'     => Harvest::where('status', 'pending')->count(),
            'recentLogs'          => AuditLog::with('admin')->latest()->take(5)->get(),
        ]);
    }

    // -------------------------------------------------------
    // User Management
    public function users()
    {
        $this->adminOnly();

        $users = User::with(['farmerProfile', 'logisticsProfile'])
                     ->orderBy('role')
                     ->get();

        return view('admin.users', compact('users'));
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

        return back()->with('success', "{$user->name} has been rejected.");
    }

    // -------------------------------------------------------
    // Audit Logs
    public function auditLogs()
    {
        $this->adminOnly();

        $logs = AuditLog::with(['admin'])->latest()->paginate(20);

        return view('admin.audit-logs', compact('logs'));
    }
}
