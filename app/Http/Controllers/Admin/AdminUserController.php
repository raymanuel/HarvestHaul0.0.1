<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\Negotiation;
use App\Http\Requests\AdminStoreUserRequest;
use App\Http\Requests\AdminUpdateUserRequest;
use App\Traits\Notifiable;

class AdminUserController extends Controller
{
    use Notifiable;

    // -------------------------------------------------------
    // User Management
    public function users()
    {
        $users = User::with(['farmerProfile', 'logisticsProfile', 'driverProfile.partner.user'])
                     ->orderBy('role')
                     ->paginate(50);

        $cooperatives = \App\Models\LogisticsProfile::with('user')->orderBy('company_name')->take(100)->get();

        return view('admin.users', compact('users', 'cooperatives'));
    }

    public function toggleStatus(Request $request, User $user)
    {
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

                self::logAudit(
                    Auth::id(),
                    'cancelled_harvests_on_archive',
                    'farmer',
                    $user->id,
                    "Cancelled {$activeHarvestIds->count()} active harvest post(s) and their negotiations due to account archiving of {$user->name}."
                );
            }
        }

        $user->update(['status' => $newStatus]);

        self::logAudit(
            Auth::id(),
            $newStatus === 'inactive' ? 'archived_user' : 'reactivated_user',
            $user->role,
            $user->id,
            "User {$user->name} status changed to {$newStatus}."
        );

        // JSON response for AJAX, redirect for normal POST
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'new_status' => $newStatus]);
        }

        return back()->with('success', "User {$user->name} marked as {$newStatus}.");
    }

    // -------------------------------------------------------
    public function storeUser(AdminStoreUserRequest $request)
    {
        $validated = $request->validated();

        return \DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name'             => $validated['name'],
                'email'            => $validated['email'],
                'password'         => $validated['password'],
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
                    'latitude'         => $validated['latitude'] ?? 6.9,
                    'longitude'        => $validated['longitude'] ?? 125.0,
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
                    'vehicle_type'   => $validated['vehicle_type'] ?? null,
                    'status'         => 'active',
                ]);
            }

            self::logAudit(
                Auth::id(),
                'created_user',
                $user->role,
                $user->id,
                "Admin created user {$user->name} with role {$user->role}."
            );

            return redirect()->route('admin.users')->with('success', "User {$user->name} created successfully.");
        });
    }

    public function updateUser(AdminUpdateUserRequest $request, User $user)
    {
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
                $updateData['password'] = $validated['password'];
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
                    'latitude'         => $validated['latitude'] ?? $user->farmerProfile?->latitude ?? 6.9,
                    'longitude'        => $validated['longitude'] ?? $user->farmerProfile?->longitude ?? 125.0,
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
                    'vehicle_type'   => $validated['vehicle_type'] ?? null,
                    'status'         => 'active',
                ]);
            }

            self::logAudit(
                Auth::id(),
                'updated_user',
                $user->role,
                $user->id,
                "Admin updated user {$user->name}. Role: {$oldRole} -> {$user->role}. Status: {$oldStatus} -> {$user->status}."
            );

            if ($validated['role'] === 'admin' && $oldRole !== 'admin') {
                self::logAudit(
                    Auth::id(),
                    'promoted_to_admin',
                    'user',
                    $user->id,
                    "Admin promoted user {$user->name} ({$oldRole}) to administrator."
                );
            } elseif ($oldRole === 'admin' && $validated['role'] !== 'admin') {
                self::logAudit(
                    Auth::id(),
                    'demoted_from_admin',
                    'user',
                    $user->id,
                    "Admin demoted administrator {$user->name} to {$validated['role']}."
                );
            }

            return redirect()->route('admin.users')->with('success', "User {$user->name} updated successfully.");
        });
    }

    // -------------------------------------------------------
    // Data Export (CSV)
    public function exportUsers()
    {
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
}
