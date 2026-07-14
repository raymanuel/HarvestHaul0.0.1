<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized.');
        }

        $userRole = Auth::user()->role;

        if (!empty($roles) && !in_array($userRole, $roles)) {
            abort(403, 'Unauthorized. Required role: ' . implode(' or ', $roles) . '.');
        }

        return $next($request);
    }
}
