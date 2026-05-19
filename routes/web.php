<?php

use Illuminate\Support\Facades\Route;
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
use App\Http\Middleware\EnsureUserIsFarmer;
use App\Http\Middleware\EnsureUserIsLogistics;
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
Route::middleware(['auth', \App\Http\Middleware\EnsureAccountIsActive::class])->group(function () {

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
    | Email Verification Routes
    */
    Route::get('/email/verify', function () {
        // If already verified, skip this page and go straight to dashboard
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
    | Verified-Only Routes
    |----------------------------------------------------------------------
    */
    Route::middleware('verified')->group(function () {
        // After
        Route::get('/route-optimization', [RouteOptimizationController::class, 'index'])
            ->name('route.optimization')
            ->middleware(\App\Http\Middleware\EnsureUserIsLogistics::class);


       Route::middleware(['auth', 'verified', EnsureUserIsFarmer::class])
       ->group(function () {
            Route::get('/harvests', [HarvestController::class, 'index'])->name('harvests.index');
            Route::get('/harvests/create', [HarvestController::class, 'create'])->name('harvests.create');
            Route::post('/harvests', [HarvestController::class, 'store'])->name('harvests.store');
            Route::get('/harvests/{id}/edit', [HarvestController::class, 'edit'])->name('harvests.edit');
            Route::put('/harvests/{id}', [HarvestController::class, 'update'])->name('harvests.update');
            Route::delete('/harvests/{id}', [HarvestController::class, 'destroy'])->name('harvests.destroy');

            // Farmer Document Management
            Route::get('/my-documents', [FarmerDocumentController::class, 'index'])->name('farmer.documents');
            Route::post('/my-documents', [FarmerDocumentController::class, 'store'])->name('farmer.documents.store');
            Route::delete('/my-documents/{document}', [FarmerDocumentController::class, 'destroy'])->name('farmer.documents.destroy');
        });

        // Logistics Partner Routes
        Route::middleware(['auth', 'verified', EnsureUserIsLogistics::class])
        ->group(function () {
                Route::get('/business-documents', [LogisticsDocumentController::class, 'index'])->name('logistics.documents');
                Route::post('/business-documents', [LogisticsDocumentController::class, 'store'])->name('logistics.documents.store');
                Route::delete('/business-documents/{document}', [LogisticsDocumentController::class, 'destroy'])->name('logistics.documents.destroy');

                // --- Pooling Jobs ---
                Route::prefix('pooling')->name('pooling.')->group(function () {
                    Route::get('/',                        [PoolingJobController::class, 'index'])   ->name('index');
                    Route::get('/{poolingJob}',            [PoolingJobController::class, 'show'])    ->name('show');
                    Route::post('/plan',                   [PoolingJobController::class, 'plan'])    ->name('plan');
                    Route::post('/confirm',                [PoolingJobController::class, 'confirm']) ->name('confirm');
                });

                // --- Real-Time Tracking ---
                Route::get('/tracking/{poolingJob}/latest', [App\Http\Controllers\TrackingController::class, 'latest'])->name('tracking.latest');
        });

        // --- Driver Portal ---
        Route::middleware(['auth', 'verified', 'driver'])->prefix('driver')->name('driver.')->group(function () {
            Route::get('/',                                  [DriverController::class, 'index'])       ->name('dashboard');
            Route::get('/jobs/{poolingJob}',                 [DriverController::class, 'show'])        ->name('jobs.show');
            Route::patch('/jobs/{poolingJob}/status',        [DriverController::class, 'updateStatus'])->name('jobs.status');

            // Fixed: Removed the extra '/driver' since the group handles the prefix
            Route::post('/tracking/store', [App\Http\Controllers\TrackingController::class, 'store'])->name('tracking.store');
        });

        //Admin Routes — role:admin middleware enforced at controller level
        Route::prefix('admin')->name('admin.')->middleware('verified')->group(function () {

            // Existing user/farmer/logistics management
            Route::get('/users',                    [AdminController::class, 'users'])->name('users');
            Route::post('/users/{user}/status',     [AdminController::class, 'toggleStatus'])->name('users.status');

            Route::get('/farmers',                  [AdminController::class, 'farmers'])->name('farmers');
            Route::post('/farmers/{user}/verify',   [AdminController::class, 'verifyFarmer'])->name('farmers.verify');
            Route::post('/farmers/{user}/reject',   [AdminController::class, 'rejectFarmer'])->name('farmers.reject');

            Route::get('/farmer-documents', [AdminFarmerDocumentController::class, 'index'])->name('farmer-documents');
            Route::patch('/farmer-documents/{document}/approve', [AdminFarmerDocumentController::class, 'approve'])->name('farmer-documents.approve');
            Route::patch('/farmer-documents/{document}/reject', [AdminFarmerDocumentController::class, 'reject'])->name('farmer-documents.reject');

            Route::get('/logistics',                [AdminController::class, 'logistics'])->name('logistics');
            Route::post('/logistics/{user}/verify', [AdminController::class, 'verifyLogistics'])->name('logistics.verify');
            Route::post('/logistics/{user}/reject', [AdminController::class, 'rejectLogistics'])->name('logistics.reject');

            Route::get('/logistics-documents', [AdminLogisticsDocumentController::class, 'index'])->name('logistics-documents');
            Route::patch('/logistics-documents/{document}/approve', [AdminLogisticsDocumentController::class, 'approve'])->name('logistics-documents.approve');
            Route::patch('/logistics-documents/{document}/reject', [AdminLogisticsDocumentController::class, 'reject'])->name('logistics-documents.reject');

            // Harvest Oversight
            Route::get('/harvests', [AdminController::class, 'harvests'])->name('harvests');

            // Driver Management
            Route::get('/drivers', [AdminController::class, 'drivers'])->name('drivers');

            // audit logsss
            Route::get('/audit-logs',               [AdminController::class, 'auditLogs'])->name('audit-logs');

            // Crop Manager — categories, crops, varieties + pricing
            Route::prefix('crops')->name('crops.')->group(function () {
                Route::get('/',    [CropManagerController::class, 'index'])->name('index');

                // Categories
                Route::post('/categories',             [CropManagerController::class, 'storeCategory'])->name('categories.store');
                Route::put('/categories/{category}',   [CropManagerController::class, 'updateCategory'])->name('categories.update');
                Route::delete('/categories/{category}',[CropManagerController::class, 'destroyCategory'])->name('categories.destroy');

                // Crops
                Route::post('/',            [CropManagerController::class, 'storeCrop'])->name('store');
                Route::put('/{crop}',       [CropManagerController::class, 'updateCrop'])->name('update');
                Route::delete('/{crop}',    [CropManagerController::class, 'destroyCrop'])->name('destroy');

                // Varieties
                Route::post('/varieties',              [CropManagerController::class, 'storeVariety'])->name('varieties.store');
                Route::put('/varieties/{variety}',     [CropManagerController::class, 'updateVariety'])->name('varieties.update');
                Route::delete('/varieties/{variety}',  [CropManagerController::class, 'destroyVariety'])->name('varieties.destroy');
            });
        });
    });


});
