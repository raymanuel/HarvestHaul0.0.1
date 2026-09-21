<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SendOtpMail;
use App\Models\AuditLog;
use App\Models\Cooperative;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class RegisterController extends Controller
{
    public function index()
    {
        return view('auth.register-select');
    }

    public function create($role)
    {
        $validRoles = ['cooperative', 'buyer'];

        if (! in_array($role, $validRoles)) {
            abort(404);
        }

        return view("auth.register-{$role}");
    }

    public function store(Request $request)
    {
        $role = $request->input('role');

        $request->validate($this->rules($role));

        try {
            return DB::transaction(function () use ($request, $role) {
                if ($role === 'cooperative') {
                    $user = $this->createCoopAdmin($request);
                    $cooperative = $this->createCooperative($request, $user->id);
                    $user->cooperative_id = $cooperative->id;
                    $user->save();
                } else {
                    $user = $this->createBuyer($request);
                }

                Auth::login($user);

                AuditLog::create([
                    'admin_id' => $user->id,
                    'action' => 'register',
                    'target_type' => $user->role,
                    'target_id' => $user->id,
                    'notes' => "User {$user->name} registered as {$user->roleLabel()} and logged in.",
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

    private function rules(string $role): array
    {
        $base = [
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'accepted_terms' => 'accepted',
            'role' => 'required|in:cooperative,buyer',
        ];

        if ($role === 'cooperative') {
            return array_merge($base, [
                'name' => 'required|string|max:255',
                'type' => 'required|in:primary,secondary,other',
                'province' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'municipality' => 'nullable|string|max:255',
                'barangay' => 'nullable|string|max:255',
                'street_address' => 'nullable|string|max:255',
                'contact_number' => 'required|string|max:20',
                'official_email' => 'required|string|email|max:255',
                'year_established' => 'nullable|integer|between:1900,'.now()->year,
                'business_activities' => 'nullable|string|max:2000',
                'cda_registration_number' => 'required|string|max:255',
                'registration_date' => 'nullable|date',
                'cert_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'articles_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'bylaws_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'rep_name' => 'required|string|max:255',
                'rep_position' => 'required|string|max:255',
                'rep_contact' => 'required|string|max:20',
                'rep_email' => 'required|string|email|max:255',
                'rep_id_type' => 'required|string|max:255',
                'rep_id_number' => 'required|string|max:255',
                'rep_id_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'rep_authorization_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);
        }

        // buyer
        return array_merge($base, [
            'business_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'business_address' => 'required|string|max:1000',
            'business_information' => 'nullable|string|max:2000',
        ]);
    }

    private function createCoopAdmin(Request $request): User
    {
        return User::create([
            'name' => $request->rep_name,
            'email' => strtolower($request->email),
            'password' => $request->password,
            'role' => UserRole::COOP_ADMIN->value,
            'phone' => $request->rep_contact,
        ]);
    }

    private function createBuyer(Request $request): User
    {
        $user = User::create([
            'name' => $request->business_name,
            'email' => strtolower($request->email),
            'password' => $request->password,
            'role' => UserRole::BUYER->value,
            'phone' => $request->phone,
            'status' => 'pending',
        ]);

        $user->buyerProfile()->create([
            'business_name' => $request->business_name,
            'contact_person' => $request->contact_person,
            'phone' => $request->phone,
            'business_address' => $request->business_address,
            'business_information' => $request->business_information,
            'status' => \App\Models\BuyerProfile::STATUS_PENDING,
            'is_verified' => false,
        ]);

        return $user;
    }

    private function createCooperative(Request $request, int $coopAdminUserId): Cooperative
    {
        $store = fn ($file) => $file
            ? Storage::disk('local')->putFile('coop-documents', $file)
            : null;

        return Cooperative::create([
            'name' => $request->name,
            'type' => $request->type,
            'province' => $request->province,
            'city' => $request->city,
            'municipality' => $request->municipality,
            'barangay' => $request->barangay,
            'street_address' => $request->street_address,
            'contact_number' => $request->contact_number,
            'official_email' => strtolower($request->official_email),
            'year_established' => $request->year_established,
            'business_activities' => $request->business_activities,
            'cda_registration_number' => $request->cda_registration_number,
            'registration_date' => $request->registration_date,
            'cert_document_path' => $store($request->file('cert_document')),
            'articles_document_path' => $store($request->file('articles_document')),
            'bylaws_document_path' => $store($request->file('bylaws_document')),
            'rep_name' => $request->rep_name,
            'rep_position' => $request->rep_position,
            'rep_contact' => $request->rep_contact,
            'rep_email' => strtolower($request->rep_email),
            'rep_id_type' => $request->rep_id_type,
            'rep_id_number' => $request->rep_id_number,
            'rep_id_document_path' => $store($request->file('rep_id_document')),
            'rep_authorization_document_path' => $store($request->file('rep_authorization_document')),
            'status' => Cooperative::STATUS_PENDING,
            'coop_admin_user_id' => $coopAdminUserId,
        ]);
    }
}