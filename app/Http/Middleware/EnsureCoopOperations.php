<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureCoopOperations
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        $isCoop = $user
            && $user->role === 'logistics_partner'
            && $user->logisticsProfile
            && $user->logisticsProfile->isCooperative()
            && $user->logisticsProfile->is_verified;

        if (!$isCoop) {
            abort(403, 'Only verified cooperatives can manage customer distribution.');
        }

        return $next($request);
    }
}