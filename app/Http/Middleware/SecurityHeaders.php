<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(), microphone=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $devAsset = '';
        if (file_exists(public_path('hot'))) {
            // Local dev only (Vite dev server active): allow its origin so
            // Blade's @vite() assets and HMR can load. Production ignores this.
            $devAsset = ' http://localhost:5173 ws://localhost:5173';
        }

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com{$devAsset}; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com https://cdnjs.cloudflare.com{$devAsset}; " .
            "img-src 'self' data: https:; " .
            "font-src 'self' data: https://fonts.gstatic.com; " .
            "connect-src 'self' ws: wss: https:{$devAsset}; " .
            "object-src 'none'; " .
            "frame-ancestors 'self'; " .
            "base-uri 'self'; " .
            "form-action 'self'"
        );

        return $response;
    }
}
