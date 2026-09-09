<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsBuyer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        $isBuyer = $user && $user->role === 'buyer';
        $isCooperativeLogistics = false;

        if ($user && $user->role === 'logistics_partner') {
            // Load logisticsProfile once if not already loaded
            if (!$user->relationLoaded('logisticsProfile')) {
                $user->load('logisticsProfile');
            }
            $isCooperativeLogistics = $user->logisticsProfile
                && $user->logisticsProfile->isCooperative();
        }

        if (!$isBuyer && !$isCooperativeLogistics) {
            abort(403, 'Unauthorized. Buyer access only.');
        }

        return $next($request);
    }
}
