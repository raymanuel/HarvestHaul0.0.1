<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class EnsureAccountIsActive
 * 
 * Middleware to enforce active account checks for authenticated users.
 * If a user's status is set to 'inactive' (e.g., archived or suspended by admin),
 * the session is destroyed, the user is logged out, and redirected to login.
 */
class EnsureAccountIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated and their account status is explicitly 'inactive'
        if (Auth::check() && Auth::user()->status === 'inactive') {
            // Force logout the user
            Auth::logout();
            
            // Invalidate the session data
            $request->session()->invalidate();
            
            // Regenerate the CSRF token to prevent CSRF fixation attacks
            $request->session()->regenerateToken();

            // Redirect back to login with archiving notification
            return redirect('/login')->withErrors([
                'email' => 'Your account has been archived. Contact the administrator.',
            ]);
        }

        return $next($request);
    }
}
