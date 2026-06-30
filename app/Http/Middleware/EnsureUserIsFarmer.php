<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class EnsureUserIsFarmer
 * 
 * Middleware to restrict routing ingress only to users with the 'farmer' role.
 * Throws a 403 Forbidden exception if validation fails.
 */
class EnsureUserIsFarmer
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
        // Check if user is logged in and role is 'farmer'
        if (!Auth::check() || Auth::user()->role !== 'farmer') {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
