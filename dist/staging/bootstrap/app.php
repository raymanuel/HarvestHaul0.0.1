<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\EnsureSchedulerAlive::class);

        $middleware->alias([
            'driver' => \App\Http\Middleware\EnsureUserIsDriver::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'farmer' => \App\Http\Middleware\EnsureUserIsFarmer::class,
            'logistics' => \App\Http\Middleware\EnsureUserIsLogistics::class,
            'logistics.independent' => \App\Http\Middleware\EnsureIndependentLogistics::class,
            'buyer' => \App\Http\Middleware\EnsureUserIsBuyer::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'farmer.location' => \App\Http\Middleware\EnsureFarmerHasLocation::class,
        ]);
    })->create();

