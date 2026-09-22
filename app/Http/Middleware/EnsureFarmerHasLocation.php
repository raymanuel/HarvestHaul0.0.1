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
                $allowedRoutes = ['profile.show', 'profile.update', 'profile.password', 'profile.save-location', 'logout'];
                $routeName = $request->route()?->getName();

                if (!in_array($routeName, $allowedRoutes)) {
                    return redirect()->route('profile.show')
                        ->with('warning', 'Set your farm location first so your cooperative can plan pickups. Open your profile and save your location.');
                }
            }
        }

        return $next($request);
    }
}
