<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\Notifiable;
use Illuminate\Support\Facades\Auth;

class AdminVerificationController extends Controller
{
    use Notifiable;

    // -------------------------------------------------------
    // Driver Management
    public function drivers()
    {
        $drivers = User::where('role', 'driver')
            ->with(['logisticsProfile', 'driverProfile'])
            ->orderBy('status')
            ->paginate(50);

        return view('admin.drivers', compact('drivers'));
    }

    public function verifyDriverIdentity(User $user)
    {
        if ($user->role !== 'driver') {
            return back()->with('error', 'User is not a driver.');
        }

        $user->driverProfile()->update(['identity_verified' => true]);

        self::logAudit(
            Auth::id(),
            'verified_driver_identity',
            'driver',
            $user->id,
            "Driver identity verified for {$user->name}."
        );

        self::notifyIdentityVerified($user);

        return back()->with('success', "{$user->name}'s identity has been verified.");
    }

    public function rejectDriverIdentity(User $user)
    {
        if ($user->role !== 'driver') {
            return back()->with('error', 'User is not a driver.');
        }

        $user->driverProfile()->update(['identity_verified' => false]);

        self::logAudit(
            Auth::id(),
            'rejected_driver_identity',
            'driver',
            $user->id,
            "Driver identity verification rejected for {$user->name}."
        );

        self::notifyIdentityRejected($user);

        return back()->with('success', "{$user->name}'s identity verification has been rejected.");
    }

    // -------------------------------------------------------
    // Farmer Verification
    public function farmers()
    {
        $farmers = User::where('role', 'farmer')
                       ->with('farmerProfile')
                       ->paginate(50);

        return view('admin.farmers', compact('farmers'));
    }

    public function verifyFarmer(User $user)
    {
        $user->farmerProfile()->update(['is_verified' => true]);

        self::logAudit(
            Auth::id(),
            'verified_farmer',
            'farmer',
            $user->id,
            "Farmer profile approved for {$user->name}."
        );

        self::notifyProfileVerified($user);

        return back()->with('success', "{$user->name} has been verified.");
    }

    public function rejectFarmer(User $user)
    {
        $user->farmerProfile()->update(['is_verified' => false]);

        self::logAudit(
            Auth::id(),
            'rejected_farmer',
            'farmer',
            $user->id,
            "Farmer profile rejected for {$user->name}."
        );

        self::notifyProfileRejected($user);

        return back()->with('success', "{$user->name} has been rejected.");
    }

    // -------------------------------------------------------
    // Logistics Partner Verification
    public function logistics()
    {
        $partners = User::where('role', 'logistics_partner')
                        ->with('logisticsProfile')
                        ->paginate(50);

        return view('admin.logistics', compact('partners'));
    }

    public function verifyLogistics(User $user)
    {
        $user->logisticsProfile()->update(['is_verified' => true]);

        self::logAudit(
            Auth::id(),
            'verified_logistics',
            'logistics_partner',
            $user->id,
            "Logistics partner approved for {$user->name}."
        );

        self::notifyProfileVerified($user);

        return back()->with('success', "{$user->name} has been verified.");
    }

    public function rejectLogistics(User $user)
    {
        $user->logisticsProfile()->update(['is_verified' => false]);

        self::logAudit(
            Auth::id(),
            'rejected_logistics',
            'logistics_partner',
            $user->id,
            "Logistics partner rejected for {$user->name}."
        );

        self::notifyProfileRejected($user);

        return back()->with('success', "{$user->name} has been rejected.");
    }

    // -------------------------------------------------------
    // Buyer Verification
    public function buyers()
    {
        $buyers = User::where('role', 'buyer')
                        ->with('buyerProfile')
                        ->paginate(50);

        return view('admin.buyers', compact('buyers'));
    }

    public function verifyBuyer(User $user)
    {
        $user->buyerProfile()->update(['is_verified' => true]);

        self::logAudit(
            Auth::id(),
            'verified_buyer',
            'buyer',
            $user->id,
            "Buyer profile approved for {$user->name}."
        );

        self::notifyProfileVerified($user);

        return back()->with('success', "{$user->name} has been verified.");
    }

    public function rejectBuyer(User $user)
    {
        $user->buyerProfile()->update(['is_verified' => false]);

        self::logAudit(
            Auth::id(),
            'rejected_buyer',
            'buyer',
            $user->id,
            "Buyer profile rejected for {$user->name}."
        );

        self::notifyProfileRejected($user);

        return back()->with('success', "{$user->name} has been rejected.");
    }
}
