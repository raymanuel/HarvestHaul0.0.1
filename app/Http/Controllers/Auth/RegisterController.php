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
        $validRoles = ['farmer', 'logistics_partner'];

        if (!in_array($role, $validRoles)) {
            abort(404);
        }

        return view("auth.register-{$role}");
    }
    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|string|email|max:255|unique:users',
            'phone'         => 'required|string|max:20',
            'password'      => 'required|string|min:8|confirmed',
            'role'          => 'required|in:farmer,logistics_partner',
            'farm_location' => 'required_if:role,farmer|nullable|string|max:255',
            'latitude'      => 'required_if:role,farmer|nullable|numeric|between:-90,90',
            'longitude'     => 'required_if:role,farmer|nullable|numeric|between:-180,180',
            'company_name'  => 'required_if:role,logistics_partner|nullable|string|max:255',
            'business_permit_no' => 'nullable|string|max:255',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = User::create([
                    'name'     => $request->name,
                    'email'    => $request->email,
                    'password' => Hash::make($request->password),
                    'role'     => $request->role,
                ]);

                if ($request->role === 'farmer') {
                    $user->farmerProfile()->create([
                        'phone'         => $request->phone,
                        'farm_location' => $request->farm_location,
                        'latitude'      => $request->latitude,
                        'longitude'     => $request->longitude,
                        'is_verified'   => false,
                    ]);
                } elseif ($request->role === 'logistics_partner') {
                    $user->logisticsProfile()->create([
                        'phone'              => $request->phone,
                        'company_name'       => $request->company_name,
                        'business_permit_no' => $request->business_permit_no,
                    ]);
                }


                Auth::login($user);
                $user->sendEmailVerificationNotification();
                return redirect()->route('verification.notice');
            });
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }
}


