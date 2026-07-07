<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\FarmerProfile;
use App\Models\LogisticsProfile;

class RegisterController extends Controller
{
    public function index()
    {
        return view('auth.register-select');
    }

    public function create($role)
    {
        $validRoles = ['farmer', 'logistics_partner', 'buyer'];

        if (!in_array($role, $validRoles)) {
            abort(404);
        }

        $cooperatives = collect();
        if ($role === 'farmer' || $role === 'buyer') {
            $cooperatives = LogisticsProfile::where('logistics_type', 'cooperative')
                ->where('is_verified', true)
                ->with('user')
                ->get();
        }

        return view("auth.register-{$role}", compact('cooperatives'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|max:255|unique:users',
            'email'               => 'required|string|email|max:255|unique:users',
            'phone'               => 'required|string|max:20',
            'password'            => 'required|string|min:8|confirmed',
            'role'                => 'required|in:farmer,logistics_partner,buyer',

            // Buyer fields
            'company_name'        => 'required_if:role,buyer|nullable|string|max:255',
            'affiliation_type'    => 'required_if:role,buyer|required_if:role,farmer|nullable|in:cooperative,independent',
            'cooperative_id'      => 'required_if:affiliation_type,cooperative|nullable|exists:logistics_profiles,id',

            // Farmer fields
            'farm_location'       => 'required_if:role,farmer|nullable|string|max:255',
            'latitude'            => 'required_if:role,farmer|nullable|numeric|between:-90,90',
            'longitude'           => 'required_if:role,farmer|nullable|numeric|between:-180,180',

            // Logistics fields
            'company_name'        => 'required_if:role,logistics_partner|nullable|string|max:255',
            'business_permit_no'  => 'required_if:role,logistics_partner|nullable|string|max:255',
            'logistics_type'      => 'required_if:role,logistics_partner|nullable|in:cooperative,company',
            'cda_registration_no' => 'required_if:logistics_type,cooperative|nullable|string|max:255',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = User::create([
                    'name'             => $request->name,
                    'email'            => $request->email,
                    'password'         => Hash::make($request->password),
                    'role'             => $request->role,
                    'affiliation_type' => $request->role === 'buyer' ? ($request->affiliation_type ?? 'independent') : 'independent',
                    'cooperative_id'   => ($request->role === 'buyer' && $request->affiliation_type === 'cooperative') ? $request->cooperative_id : null,
                ]);

                if ($request->role === 'farmer') {
                    $user->farmerProfile()->create([
                        'phone'            => $request->phone,
                        'farm_location'    => $request->farm_location,
                        'latitude'         => $request->latitude,
                        'longitude'        => $request->longitude,
                        'is_verified'      => false,
                        'affiliation_type' => $request->affiliation_type,
                        'cooperative_id'   => $request->affiliation_type === 'cooperative'
                                                ? $request->cooperative_id
                                                : null,
                    ]);
                } elseif ($request->role === 'logistics_partner') {
                    $user->logisticsProfile()->create([
                        'phone'               => $request->phone,
                        'company_name'        => $request->company_name,
                        'business_permit_no'  => $request->business_permit_no,
                        'logistics_type'      => $request->logistics_type,
                        'cda_registration_no' => $request->logistics_type === 'cooperative'
                                                    ? $request->cda_registration_no
                                                    : null,
                    ]);
                }
                // Buyer: no extended profile needed, just store phone on user
                // company_name stored in audit log for reference

                Auth::login($user);

                \App\Models\AuditLog::create([
                    'admin_id'    => $user->id,
                    'action'      => 'register',
                    'target_type' => $user->role,
                    'target_id'   => $user->id,
                    'notes'       => "User {$user->name} registered as {$user->role} and logged in.",
                ]);

                $user->sendEmailVerificationNotification();
                return redirect()->route('verification.notice');
            });
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
}
