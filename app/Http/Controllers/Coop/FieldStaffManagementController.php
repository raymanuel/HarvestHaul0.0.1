<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class FieldStaffManagementController extends Controller
{
    /**
     * Field/receiving staff. Previously only super_admin could create this
     * role (Admin\AdminUserController::store()) — contradicted the running-
     * system document's own stated design (coop admin manages its own
     * people). Mirrors Coop\DriverManagementController's direct-add flow
     * exactly, minus a profile table: field_receiving has none (Field\
     * ReceivingController reads Auth::user()->cooperative_id directly).
     */
    public function index()
    {
        $cooperativeId = Auth::user()?->cooperative_id;

        if (! $cooperativeId) {
            abort(403, 'You are not an active cooperative admin.');
        }

        $staff = User::where('role', UserRole::FIELD_RECEIVING->value)
            ->where('cooperative_id', $cooperativeId)
            ->orderBy('name')
            ->get();

        return view('coop.staff.index', compact('staff'));
    }

    public function create()
    {
        $cooperativeId = Auth::user()?->cooperative_id;

        if (! $cooperativeId) {
            abort(403, 'You are not an active cooperative admin.');
        }

        return view('coop.staff.create');
    }

    public function store(Request $request)
    {
        $cooperativeId = Auth::user()?->cooperative_id;

        if (! $cooperativeId) {
            abort(403, 'You are not an active cooperative admin.');
        }

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'phone'    => 'nullable|string|max:20',
        ]);

        $staff = User::create([
            'name'             => $data['name'],
            'email'            => strtolower($data['email']),
            'password'         => Hash::make($data['password']),
            'role'             => UserRole::FIELD_RECEIVING->value,
            'status'           => 'active',
            'phone'            => $data['phone'] ?? null,
            'affiliation_type' => 'cooperative',
            'cooperative_id'   => $cooperativeId,
        ]);

        // email_verified_at is deliberately not mass-assignable (guards
        // against a registration request ever setting it); a coop-admin-
        // created account is trusted immediately, same as the driver flow.
        $staff->forceFill(['email_verified_at' => now()])->save();

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'create_field_staff',
            'target_type' => 'user',
            'target_id'   => $staff->id,
            'notes'       => "Field/receiving staff {$staff->name} added directly to the cooperative by coop admin.",
        ]);

        return redirect()->route('coop.staff.index')->with('success', "{$staff->name} was added as field/receiving staff.");
    }
}
