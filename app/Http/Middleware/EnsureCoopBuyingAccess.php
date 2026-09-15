<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureCoopBuyingAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        $isCoop = $user
            && $user->role === 'logistics_partner'
            && $user->logisticsProfile
            && $user->logisticsProfile->isCooperative()
            && $user->logisticsProfile->is_verified;

        if ($user?->role !== 'buyer' && !$isCoop) {
            abort(403, 'Only buyers or verified cooperatives can access this module.');
        }

        return $next($request);
    }
}