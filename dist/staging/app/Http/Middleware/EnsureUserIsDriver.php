<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EnsureUserIsDriver
 * 
 * Middleware to restrict routing ingress only to users with the 'driver' role.
 * Throws a 403 Forbidden exception if validation fails.
 */
class EnsureUserIsDriver
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is logged in and is a driver
        if (auth()->check() && auth()->user()->role === 'driver') {
            return $next($request);
        }

        // Return a forbidden HTTP response status
        abort(403, 'Access restricted to drivers only.');
    }
}
