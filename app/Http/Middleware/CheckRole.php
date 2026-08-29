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

        $allowed = [];
        foreach ($roles as $role) {
            $allowed = array_merge($allowed, array_map('trim', explode(',', $role)));
        }

        if (!empty($allowed) && !in_array($userRole, $allowed)) {
            abort(403, 'Unauthorized. Required role: ' . implode(' or ', $allowed) . '.');
        }

        return $next($request);
    }
}
