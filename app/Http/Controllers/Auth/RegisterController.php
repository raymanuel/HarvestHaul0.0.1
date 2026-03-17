<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ProducerProfile;
use App\Models\PartnerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function create()
    {
        // Public registration is only for the "Entry Entities"
        return view('auth.register');
    }

    public function store(Request $request)
    {
        // 1. Validation (only Farmer vs Partner)
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',

            // Strictly Farmer or Partner for public signup
            'role'     => 'required|in:farmer,logistics_partner',

            'rsbsa_number' => 'required_if:role,farmer|nullable|string|size:12|unique:producer_profiles',
            'company_name' => 'required_if:role,logistics_partner|nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($request) {

            // A. Create User
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => $request->role,
            ]);

            // B. Create Profile (Farmer or Partner)
            if ($user->role === 'farmer') {
                $user->farmerProfile()->create([
                    'rsbsa_number' => $request->rsbsa_number,
                    'is_verified'  => false,
                ]);
            }

            elseif ($user->role === 'logistics_partner') {
                $user->logisticsProfile()->create([
                    'company_name' => $request->company_name,
                ]);
            }

            Auth::login($user);
            return redirect()->route('dashboard');
        });
    }
}
