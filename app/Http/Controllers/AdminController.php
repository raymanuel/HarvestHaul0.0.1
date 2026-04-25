<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AuditLog;

class AdminController extends Controller
{

    private function adminOnly()
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return true;
        }
        abort(403, 'Unauthorized');
    }
    public function dashboard()
    {
        $users = User::with('auditLogs')->get();
        return view('admin.dashboard', compact('users'));
    }

    // -------------------------------------------------------
    // Dashboard Overview
    public function index()
    {
        $this->adminOnly();

        return view('dashboards.admin-view', [
            'totalUsers'       => User::count(),
            'pendingFarmers'   => User::where('role', 'farmer')
                                    ->whereHas('farmerProfile', fn($q) => $q->where('is_verified', false))
                                    ->count(),
            'pendingLogistics' => User::where('role', 'logistics_partner')
                                    ->whereHas('logisticsProfile', fn($q) => $q->where('is_verified', false))
                                    ->count(),
            'recentLogs'       => AuditLog::with('admin')->latest()->take(5)->get(),
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

    public function toggleStatus(User $user)
    {
        $this->adminOnly();

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => $newStatus === 'inactive' ? 'archived_user' : 'reactivated_user',
            'target_type' => $user->role,
            'target_id'   => $user->id,
            'notes'       => "User status changed to {$newStatus}",
        ]);

        return back()->with('success', "User {$user->name} marked as {$newStatus}.");
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
            'notes'       => "Farmer profile approved for {$user->name}",
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
            'notes'       => "Farmer profile rejected for {$user->name}",
        ]);

        return back()->with('success', "{$user->name} has been rejected.");
    }

    // Logistics Partner Verification
    // -------------------------------------------------------
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
            'notes'       => "Logistics partner approved for {$user->name}",
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
            'notes'       => "Logistics partner rejected for {$user->name}",
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
