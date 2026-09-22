<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsApprovedCoopAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user || $user->role !== 'coop_admin') {
            abort(403, 'Unauthorized action.');
        }

        $cooperative = $user->cooperative;

        if (! $cooperative || ! $cooperative->isApproved()) {
            return redirect()->route('coop.status');
        }

        return $next($request);
    }
}
