<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\RouteOptimizationController;
use App\Http\Controllers\HarvestController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('welcome');

// Email verified success page (public — no auth needed, tab may be standalone)
Route::get('/email/verified', function () {
    return view('auth.verified');
})->name('verification.success');

/*
|--------------------------------------------------------------------------
| Guest Routes (Only for users who ARE NOT logged in)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'authenticate'])->name('login.attempt');

    Route::get('register', [RegisterController::class, 'index'])->name('register');
    Route::get('/register/{role}', [RegisterController::class, 'create'])->name('register.role');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Only for users who ARE logged in)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard (accessible even if unverified)
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Polling endpoint for verify-email page
    Route::get('/verification-status', function (Request $request) {
        return response()->json([
            'verified' => $request->user()->hasVerifiedEmail()
        ]);
    })->name('verification.status');

    /*
    |----------------------------------------------------------------------
    | Email Verification Routes
    |----------------------------------------------------------------------
    */
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('verification.success');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware('throttle:6,1')->name('verification.send');

    /*
    |----------------------------------------------------------------------
    | Verified-Only Routes (single group — no duplicates)
    |----------------------------------------------------------------------
    */
    Route::middleware('verified')->group(function () {
        Route::get('/route-optimization', [RouteOptimizationController::class, 'index'])->name('route.optimization');

        // Harvest Listings
        Route::get('/harvests', [HarvestController::class, 'index'])->name('harvests.index');
        Route::get('/harvests/create', [HarvestController::class, 'create'])->name('harvests.create');
        Route::post('/harvests', [HarvestController::class, 'store'])->name('harvests.store');
        Route::delete('/harvests/{id}', [HarvestController::class, 'destroy'])->name('harvests.destroy');
});


});
