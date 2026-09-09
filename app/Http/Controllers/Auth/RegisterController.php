<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\SendOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

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

        return view("auth.register-{$role}");
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|max:255|unique:users',
            'email'               => 'required|string|email|max:255|unique:users',
            'phone'               => 'required|string|max:20',
            'password'            => 'required|string|min:8|confirmed',
            'role'                => 'required|in:farmer,logistics_partner,buyer',
            'accepted_terms'      => 'accepted',

            // Farmer fields (nullable — can be completed later in profile)
            'farm_location'       => 'nullable|string|max:255',
            'latitude'            => 'nullable|numeric|between:-90,90',
            'longitude'           => 'nullable|numeric|between:-180,180',

            // Logistics fields (nullable — can be completed later in profile)
            'company_name'        => 'nullable|string|max:255',
            'business_permit_no'  => 'nullable|string|max:255',
            'logistics_type'      => 'nullable|in:cooperative,company',
            'cda_registration_no' => 'nullable|string|max:255',

            // Cooperative membership must point at a verified cooperative
            'cooperative_id'      => [
                'nullable',
                'integer',
                Rule::exists('logistics_profiles', 'id')->where(fn ($q) => $q->where('is_verified', true)),
            ],
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = User::create([
                    'name'             => $request->name,
                    'email'            => $request->email,
                    'password'         => $request->password,
                    'role'             => $request->role,
                    'affiliation_type' => match ($request->role) {
                        'farmer'             => 'independent',
                        'logistics_partner'  => $request->logistics_type === 'cooperative' ? 'cooperative' : 'independent',
                        'buyer'              => 'independent',
                        default              => 'independent',
                    },
                    'cooperative_id'   => null,
                ]);

                if ($request->role === 'farmer') {
                    $user->farmerProfile()->create([
                        'phone'            => $request->phone,
                        'farm_location'    => $request->farm_location ?? 'Set your farm location in profile',
                        'latitude'         => $request->latitude,
                        'longitude'        => $request->longitude,
                        'is_verified'      => false,
                        'affiliation_type' => 'independent',
                        'cooperative_id'   => null,
                    ]);
                } elseif ($request->role === 'logistics_partner') {
                    $user->logisticsProfile()->create([
                        'phone'               => $request->phone,
                        'company_name'        => $request->company_name ?? $request->name,
                        'business_permit_no'  => $request->business_permit_no,
                        'logistics_type'      => $request->logistics_type ?? 'company',
                        'cda_registration_no' => $request->logistics_type === 'cooperative'
                                                    ? $request->cda_registration_no
                                                    : null,
                    ]);
                } elseif ($request->role === 'buyer') {
                    $user->buyerProfile()->create([
                        'phone'       => $request->phone,
                        'is_verified' => false,
                    ]);
                }
                // Buyer: profile now stored in buyer_profiles table
                // company_name stored in audit log for reference

                Auth::login($user);

                \App\Models\AuditLog::create([
                    'admin_id'    => $user->id,
                    'action'      => 'register',
                    'target_type' => $user->role,
                    'target_id'   => $user->id,
                    'notes'       => "User {$user->name} registered as {$user->role} and logged in.",
                ]);

                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $user->forceFill([
                    'email_otp' => $otp,
                    'email_otp_expires_at' => now()->addMinutes(10),
                ])->save();

                Mail::to($user->email)->send(new SendOtpMail($otp, $user->name));

                return redirect()->route('verification.notice');
            });
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
}
