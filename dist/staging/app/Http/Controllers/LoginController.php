<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            // Credentials are correct — now check account status
            if (Auth::user()->status === 'inactive') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Your account has been archived. Contact the administrator.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            \App\Models\AuditLog::create([
                'admin_id'    => Auth::id(),
                'action'      => 'login',
                'target_type' => Auth::user()->role,
                'target_id'   => Auth::id(),
                'notes'       => "User " . Auth::user()->name . " logged in.",
            ]);

            // Clear API-like intended URLs set by background fetch() polling
            // (e.g. notification polls that stored /api/notifications before session expired).
            // Laravel stores the full URL (http://host/api/notifications), so match the path.
            $intended = (string) session('url.intended', '');
            if (str_starts_with((string) parse_url($intended, PHP_URL_PATH), '/api/')) {
                session()->forget('url.intended');
            }

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            \App\Models\AuditLog::create([
                'admin_id'    => $user->id,
                'action'      => 'logout',
                'target_type' => $user->role,
                'target_id'   => $user->id,
                'notes'       => "User {$user->name} logged out.",
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
