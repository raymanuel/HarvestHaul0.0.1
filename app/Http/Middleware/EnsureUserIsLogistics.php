<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class EnsureUserIsLogistics
 * 
 * Middleware to restrict routing ingress only to users with the 'logistics_partner' role.
 * Throws a 403 Forbidden exception if validation fails.
 */
class EnsureUserIsLogistics
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
        // Check if user is logged in and role is 'logistics_partner'
        if (!Auth::check() || Auth::user()->role !== 'logistics_partner') {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
