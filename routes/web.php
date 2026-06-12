<?php

/**
 * HarvestHaul Routing Topology
 * 
 * This file defines all HTTP routes for the HarvestHaul platform.
 * Security and access control are structured via nested middleware groups:
 * 
 * 1. Public Routes: Accessible to anyone (e.g. landing page, success screens).
 * 2. Guest Group ('guest'): Registration and login. Restricted to logged-out users.
 * 3. Base Authenticated Group ('auth', 'EnsureAccountIsActive'):
 *    - Authenticated users whose accounts are active (not suspended).
 *    - Includes logout, primary dashboard switcher, and email verification notice/status.
 * 4. Verified Group ('verified'):
 *    - Only authenticated, active, and email-verified users can access these.
 *    - Nested into role-specific sub-groups:
 *      a) Farmers (EnsureUserIsFarmer): Harvest listings, yield predictor, document uploads.
 *      b) Logistics Partners (EnsureUserIsLogistics): B2B resource pooling, fleet predictor, driver/vehicle management, cost ledger.
 *      c) Drivers ('driver'): Mobile PWA views, telemetry/GPS signal streaming.
 *      d) Admin ('admin' prefix): User/compliance audit, crop hierarchy management, system logs.
 */

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

// Controllers
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\RouteOptimizationController;
use App\Http\Controllers\HarvestController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\CropManagerController;
use App\Http\Controllers\FarmerDocumentController;
use App\Http\Controllers\Admin\AdminFarmerDocumentController;
use App\Http\Controllers\LogisticsDocumentController;
use App\Http\Controllers\Admin\AdminLogisticsDocumentController;
use App\Http\Controllers\PoolingJobController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\LogisticsDriverController;
use App\Http\Controllers\LogisticsVehicleController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\CostLedgerController;
use App\Http\Controllers\PredictorController;
use App\Http\Controllers\NotificationController;

// Middleware
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureUserIsFarmer;
use App\Http\Middleware\EnsureUserIsLogistics;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('welcome');

Route::get('/email/verified', function () {
    return view('auth.verified');
})->name('verification.success');

/*
|--------------------------------------------------------------------------
| Guest Routes (Unauthenticated Users Only)
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
| Authenticated Routes (Base Security Layer)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', EnsureAccountIsActive::class])->group(function () {

    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Notifications API
    Route::get('api/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('api/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('api/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    /*
    | Email Verification Core
    |----------------------------------------------------------------------
    */
    Route::get('/verification-status', function (Request $request) {
        return response()->json(['verified' => $request->user()->hasVerifiedEmail()]);
    })->name('verification.status');

    Route::get('/email/verify', function () {
        if (auth()->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }
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
    | Verified-Only Route Domain
    |----------------------------------------------------------------------
    | All routes below require an active account and verified email address.
    */
    Route::middleware('verified')->group(function () {

        /*
        | 1.0 Farmer Platform Modules
        |------------------------------------------------------------------
        */
        Route::middleware(EnsureUserIsFarmer::class)->group(function () {
            // Harvest Listings Management
            Route::get('/harvests', [HarvestController::class, 'index'])->name('harvests.index');
            Route::get('/harvests/create', [HarvestController::class, 'create'])->name('harvests.create');
            Route::post('/harvests', [HarvestController::class, 'store'])->name('harvests.store');
            Route::get('/harvests/{id}/edit', [HarvestController::class, 'edit'])->name('harvests.edit');
            Route::put('/harvests/{id}', [HarvestController::class, 'update'])->name('harvests.update');
            Route::delete('/harvests/{id}', [HarvestController::class, 'destroy'])->name('harvests.destroy');

            // FIXED: Changed path and name to prevent collision with Logistics group
            Route::get('/farmer/proposals', [PoolingJobController::class, 'farmerProposals'])
                ->name('farmer.proposals');

            // Yield Predictor
            Route::get('/farmer/predictor', [PredictorController::class, 'farmerPredict'])
                ->name('farmer.predictor');

            // Live tracking list for farmers
            Route::get('/tracking', [TrackingController::class, 'index'])
                ->name('tracking.index');

            // Documents
            Route::get('/my-documents', [FarmerDocumentController::class, 'index'])->name('farmer.documents');
            Route::post('/my-documents', [FarmerDocumentController::class, 'store'])->name('farmer.documents.store');
            Route::delete('/my-documents/{document}', [FarmerDocumentController::class, 'destroy'])->name('farmer.documents.destroy');
        });

        /*
        |------------------------------------------------------------------
        | 2.0 Logistics Partner Modules
        |------------------------------------------------------------------
        */
        Route::middleware(EnsureUserIsLogistics::class)->group(function () {

            // Optimization Hub
            Route::get('/route-optimization', [RouteOptimizationController::class, 'index'])->name('route.optimization');

            // Business Compliance Records
            Route::get('/business-documents', [LogisticsDocumentController::class, 'index'])->name('logistics.documents');
            Route::post('/business-documents', [LogisticsDocumentController::class, 'store'])->name('logistics.documents.store');
            Route::delete('/business-documents/{document}', [LogisticsDocumentController::class, 'destroy'])->name('logistics.documents.destroy');

            // Consolidated B2B Pooling Control (Cleaned & Consolidated)
            Route::prefix('pooling')->name('pooling.')->group(function () {
                // The Official Proposal Inbox Handler
                Route::get('/proposals', [PoolingJobController::class, 'index'])->name('index'); // Maps to: /pooling/proposals (Name: pooling.index)

                // Cost Ledger — index (list all jobs) + per-job detail
                Route::get('/cost-ledger', [CostLedgerController::class, 'index'])->name('cost-ledger.index');
                Route::get('/{poolingJob}/cost-ledger', [CostLedgerController::class, 'show'])->name('cost-ledger');

                // Detailed Item Views & Logic Workers
                Route::get('/{poolingJob}', [PoolingJobController::class, 'show'])->name('show');       // Maps to: /pooling/{poolingJob}
                Route::post('/plan', [PoolingJobController::class, 'plan'])->name('plan');              // Maps to: /pooling/plan
                Route::post('/confirm', [PoolingJobController::class, 'confirm'])->name('confirm');    // Maps to: /pooling/confirm
            });

            // Fleet Predictor
            Route::get('/logistics/predictor', [PredictorController::class, 'logisticsPredict'])
                ->name('logistics.predictor');

            // Fleet Surveillance Query (Egress)
            Route::get('/tracking/{poolingJob}/latest', [TrackingController::class, 'latest'])->name('tracking.latest');

            // Driver Fleet Control
            Route::get('/drivers', [LogisticsDriverController::class, 'index'])->name('logistics.drivers.index');
            Route::get('/drivers/create', [LogisticsDriverController::class, 'create'])->name('logistics.drivers.create');
            Route::post('/drivers', [LogisticsDriverController::class, 'store'])->name('logistics.drivers.store');

            // Vehicle Fleet Control
            Route::get('/vehicles', [LogisticsVehicleController::class, 'index'])->name('logistics.vehicles.index');
            Route::get('/vehicles/create', [LogisticsVehicleController::class, 'create'])->name('logistics.vehicles.create');
            Route::post('/vehicles', [LogisticsVehicleController::class, 'store'])->name('logistics.vehicles.store');
        });

        /*
        | 3.0 Driver Portal & Mobile PWA Ingress
        |------------------------------------------------------------------
        */
        Route::middleware('driver')->prefix('driver')->name('driver.')->group(function () {
            Route::get('/', [DriverController::class, 'index'])->name('dashboard');
            Route::get('/jobs/{poolingJob}', [DriverController::class, 'show'])->name('jobs.show');
            Route::patch('/jobs/{poolingJob}/status', [DriverController::class, 'updateStatus'])->name('jobs.status');

            // Live Telemetry Signal Broadcast (Ingress)
            Route::post('/tracking/store', [TrackingController::class, 'store'])->name('tracking.store');
        });

        /*
        | 4.0 Telemetry Cross-Domain Endpoint Fallbacks
        |------------------------------------------------------------------
        */
        Route::post('/tracking/stream', [TrackingController::class, 'store'])->name('tracking.stream');

        /*
        | 5.0 Administration Console Hub
        |------------------------------------------------------------------
        | Core authentication validation handled directly inside Admin controllers.
        */
        Route::prefix('admin')->name('admin.')->group(function () {

            // Standard User Security Control
            Route::get('/users', [AdminController::class, 'users'])->name('users');
            Route::post('/users/{user}/status', [AdminController::class, 'toggleStatus'])->name('users.status');

            // Verification Modules
            Route::get('/farmers', [AdminController::class, 'farmers'])->name('farmers');
            Route::post('/farmers/{user}/verify', [AdminController::class, 'verifyFarmer'])->name('farmers.verify');
            Route::post('/farmers/{user}/reject', [AdminController::class, 'rejectFarmer'])->name('farmers.reject');

            Route::get('/farmer-documents', [AdminFarmerDocumentController::class, 'index'])->name('farmer-documents');
            Route::patch('/farmer-documents/{document}/approve', [AdminFarmerDocumentController::class, 'approve'])->name('farmer-documents.approve');
            Route::patch('/farmer-documents/{document}/reject', [AdminFarmerDocumentController::class, 'reject'])->name('farmer-documents.reject');

            Route::get('/logistics', [AdminController::class, 'logistics'])->name('logistics');
            Route::post('/logistics/{user}/verify', [AdminController::class, 'verifyLogistics'])->name('logistics.verify');
            Route::post('/logistics/{user}/reject', [AdminController::class, 'rejectLogistics'])->name('logistics.reject');

            Route::get('/logistics-documents', [AdminLogisticsDocumentController::class, 'index'])->name('logistics-documents');
            Route::patch('/logistics-documents/{document}/approve', [AdminLogisticsDocumentController::class, 'approve'])->name('logistics-documents.approve');
            Route::patch('/logistics-documents/{document}/reject', [AdminLogisticsDocumentController::class, 'reject'])->name('logistics-documents.reject');

            // Global Oversight Logs & Metrics
            Route::get('/harvests', [AdminController::class, 'harvests'])->name('harvests');
            Route::get('/drivers', [AdminController::class, 'drivers'])->name('drivers');
            Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit-logs');

            // Crop Matrix Hierarchies (Categories -> Crops -> Varieties)
            Route::prefix('crops')->name('crops.')->group(function () {
                Route::get('/', [CropManagerController::class, 'index'])->name('index');

                // Categories
                Route::post('/categories', [CropManagerController::class, 'storeCategory'])->name('categories.store');
                Route::put('/categories/{category}', [CropManagerController::class, 'updateCategory'])->name('categories.update');
                Route::delete('/categories/{category}', [CropManagerController::class, 'destroyCategory'])->name('categories.destroy');

                // Crops
                Route::post('/', [CropManagerController::class, 'storeCrop'])->name('store');
                Route::put('/{crop}', [CropManagerController::class, 'updateCrop'])->name('update');
                Route::delete('/{crop}', [CropManagerController::class, 'destroyCrop'])->name('destroy');

                // Varieties
                Route::post('/varieties', [CropManagerController::class, 'storeVariety'])->name('varieties.store');
                Route::put('/varieties/{variety}', [CropManagerController::class, 'updateVariety'])->name('varieties.update');
                Route::delete('/varieties/{variety}', [CropManagerController::class, 'destroyVariety'])->name('varieties.destroy');
            });
        });

    }); // End Verified Domain
}); // End Base Auth Domain
