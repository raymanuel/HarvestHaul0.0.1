<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SendOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class VerifyOtpController extends Controller
{
    public function verify(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        if (!$user->email_otp || !$user->email_otp_expires_at) {
            return back()->withErrors(['otp' => 'No OTP found. Request a new one.']);
        }

        if (now()->gt($user->email_otp_expires_at)) {
            return back()->withErrors(['otp' => 'OTP has expired. Request a new one.']);
        }

        if ($request->otp !== $user->email_otp) {
            return back()->withErrors(['otp' => 'Invalid OTP code.']);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_otp' => null,
            'email_otp_expires_at' => null,
        ])->save();

        return redirect()->route('verification.success');
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'email_otp' => $otp,
            'email_otp_expires_at' => now()->addMinutes(10),
        ])->save();

        Mail::to($user->email)->send(new SendOtpMail($otp, $user->name));

        return back()->with('status', 'otp-sent');
    }
}
