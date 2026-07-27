<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureFarmerHasLocation
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->role === 'farmer') {
            $profile = $user->farmerProfile;

            $hasLocation = $profile
                && !is_null($profile->latitude)
                && !is_null($profile->longitude);

            if (!$hasLocation) {
                // Allow profile pages and logout so farmer can actually set location
                $allowedRoutes = ['profile.show', 'profile.update', 'profile.password', 'logout'];
                $routeName = $request->route()?->getName();

                if (!in_array($routeName, $allowedRoutes)) {
                    return redirect()->route('profile.show')
                        ->with('warning', 'Please set your farm location before continuing. This is required to post harvests and receive negotiation proposals.');
                }
            }
        }

        return $next($request);
    }
}
