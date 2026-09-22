<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Cooperative;
use App\Models\DriverProfile;
use App\Models\FarmerProfile;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role', 'all');
        $search = trim((string) $request->query('search'));

        $users = User::query()
            ->when($role !== 'all', fn ($q) => $q->where('role', $role))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->with('cooperative')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'role' => $role,
            'search' => $search,
            'roles' => UserRole::cases(),
            'cooperatives' => Cooperative::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:'.implode(',', array_map(fn ($r) => $r->value, [
                UserRole::COOP_ADMIN,
                UserRole::FIELD_RECEIVING,
                UserRole::DELIVERY_PERSONNEL,
                UserRole::FARMER,
            ])),
            'cooperative_id' => 'required|exists:cooperatives,id',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'status' => 'active',
            'phone' => $data['phone'] ?? null,
            'affiliation_type' => 'cooperative',
            'cooperative_id' => $data['cooperative_id'],
            'email_verified_at' => now(),
        ]);

        if ($user->role === UserRole::FARMER->value) {
            FarmerProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $user->phone,
                    'affiliation_type' => 'cooperative',
                    'cooperative_id' => $user->cooperative_id,
                    'membership_status' => 'approved',
                    'membership_requested_at' => now(),
                    'membership_decided_at' => now(),
                    'is_verified' => true,
                ],
            );
        }

        if ($user->role === UserRole::DELIVERY_PERSONNEL->value) {
            DriverProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'cooperative_id' => $user->cooperative_id,
                    'phone' => $user->phone,
                    'employment_status' => 'active',
                ],
            );
        }

        $this->log($request, $user, 'created', "Platform account created with role {$user->roleLabel()}.");

        return back()->with('success', "{$user->name} was added as {$user->roleLabel()}. Share the login email so they can sign in and start.");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'cooperative_id' => 'nullable|exists:cooperatives,id',
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'cooperative_id' => $data['cooperative_id'] ?? $user->cooperative_id,
        ]);

        $this->log($request, $user, 'updated', 'Platform account details updated.');

        return back()->with('success', "{$user->name}'s account details were updated.");
    }

    public function toggleStatus(Request $request, User $user)
    {
        abort_if($user->isSuperAdmin(), 403, 'Super admin accounts cannot be suspended here.');

        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        $this->log($request, $user, $user->status, "Account marked {$user->status}.");

        return back()->with('success', $user->status === 'active'
            ? "{$user->name} was reactivated and can sign in again."
            : "{$user->name} was suspended and can no longer sign in.");
    }

    private function log(Request $request, User $user, string $action, string $notes): void
    {
        AuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => $action,
            'target_type' => 'user',
            'target_id' => $user->id,
            'notes' => $notes,
        ]);
    }
}