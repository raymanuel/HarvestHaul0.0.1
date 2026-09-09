<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class EnsureIndependentLogistics
 *
 * Middleware restricting the haul-intent (farmer <-> logistics chat) flow
 * to non-cooperative logistics partners. Cooperative logistics negotiate
 * hauling through cooperative route offers / pooling, not haul intents.
 * Farmers pass through; 403 for cooperative logistics partners.
 */
class EnsureIndependentLogistics
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
        if (Auth::user()->role === 'logistics_partner'
            && Auth::user()->logisticsProfile?->isCooperative()) {
            abort(403, 'Cooperative logistics haul via route offers, not haul intents.');
        }

        return $next($request);
    }
}