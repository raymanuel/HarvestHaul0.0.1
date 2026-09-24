<?php

/**
 * HarvestHaul Routing Topology
 *
 * Cooperative-centered crop distribution system.
 *
 * Roles: super_admin, coop_admin, field_receiving, delivery_personnel, farmer, buyer.
 *
 * 1. Public routes — landing, legal, health.
 * 2. Guest routes — login, cooperative/buyer registration, password reset.
 * 3. Authenticated base — logout, dashboard switcher, profile, notifications, files, OTP verification.
 * 4. Verified, role-scoped domains:
 *    - super_admin      → platform oversight, cooperative + buyer verification, reference data
 *    - coop_admin       → cooperative operations (people, fleet, hauling, receiving, orders, deliveries)
 *    - field_receiving  → recording weight/grade/price at pickup
 *    - delivery_personnel → pickup + delivery runs, GPS broadcast
 *    - farmer           → haul requests, procurement/payout records
 *    - buyer            → browse crop availability, place orders, track deliveries
 */

use App\Http\Controllers\Admin\AdminAuditController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\BuyerVerificationController;
use App\Http\Controllers\Admin\CooperativeVerificationController;
use App\Http\Controllers\Admin\CropManagerController;
use App\Http\Controllers\Admin\ReferenceDataController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerifyOtpController;
use App\Http\Controllers\Buyer\DashboardController as BuyerDashboardController;
use App\Http\Controllers\Buyer\OrderController as BuyerOrderController;
use App\Http\Controllers\Coop\BuyerOrderController as CoopBuyerOrderController;
use App\Http\Controllers\Coop\CoopStatusController;
use App\Http\Controllers\Coop\CropAvailabilityController;
use App\Http\Controllers\Coop\DashboardController as CoopDashboardController;
use App\Http\Controllers\Coop\DriverManagementController;
use App\Http\Controllers\Coop\FacilityReceivingController;
use App\Http\Controllers\Coop\FieldStaffManagementController;
use App\Http\Controllers\Coop\FarmerManagementController;
use App\Http\Controllers\Coop\HaulRequestController as CoopHaulRequestController;
use App\Http\Controllers\Coop\LocationMonitoringController;
use App\Http\Controllers\Coop\OutboundDeliveryController;
use App\Http\Controllers\Coop\TruckController;
use App\Http\Controllers\Coop\PickupTripController;
use App\Http\Controllers\Coop\ProcurementController;
use App\Http\Controllers\Coop\SettingsController as CoopSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Delivery\DashboardController as DeliveryDashboardController;
use App\Http\Controllers\Delivery\TripController as DeliveryTripController;
use App\Http\Controllers\Farmer\DashboardController as FarmerDashboardController;
use App\Http\Controllers\Farmer\HaulRequestController as FarmerHaulRequestController;
use App\Http\Controllers\Field\DashboardController as FieldDashboardController;
use App\Http\Controllers\Field\ReceivingController as FieldReceivingController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureAccountIsActive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [\App\Http\Controllers\WelcomeController::class, 'index'])->name('welcome');

Route::get('/health', [HealthController::class, 'index'])->name('health');

Route::view('/legal/terms', 'legal.terms')->name('legal.terms');
Route::view('/legal/privacy', 'legal.privacy')->name('legal.privacy');

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
    Route::post('login', [LoginController::class, 'authenticate'])->middleware('throttle:15,1')->name('login.attempt');

    Route::get('register', [RegisterController::class, 'index'])->name('register');
    Route::get('/register/{role}', [RegisterController::class, 'create'])->name('register.role');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1')->name('register.store');

    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware('throttle:3,1')->name('password.email');
    Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Base Security Layer)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', EnsureAccountIsActive::class])->group(function () {

    Route::post('logout', [LoginController::class, 'logout'])->middleware('throttle:10,1')->name('logout');
    Route::get('dashboard', [DashboardController::class, 'index'])->middleware('verified')->name('dashboard');

    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [ProfileController::class, 'update'])->middleware('throttle:5,1')->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('profile/save-location', [ProfileController::class, 'saveLocation'])->name('profile.save-location');

    Route::get('api/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('api/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('api/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::get('settings/notifications', [NotificationPreferenceController::class, 'index'])->name('notifications.preferences');
    Route::put('settings/notifications', [NotificationPreferenceController::class, 'update'])->name('notifications.preferences.update');

    Route::get('files/{type}/{id}', [FileController::class, 'show'])
        ->whereIn('type', ['coop-document', 'delivery-id', 'delivery-selfie', 'load-photo', 'delivery-receipt', 'pod-photo', 'depot-pod-photo'])
        ->middleware('throttle:60,1')
        ->name('files.show');

    /*
    | Email Verification Core
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

    Route::post('/email/verify-otp', [VerifyOtpController::class, 'verify'])
        ->middleware('throttle:5,1')
        ->name('verification.verify-otp');

    Route::post('/email/resend-otp', [VerifyOtpController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.resend-otp');

    /*
    |--------------------------------------------------------------------------
    | Verified, Role-Scoped Domains
    |--------------------------------------------------------------------------
    */
    Route::middleware('verified')->group(function () {

        /*
        | 1.0 Super Admin — platform control, not cooperative operations
        */
        Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
            Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

            // Cooperative registration review
            Route::get('/cooperatives', [CooperativeVerificationController::class, 'index'])->name('cooperatives.index');
            Route::get('/cooperatives/{cooperative}', [CooperativeVerificationController::class, 'show'])->name('cooperatives.show');
            Route::post('/cooperatives/{cooperative}/approve', [CooperativeVerificationController::class, 'approve'])->name('cooperatives.approve')->middleware('throttle:30,1');
            Route::post('/cooperatives/{cooperative}/reject', [CooperativeVerificationController::class, 'reject'])->name('cooperatives.reject')->middleware('throttle:30,1');
            Route::post('/cooperatives/{cooperative}/request-info', [CooperativeVerificationController::class, 'requestInfo'])->name('cooperatives.request-info')->middleware('throttle:30,1');
            Route::post('/cooperatives/{cooperative}/suspend', [CooperativeVerificationController::class, 'suspend'])->name('cooperatives.suspend')->middleware('throttle:30,1');
            Route::post('/cooperatives/{cooperative}/reactivate', [CooperativeVerificationController::class, 'reactivate'])->name('cooperatives.reactivate')->middleware('throttle:30,1');

            // Buyer verification
            Route::get('/buyers', [BuyerVerificationController::class, 'index'])->name('buyers.index');
            Route::post('/buyers/{user}/approve', [BuyerVerificationController::class, 'approve'])->name('buyers.approve')->middleware('throttle:30,1');
            Route::post('/buyers/{user}/reject', [BuyerVerificationController::class, 'reject'])->name('buyers.reject')->middleware('throttle:30,1');
            Route::post('/buyers/{user}/suspend', [BuyerVerificationController::class, 'suspend'])->name('buyers.suspend')->middleware('throttle:30,1');
            Route::post('/buyers/{user}/reactivate', [BuyerVerificationController::class, 'reactivate'])->name('buyers.reactivate')->middleware('throttle:30,1');

            // Platform user accounts
            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::post('/users', [AdminUserController::class, 'store'])->name('users.store')->middleware('throttle:10,1');
            Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update')->middleware('throttle:30,1');
            Route::post('/users/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('users.status')->middleware('throttle:30,1');

            // System reference data
            Route::get('/reference-data', [ReferenceDataController::class, 'index'])->name('reference.index');
            Route::post('/reference-data/grades', [ReferenceDataController::class, 'storeGrade'])->name('reference.grades.store');
            Route::put('/reference-data/grades/{cropGrade}', [ReferenceDataController::class, 'updateGrade'])->name('reference.grades.update');
            Route::delete('/reference-data/grades/{cropGrade}', [ReferenceDataController::class, 'destroyGrade'])->name('reference.grades.destroy');
            Route::post('/reference-data/packaging', [ReferenceDataController::class, 'storePackaging'])->name('reference.packaging.store');
            Route::put('/reference-data/packaging/{packagingType}', [ReferenceDataController::class, 'updatePackaging'])->name('reference.packaging.update');
            Route::delete('/reference-data/packaging/{packagingType}', [ReferenceDataController::class, 'destroyPackaging'])->name('reference.packaging.destroy');

            // Market price monitoring (18)
            Route::get('/market-prices', [\App\Http\Controllers\Admin\MarketPriceController::class, 'index'])->name('market-prices.index');
            Route::post('/market-prices', [\App\Http\Controllers\Admin\MarketPriceController::class, 'store'])->name('market-prices.store');
            Route::delete('/market-prices/{marketPrice}', [\App\Http\Controllers\Admin\MarketPriceController::class, 'destroy'])->name('market-prices.destroy');

            // Crop matrix (categories → crops → varieties)
            Route::prefix('crops')->name('crops.')->group(function () {
                Route::get('/', [CropManagerController::class, 'index'])->name('index');
                Route::post('/categories', [CropManagerController::class, 'storeCategory'])->name('categories.store');
                Route::put('/categories/{category}', [CropManagerController::class, 'updateCategory'])->name('categories.update');
                Route::delete('/categories/{category}', [CropManagerController::class, 'destroyCategory'])->name('categories.destroy');
                Route::post('/', [CropManagerController::class, 'storeCrop'])->name('store');
                Route::put('/{crop}', [CropManagerController::class, 'updateCrop'])->name('update');
                Route::delete('/{crop}', [CropManagerController::class, 'destroyCrop'])->name('destroy');
                Route::post('/varieties', [CropManagerController::class, 'storeVariety'])->name('varieties.store');
                Route::put('/varieties/{variety}', [CropManagerController::class, 'updateVariety'])->name('varieties.update');
                Route::delete('/varieties/{variety}', [CropManagerController::class, 'destroyVariety'])->name('varieties.destroy');
            });

            Route::get('/audit-logs', [AdminAuditController::class, 'auditLogs'])->name('audit-logs');

            // Platform-level reports (19) — aggregate counts, not per-cooperative detail.
            Route::get('/reports', [\App\Http\Controllers\Admin\PlatformReportController::class, 'index'])->name('reports.index');
        });

        /*
        | 2.0 Cooperative Admin — manages its own cooperative
        */
        Route::prefix('coop')->name('coop.')->middleware('coop_admin')->group(function () {
            Route::get('/status', [CoopStatusController::class, 'show'])->name('status');
            Route::post('/status/resubmit', [CoopStatusController::class, 'resubmit'])->name('status.resubmit');
        });

        Route::prefix('coop')->name('coop.')->middleware('coop_admin.approved')->group(function () {
            Route::get('/', [CoopDashboardController::class, 'index'])->name('dashboard');

            Route::put('settings', [CoopSettingsController::class, 'update'])->name('settings.update');

            /*
            | Farmer membership requests (3A)
            */
            Route::prefix('farmers')->name('farmers.')->group(function () {
                Route::get('/', [FarmerManagementController::class, 'index'])->name('index');
                Route::get('/create', [FarmerManagementController::class, 'create'])->name('create');
                Route::post('/', [FarmerManagementController::class, 'store'])->name('store');
                Route::get('/import', [FarmerManagementController::class, 'importForm'])->name('import');
                Route::post('/import', [FarmerManagementController::class, 'import'])
                    ->middleware('throttle:10,1')
                    ->name('import.store');
                Route::get('/import/template', [FarmerManagementController::class, 'template'])->name('import.template');
                Route::get('/{user}', [FarmerManagementController::class, 'show'])->name('show');
                Route::get('/{user}/edit', [FarmerManagementController::class, 'edit'])->name('edit');
                Route::put('/{user}', [FarmerManagementController::class, 'update'])->name('update');
                Route::post('/{user}/approve', [FarmerManagementController::class, 'approve'])->name('approve');
                Route::post('/{user}/reject', [FarmerManagementController::class, 'reject'])->name('reject');
                Route::post('/{user}/remove', [FarmerManagementController::class, 'remove'])->name('remove');
            });

            /*
            | Drivers (delivery personnel)
            */
            Route::prefix('drivers')->name('drivers.')->group(function () {
                Route::get('/', [DriverManagementController::class, 'index'])->name('index');
                Route::get('/create', [DriverManagementController::class, 'create'])->name('create');
                Route::post('/', [DriverManagementController::class, 'store'])->name('store');
            });

            /*
            | Field/receiving staff
            */
            Route::prefix('staff')->name('staff.')->group(function () {
                Route::get('/', [FieldStaffManagementController::class, 'index'])->name('index');
                Route::get('/create', [FieldStaffManagementController::class, 'create'])->name('create');
                Route::post('/', [FieldStaffManagementController::class, 'store'])->name('store');
            });

            /*
            | Haul requests (4)
            */
            Route::prefix('haul-requests')->name('haul-requests.')->group(function () {
                Route::get('/', [CoopHaulRequestController::class, 'index'])->name('index');
                Route::get('/{haulRequest}', [CoopHaulRequestController::class, 'show'])->name('show');
                Route::post('/{haulRequest}/approve', [CoopHaulRequestController::class, 'approve'])
                    ->middleware('throttle:30,1')
                    ->name('approve');
                Route::post('/{haulRequest}/reject', [CoopHaulRequestController::class, 'reject'])
                    ->middleware('throttle:30,1')
                    ->name('reject');
            });

            /*
            | Pickup planning (5-6)
            */
            Route::prefix('pickups')->name('pickups.')->group(function () {
                Route::get('/', [PickupTripController::class, 'index'])->name('index');
                Route::get('/calendar', [PickupTripController::class, 'calendar'])->name('calendar');
                Route::get('/new', [PickupTripController::class, 'create'])->name('create');
                Route::post('/', [PickupTripController::class, 'store'])->name('store');
                Route::post('/preview', [PickupTripController::class, 'preview'])
                    ->middleware('throttle:60,1')
                    ->name('preview');
                Route::get('/{haulJob}', [PickupTripController::class, 'show'])->name('show');
                Route::put('/{haulJob}/reschedule', [PickupTripController::class, 'reschedule'])
                    ->middleware('throttle:30,1')
                    ->name('reschedule');
                Route::put('/{haulJob}/reassign', [PickupTripController::class, 'reassign'])
                    ->middleware('throttle:30,1')
                    ->name('reassign');
                Route::delete('/stops/{stop}', [PickupTripController::class, 'removeStop'])
                    ->middleware('throttle:30,1')
                    ->name('stops.remove');
                Route::post('/{haulJob}/cancel', [PickupTripController::class, 'cancel'])
                    ->middleware('throttle:30,1')
                    ->name('cancel');
            });

            /*
            | Procurement / receiving confirmation (11)
            */
            Route::prefix('procurement')->name('procurement.')->group(function () {
                Route::get('/', [ProcurementController::class, 'index'])->name('index');
                Route::get('/{receivingRecord}', [ProcurementController::class, 'show'])->name('show');
                Route::post('/{receivingRecord}/price', [ProcurementController::class, 'setPrice'])
                    ->middleware('throttle:30,1')
                    ->name('price');
                Route::post('/{receivingRecord}/confirm', [ProcurementController::class, 'confirm'])
                    ->middleware('throttle:30,1')
                    ->name('confirm');
                Route::post('/{receivingRecord}/cancel', [ProcurementController::class, 'cancel'])
                    ->middleware('throttle:30,1')
                    ->name('cancel');
                Route::post('/{receivingRecord}/payments', [ProcurementController::class, 'recordPayment'])
                    ->middleware('throttle:30,1')
                    ->name('payments.store');
            });

            /*
            | Facility receiving verification (10)
            */
            Route::prefix('facility-receiving')->name('facility-receiving.')->group(function () {
                Route::get('/', [FacilityReceivingController::class, 'index'])->name('index');
                Route::get('/{receivingRecord}', [FacilityReceivingController::class, 'show'])->name('show');
                Route::post('/{receivingRecord}/verify', [FacilityReceivingController::class, 'verify'])
                    ->middleware('throttle:30,1')
                    ->name('verify');
                Route::post('/{receivingRecord}/resolve', [FacilityReceivingController::class, 'resolve'])
                    ->middleware('throttle:30,1')
                    ->name('resolve');
            });

            /*
            | Crop availability / B2B inventory (14)
            */
            Route::prefix('availability')->name('availability.')->group(function () {
                Route::get('/', [CropAvailabilityController::class, 'index'])->name('index');
                Route::post('/{cropAvailability}/price', [CropAvailabilityController::class, 'updatePrice'])->name('price');
                Route::post('/{cropAvailability}/archive', [CropAvailabilityController::class, 'archive'])->name('archive');
                Route::post('/{cropAvailability}/restore', [CropAvailabilityController::class, 'restore'])->name('restore');
            });

            /*
            | B2B buyer order review (13)
            */
            Route::prefix('buyer-orders')->name('buyer-orders.')->group(function () {
                Route::get('/', [CoopBuyerOrderController::class, 'index'])->name('index');
                Route::get('/{buyerOrder}', [CoopBuyerOrderController::class, 'show'])->name('show');
                Route::post('/{buyerOrder}/accept', [CoopBuyerOrderController::class, 'accept'])
                    ->middleware('throttle:30,1')
                    ->name('accept');
                Route::post('/{buyerOrder}/payments', [CoopBuyerOrderController::class, 'recordPayment'])
                    ->middleware('throttle:30,1')
                    ->name('payments.store');
                Route::post('/{buyerOrder}/reject', [CoopBuyerOrderController::class, 'reject'])
                    ->middleware('throttle:30,1')
                    ->name('reject');
            });

            /*
            | Outbound delivery planning (14)
            */
            Route::prefix('outbound')->name('outbound.')->group(function () {
                Route::get('/', [OutboundDeliveryController::class, 'index'])->name('index');
                Route::get('/create', [OutboundDeliveryController::class, 'create'])->name('create');
                Route::post('/', [OutboundDeliveryController::class, 'store'])
                    ->middleware('throttle:20,1')
                    ->name('store');
                Route::get('/{haulJob}', [OutboundDeliveryController::class, 'show'])->name('show');
            });

            /*
            | Location monitoring (16)
            */
            Route::prefix('tracking')->name('tracking.')->group(function () {
                Route::get('/', [LocationMonitoringController::class, 'index'])->name('index');
                Route::get('/{haulJob}', [LocationMonitoringController::class, 'show'])->name('show');
                Route::get('/{haulJob}/location', [LocationMonitoringController::class, 'location'])->name('location');
            });

            /*
            | Reporting (19)
            */
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Coop\ReportController::class, 'index'])->name('index');
                Route::get('/procurement', [\App\Http\Controllers\Coop\ReportController::class, 'procurement'])->name('procurement');
                Route::get('/procurement/csv', [\App\Http\Controllers\Coop\ReportController::class, 'procurementCsv'])->name('procurement.csv');
                Route::get('/procurement/pdf', [\App\Http\Controllers\Coop\ReportController::class, 'procurementPdf'])->name('procurement.pdf');
                Route::get('/sales', [\App\Http\Controllers\Coop\ReportController::class, 'sales'])->name('sales');
                Route::get('/sales/csv', [\App\Http\Controllers\Coop\ReportController::class, 'salesCsv'])->name('sales.csv');
                Route::get('/sales/pdf', [\App\Http\Controllers\Coop\ReportController::class, 'salesPdf'])->name('sales.pdf');
                Route::get('/payouts', [\App\Http\Controllers\Coop\ReportController::class, 'payouts'])->name('payouts');
                Route::get('/payouts/csv', [\App\Http\Controllers\Coop\ReportController::class, 'payoutsCsv'])->name('payouts.csv');
                Route::get('/payouts/pdf', [\App\Http\Controllers\Coop\ReportController::class, 'payoutsPdf'])->name('payouts.pdf');
                Route::get('/deliveries', [\App\Http\Controllers\Coop\ReportController::class, 'deliveries'])->name('deliveries');
                Route::get('/deliveries/csv', [\App\Http\Controllers\Coop\ReportController::class, 'deliveriesCsv'])->name('deliveries.csv');
                Route::get('/deliveries/pdf', [\App\Http\Controllers\Coop\ReportController::class, 'deliveriesPdf'])->name('deliveries.pdf');
                Route::get('/consolidation', [\App\Http\Controllers\Coop\ReportController::class, 'consolidation'])->name('consolidation');
                Route::get('/consolidation/csv', [\App\Http\Controllers\Coop\ReportController::class, 'consolidationCsv'])->name('consolidation.csv');
                Route::get('/consolidation/pdf', [\App\Http\Controllers\Coop\ReportController::class, 'consolidationPdf'])->name('consolidation.pdf');
            });

            /*
            | Truck fleet registry (3B)
            */
            Route::prefix('trucks')->name('trucks.')->group(function () {
                Route::get('/', [TruckController::class, 'index'])->name('index');
                Route::post('/', [TruckController::class, 'store'])->name('store');
                Route::put('/{truck}', [TruckController::class, 'update'])->name('update');
                Route::post('/{truck}/status/{status}', [TruckController::class, 'toggleStatus'])->name('status');
                Route::delete('/{truck}', [TruckController::class, 'destroy'])->name('destroy');
            });

        });

        /*
        | 3.0 Field / Receiving Personnel
        */
        Route::prefix('field')->name('field.')->middleware('field_personnel')->group(function () {
            Route::get('/', [FieldDashboardController::class, 'index'])->name('dashboard');

            Route::prefix('receiving')->name('receiving.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Field\ReceivingController::class, 'index'])->name('index');
                Route::get('/jobs/{haulJob}', [\App\Http\Controllers\Field\ReceivingController::class, 'show'])->name('show');
                Route::get('/jobs/{haulJob}/stops/{stop}/receive', [\App\Http\Controllers\Field\ReceivingController::class, 'create'])->name('create');
                Route::post('/jobs/{haulJob}/stops/{stop}/receive', [\App\Http\Controllers\Field\ReceivingController::class, 'store'])->name('store');
            });
        });

        /*
        | 4.0 Delivery Personnel
        */
        Route::prefix('delivery')->name('delivery.')->middleware('delivery')->group(function () {
            Route::get('/', [DeliveryDashboardController::class, 'index'])->name('dashboard');

            Route::prefix('trips')->name('trips.')->group(function () {
                Route::get('/', [DeliveryTripController::class, 'index'])->name('index');
                Route::get('/{haulJob}', [DeliveryTripController::class, 'show'])->name('show');
                Route::post('/stops/{stop}/{status}', [DeliveryTripController::class, 'updateStopStatus'])
                    ->whereIn('status', ['arrived', 'picked_up', 'delivered', 'skipped', 'failed'])
                    ->middleware('throttle:60,1')
                    ->name('stop-status');
                Route::post('/{haulJob}/complete', [DeliveryTripController::class, 'complete'])
                    ->middleware('throttle:20,1')
                    ->name('complete');
                Route::post('/{haulJob}/location', [DeliveryTripController::class, 'postLocation'])
                    ->middleware('throttle:60,1')
                    ->name('location');
            });
        });

        /*
        | 5.0 Farmer
        */
        Route::prefix('farmer')->name('farmer.')->middleware('farmer')->group(function () {
            Route::get('/', [FarmerDashboardController::class, 'index'])->name('dashboard');

            Route::prefix('haul-requests')->name('haul-requests.')->group(function () {
                Route::get('/', [FarmerHaulRequestController::class, 'index'])->name('index');
                Route::get('/new', [FarmerHaulRequestController::class, 'create'])->name('create');
                Route::post('/', [FarmerHaulRequestController::class, 'store'])->name('store');
                Route::post('/{haulRequest}/cancel', [FarmerHaulRequestController::class, 'cancel'])
                    ->middleware('throttle:20,1')
                    ->name('cancel');
                Route::get('/{haulRequest}/track', [FarmerHaulRequestController::class, 'track'])->name('track');
                Route::get('/{haulRequest}/track/location', [FarmerHaulRequestController::class, 'trackLocation'])->name('track.location');
            });

            Route::prefix('join-cooperative')->name('join-cooperative.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Farmer\CooperativeMembershipController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Farmer\CooperativeMembershipController::class, 'store'])
                    ->middleware('throttle:10,1')
                    ->name('store');
            });
        });

        /*
        | 6.0 Buyer
        */
        Route::prefix('buyer')->name('buyer.')->middleware('buyer')->group(function () {
            Route::get('/', [BuyerDashboardController::class, 'index'])->name('dashboard');

            Route::prefix('listings')->name('listings.')->group(function () {
                Route::get('/', [BuyerOrderController::class, 'browse'])->name('index');
                Route::get('/{cropAvailability}', [BuyerOrderController::class, 'show'])->name('show');
            });

            Route::prefix('orders')->name('orders.')->group(function () {
                Route::get('/', [BuyerOrderController::class, 'index'])->name('index');
                Route::get('/{buyerOrder}', [BuyerOrderController::class, 'showOrder'])->name('show');
                Route::post('/', [BuyerOrderController::class, 'store'])
                    ->middleware('throttle:20,1')
                    ->name('store');
                Route::get('/{buyerOrder}/track', [BuyerOrderController::class, 'track'])->name('track');
                Route::get('/{buyerOrder}/track/location', [BuyerOrderController::class, 'trackLocation'])->name('track.location');
                Route::post('/{buyerOrder}/confirm-receipt', [BuyerOrderController::class, 'confirmReceipt'])
                    ->middleware('throttle:20,1')
                    ->name('confirm-receipt');
            });
        });

        /*
        | 7.0 In-app messaging (farmer ↔ coop, delivery ↔ coop; coop-scoped)
        */
        Route::prefix('messages')->name('messages.')->middleware('auth')->group(function () {
            Route::get('/', [\App\Http\Controllers\MessageController::class, 'index'])->name('index');
            Route::get('/{conversation}', [\App\Http\Controllers\MessageController::class, 'show'])->name('show');
            Route::post('/{conversation}/messages', [\App\Http\Controllers\MessageController::class, 'store'])->name('store');
            Route::get('/{conversation}/poll', [\App\Http\Controllers\MessageController::class, 'poll'])->name('poll');
        });
    });
});
