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
 *      a) Farmers (EnsureUserIsFarmer): Harvest posts, document uploads.
 *      b) Logistics Partners (EnsureUserIsLogistics): B2B resource pooling, fleet capacity, driver/vehicle management, cost ledger.
 *      c) Drivers ('driver'): Mobile PWA views, telemetry/GPS signal streaming.
 *      d) Admin ('admin' prefix): User/compliance audit, crop hierarchy management, system logs.
 */

use App\Http\Controllers\Admin\AdminFarmerDocumentController;
use App\Http\Controllers\Admin\AdminLogisticsDocumentController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminVerificationController;
use App\Http\Controllers\Admin\AdminHarvestController;
use App\Http\Controllers\Admin\AdminAuditController;
use App\Http\Controllers\FarmerExpenseController;
use App\Http\Controllers\Admin\CropManagerController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerifyOtpController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\CapacityController;
use App\Http\Controllers\CostLedgerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\FarmerDocumentController;
use App\Http\Controllers\FarmerLogisticsController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\HarvestController;
use App\Http\Controllers\HaulNegotiationController;
use App\Http\Controllers\HaulRequestController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogisticsDocumentController;
use App\Http\Controllers\LogisticsDriverController;
use App\Http\Controllers\LogisticsVehicleController;

use App\Http\Controllers\NegotiationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\OutboundCustomerController;
use App\Http\Controllers\OutboundOrderController;
use App\Http\Controllers\OutboundTrackController;
use App\Http\Controllers\PoolingJobController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
// Middleware
use App\Http\Controllers\RouteOptimizationController;
use App\Http\Controllers\TrackingController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureUserIsBuyer;
use App\Http\Middleware\EnsureUserIsFarmer;
use App\Http\Middleware\EnsureUserIsLogistics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('welcome');

// Lightweight health check for uptime monitors (UptimeRobot, cron-job.org, etc.).
// Reports DB status, latest stored price date, and whether the hourly scraper heartbeat is alive.
Route::get('/health', [HealthController::class, 'index'])->name('health');

Route::view('/legal/terms', 'legal.terms')->name('legal.terms');
Route::view('/legal/privacy', 'legal.privacy')->name('legal.privacy');

Route::get('/email/verified', function () {
    return view('auth.verified');
})->name('verification.success');

/*
|--------------------------------------------------------------------------
| Public Outbound Customer Tracking (Anonymous — uses opaque token, never IDs)
|--------------------------------------------------------------------------
*/
Route::get('/track-out/{token}', [OutboundTrackController::class, 'show'])->name('outbound.track');
Route::get('/track-out/{token}/ping', [OutboundTrackController::class, 'ping'])->name('outbound.track.ping');
Route::post('/track-out/{token}/confirm', [OutboundTrackController::class, 'confirm'])
    ->name('outbound.track.confirm')->middleware('throttle:15,1');

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

    // Password Reset
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
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Management
    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('profile/save-location', [ProfileController::class, 'saveLocation'])->name('profile.save-location');

    // Logistics Route Pricing
    Route::get('profile/route-pricing', [ProfileController::class, 'showRoutePricing'])->name('profile.route-pricing');
    Route::post('profile/route-pricing', [ProfileController::class, 'updateRoutePricing'])->name('profile.route-pricing.update');

    // Notifications API
    Route::get('api/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('api/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('api/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Notification Preferences
    Route::get('settings/notifications', [NotificationPreferenceController::class, 'index'])->name('notifications.preferences');
    Route::put('settings/notifications', [NotificationPreferenceController::class, 'update'])->name('notifications.preferences.update');

    // Private file serving (IDs, receipts, load photos — stored on the private disk)
    Route::get('files/{type}/{id}', [FileController::class, 'show'])
        ->whereIn('type', ['farmer-document', 'logistics-document', 'driver-id', 'driver-selfie', 'payment-receipt', 'load-photo', 'delivery-receipt'])
        ->middleware('throttle:60,1')
        ->name('files.show');

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

    Route::post('/email/verify-otp', [VerifyOtpController::class, 'verify'])
        ->middleware('throttle:5,1')
        ->name('verification.verify-otp');

    Route::post('/email/resend-otp', [VerifyOtpController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.resend-otp');

    /*
    |----------------------------------------------------------------------
    | Verified-Only Route Domain
    |----------------------------------------------------------------------
    | All routes below require an active account and verified email address.
    */
    Route::middleware(['verified', 'farmer.location'])->group(function () {

        /*
        | 1.0 Farmer Platform Modules
        |------------------------------------------------------------------
        */
        Route::middleware(EnsureUserIsFarmer::class)->group(function () {
            // Harvest Posts Management
            Route::resource('harvests', HarvestController::class)->except(['show']);
            Route::get('harvests/{harvest}', [HarvestController::class, 'show'])->name('harvests.show');
            Route::post('harvests/{harvest}/mark-as-sold', [HarvestController::class, 'markAsSold'])->name('harvests.mark-as-sold')->middleware('throttle:10,10');

            // FIXED: Changed path and name to prevent collision with Logistics group
            Route::get('/farmer/proposals', [PoolingJobController::class, 'farmerProposals'])
                ->name('farmer.proposals');

            // Haul Requests for sold-outside-platform harvests
            Route::post('harvests/{harvest}/request-haul', [HaulRequestController::class,
                'create'])->name('harvests.request-haul')->middleware('throttle:10,10');
            Route::get('/farmer/haul-requests', [HaulRequestController::class, 'farmerHaulRequests'])->name('farmer.haul-requests');
            Route::post('/haul-intents/{haulIntent}/accept', [HaulRequestController::class, 'acceptIntent'])->name('haul-intents.accept')->middleware('throttle:20,10');
            Route::post('/haul-intents/{haulIntent}/decline', [HaulRequestController::class, 'declineIntent'])->name('haul-intents.decline')->middleware('throttle:20,10');

            // Farmer logistics monitoring
            Route::get('/farmer/logistics', [FarmerLogisticsController::class, 'index'])
                ->name('farmer.logistics');

            // Documents
            Route::get('/my-documents', [FarmerDocumentController::class, 'index'])->name('farmer.documents');
            Route::post('/my-documents', [FarmerDocumentController::class, 'store'])->name('farmer.documents.store');
            Route::delete('/my-documents/{document}', [FarmerDocumentController::class, 'destroy'])->name('farmer.documents.destroy');

            // Farmer Negotiations List
            Route::get('/farmer/negotiations', [NegotiationController::class, 'farmerNegotiations'])->name('farmer.negotiations');
            // Farmer Deal Room (unified crop + haul)
            Route::get('/farmer/deals/{negotiation}', [NegotiationController::class, 'dealRoom'])->name('farmer.deal-room');

            // Farmer Reports
            Route::get('/farmer/reports/profit-expense', [ReportController::class, 'farmerProfitExpense'])->name('farmer.reports.profit-expense');
            Route::get('/farmer/reports/profit-expense/download', [ReportController::class, 'farmerProfitExpenseDownload'])->name('farmer.reports.profit-expense.download');
            Route::get('/farmer/reports/sales', [ReportController::class, 'farmerSales'])->name('farmer.reports.sales');

            // Farmer Expense Logbook
            Route::get('/farmer/expenses', [FarmerExpenseController::class, 'index'])->name('farmer.expenses');
            Route::post('/farmer/expenses', [FarmerExpenseController::class, 'store'])->name('farmer.expenses.store');
            Route::delete('/farmer/expenses/{expense}', [FarmerExpenseController::class, 'destroy'])->name('farmer.expenses.destroy');

            // Join Cooperative
            Route::get('join-cooperative', [\App\Http\Controllers\JoinCooperativeController::class, 'index'])->name('farmer.join-cooperative.index');
            Route::delete('join-cooperative/leave', [\App\Http\Controllers\JoinCooperativeController::class, 'leave'])->name('farmer.join-cooperative.leave');
            Route::post('join-cooperative/{cooperative}', [\App\Http\Controllers\JoinCooperativeController::class, 'request'])->name('farmer.join-cooperative.request')->whereNumber('cooperative');
            Route::delete('join-cooperative/{cooperative}', [\App\Http\Controllers\JoinCooperativeController::class, 'cancel'])->name('farmer.join-cooperative.cancel')->whereNumber('cooperative');
        });

        // Farmer <-> Logistics in-app haul negotiation (chat + rate offers)
        Route::middleware(['role:farmer,logistics_partner', 'logistics.independent'])->prefix('haul-negotiations')->name('haul-negotiations.')->group(function () {
            Route::get('/{haulIntent}', [HaulNegotiationController::class, 'room'])->name('room');
            Route::post('/{haulIntent}/message', [HaulNegotiationController::class, 'sendMessage'])->middleware('throttle:15,1')->name('message');
            Route::get('/{haulIntent}/messages', [HaulNegotiationController::class, 'getMessages'])->name('messages');
            Route::post('/{haulIntent}/propose-rate', [HaulNegotiationController::class, 'proposeRate'])->middleware('throttle:15,1')->name('propose-rate');
            Route::post('/{haulIntent}/counter-rate', [HaulNegotiationController::class, 'counterRate'])->middleware('throttle:15,1')->name('counter-rate');
            Route::post('/{haulIntent}/agree', [HaulNegotiationController::class, 'agree'])->middleware('throttle:15,1')->name('agree');
        });

        // Full Market Prices Page (accessible to all verified users)
        Route::get('/market-prices', [DashboardController::class, 'fullPrices'])->name('prices.full');

        /*
        |------------------------------------------------------------------
        | 2.0 Logistics Partner Modules
        |------------------------------------------------------------------
        */
        Route::middleware(EnsureUserIsLogistics::class)->group(function () {

            // Optimization Hub
            Route::get('/route-optimization', [RouteOptimizationController::class, 'index'])->name('route.optimization');
            Route::get('/logistics/analytics', [CostLedgerController::class, 'fleetAnalytics'])->name('logistics.analytics');

            // Logistics Reports
            Route::get('/logistics/reports/trips', [ReportController::class, 'logisticsTrips'])->name('logistics.reports.trips');

            // Business Compliance Records
            Route::get('/business-documents', [LogisticsDocumentController::class, 'index'])->name('logistics.documents');
            Route::post('/business-documents', [LogisticsDocumentController::class, 'store'])->name('logistics.documents.store');
            Route::delete('/business-documents/{document}', [LogisticsDocumentController::class, 'destroy'])->name('logistics.documents.destroy');

            // Consolidated B2B Pooling Control (Cleaned & Consolidated)
            Route::prefix('pooling')->name('pooling.')->group(function () {
                // The Official Proposal Inbox Handler
                Route::get('/proposals', [PoolingJobController::class, 'index'])->name('index'); // Maps to: /pooling/proposals (Name: pooling.index)

                // Cost Ledger — index (list all jobs for the logistics partner)
                Route::get('/cost-ledger/jobs', [CostLedgerController::class, 'index'])->name('cost-ledger.index');

                // Detailed Item Views & Logic Workers
                Route::get('/{poolingJob}', [PoolingJobController::class, 'show'])->name('show')->whereNumber('poolingJob');       // Maps to: /pooling/{poolingJob}
                Route::post('/plan', [PoolingJobController::class, 'plan'])->name('plan')->middleware('throttle:30,10');    // Maps to: /pooling/plan
                Route::post('/confirm', [PoolingJobController::class, 'confirm'])->name('confirm')->middleware('throttle:10,10');    // Maps to: /pooling/confirm
                Route::post('/plan-all', [PoolingJobController::class, 'planAll'])->name('planAll')->middleware('throttle:30,10');
                Route::post('/confirm-batch', [PoolingJobController::class, 'confirmBatch'])->name('confirmBatch')->middleware('throttle:10,10');
            });

            // Haul Requests - logistics expresses intent
            Route::post('/haul-requests/{haulRequest}/express-intent', [HaulRequestController::class, 'expressIntent'])->name('haul-requests.express-intent')->middleware('throttle:10,10', 'logistics.independent');

            // Logistics haul-negotiation inbox
            Route::get('/logistics/haul-negotiations', [HaulNegotiationController::class, 'logisticsInbox'])->name('logistics.haul-negotiations');

            // Fleet Capacity
            Route::get('/logistics/capacity', [CapacityController::class, 'capacity'])
                ->name('logistics.capacity');

            // Auto-assign nearest available driver
            Route::post('/route-optimization/auto-assign-driver', [RouteOptimizationController::class, 'autoAssignDriver'])->name('route.auto-assign-driver')->middleware('throttle:15,1');

            // Manual driver assignment for a specific truck
            Route::post('/route-optimization/assign-driver', [RouteOptimizationController::class, 'assignDriver'])
                ->name('route-optimization.assign-driver')->middleware('throttle:15,1');

            // Driver Fleet Control
            Route::get('/drivers', [LogisticsDriverController::class, 'index'])->name('logistics.drivers.index');
            Route::get('/drivers/create', [LogisticsDriverController::class, 'create'])->name('logistics.drivers.create');
            Route::post('/drivers', [LogisticsDriverController::class, 'store'])->name('logistics.drivers.store');

            // Vehicle Fleet Control
            Route::get('/vehicles', [LogisticsVehicleController::class, 'index'])->name('logistics.vehicles.index');
            Route::get('/vehicles/create', [LogisticsVehicleController::class, 'create'])->name('logistics.vehicles.create');
            Route::post('/vehicles', [LogisticsVehicleController::class, 'store'])->name('logistics.vehicles.store');

            // Cooperative Members Management
            Route::get('logistics/members', [\App\Http\Controllers\CooperativeMembersController::class, 'index'])->name('logistics.members.index');
            Route::post('logistics/members/{farmerProfile}/approve', [\App\Http\Controllers\CooperativeMembersController::class, 'approve'])->name('logistics.members.approve');
            Route::post('logistics/members/{farmerProfile}/reject', [\App\Http\Controllers\CooperativeMembersController::class, 'reject'])->name('logistics.members.reject');
            Route::post('logistics/members/{farmerProfile}/remove', [\App\Http\Controllers\CooperativeMembersController::class, 'remove'])->name('logistics.members.remove');
        });

        /*
        | 3.0 Driver Portal & Mobile PWA Ingress
        |------------------------------------------------------------------
        */
        /*
        | Fleet Surveillance Egress (shared by farmer / buyer / logistics)
        |------------------------------------------------------------------
        */
        Route::middleware(['role:farmer,buyer,logistics_partner'])->group(function () {
            Route::get('/tracking', [TrackingController::class, 'index'])
                ->name('tracking.index');
            Route::get('/tracking/{poolingJob}/latest', [TrackingController::class, 'latest'])->name('tracking.latest');
            Route::get('/tracking/{poolingJob}/eta', [TrackingController::class, 'eta'])->name('tracking.eta');
        });

        Route::middleware('driver')->prefix('driver')->name('driver.')->group(function () {
            Route::get('/', [DriverController::class, 'index'])->name('dashboard');
            Route::get('/jobs/{poolingJob}', [DriverController::class, 'show'])->name('jobs.show');
            Route::patch('/jobs/{poolingJob}/status', [DriverController::class, 'updateStatus'])->name('jobs.status');
            Route::patch('/jobs/{poolingJob}/harvests/{harvest}/status', [DriverController::class, 'updateStopStatus'])->name('jobs.stop.status');
            Route::post('/jobs/{poolingJob}/fuel-log', [DriverController::class, 'storeFuelLog'])->name('jobs.fuel-log');
            Route::post('/jobs/{poolingJob}/accept', [DriverController::class, 'acceptJob'])->name('jobs.accept');
            Route::post('/jobs/{poolingJob}/outbound-delivered', [DriverController::class, 'markOutboundDelivered'])->name('jobs.outbound-delivered')->middleware('throttle:15,1');
            Route::post('/identity-upload', [DriverController::class, 'uploadIdentity'])->name('identity.upload');

            // Live Telemetry Signal Broadcast (Ingress) — rate limited to 12 req/min per driver
            Route::post('/tracking/store', [TrackingController::class, 'store'])->name('tracking.store')->middleware('throttle:12,1');
        });

        /*
        |------------------------------------------------------------------
        | 3.5b Outbound Distribution (Coop → Customer)
        |------------------------------------------------------------------
        */
        Route::middleware('coop')->prefix('coop')->name('coop.')->group(function () {
            Route::get('/customers', [OutboundCustomerController::class, 'index'])->name('customers.index');
            Route::get('/customers/create', [OutboundCustomerController::class, 'create'])->name('customers.create');
            Route::post('/customers', [OutboundCustomerController::class, 'store'])->name('customers.store')->middleware('throttle:10,1');
            Route::get('/customers/{customerCard}/edit', [OutboundCustomerController::class, 'edit'])->name('customers.edit');
            Route::put('/customers/{customerCard}', [OutboundCustomerController::class, 'update'])->name('customers.update')->middleware('throttle:30,1');
            Route::delete('/customers/{customerCard}', [OutboundCustomerController::class, 'destroy'])->name('customers.destroy')->middleware('throttle:10,1');

            Route::get('/outbound', [OutboundOrderController::class, 'index'])->name('outbound.index');
            Route::get('/outbound/create', [OutboundOrderController::class, 'create'])->name('outbound.create');
            Route::post('/outbound', [OutboundOrderController::class, 'store'])->name('outbound.store')->middleware('throttle:10,1');
            Route::get('/outbound/{outboundOrder}', [OutboundOrderController::class, 'show'])->name('outbound.show');
            Route::post('/outbound/{outboundOrder}/cancel', [OutboundOrderController::class, 'cancel'])->name('outbound.cancel')->middleware('throttle:10,1');
            Route::post('/outbound/{outboundOrder}/dispatch', [OutboundOrderController::class, 'dispatch'])->name('outbound.dispatch')->middleware('throttle:10,1');
        });

        /*
        |------------------------------------------------------------------
        | 3.5 Buyer Platform Modules
        |------------------------------------------------------------------
        */
        Route::middleware(EnsureUserIsBuyer::class)->prefix('buyer')->name('buyer.')->group(function () {
            Route::get('/crop-board', [BuyerController::class, 'cropBoard'])->name('crop-board');
            Route::get('/crop-board/json', [BuyerController::class, 'cropBoardJson'])->name('crop-board.json');
            Route::get('/crop-board/{harvest}', [BuyerController::class, 'showCropDetail'])->name('crop-board.show');
            Route::get('/negotiations', [BuyerController::class, 'negotiations'])->name('negotiations');
            Route::get('/tracking', [BuyerController::class, 'tracking'])->name('tracking');
            Route::post('/deliveries/{poolingJob}/confirm', [BuyerController::class, 'confirmReceipt'])->name('confirm-receipt')->middleware('throttle:15,1');
        });

        /*
        |------------------------------------------------------------------
        | 3.6 Crop Negotiation shared routes (Farmer <-> Buyer)
        |------------------------------------------------------------------
        */
        Route::prefix('negotiations')->name('negotiations.')->middleware(['role:farmer,buyer,logistics_partner'])->group(function () {
            Route::post('/start', [NegotiationController::class, 'start'])->name('start')->middleware('throttle:10,1');
            Route::get('/list', [NegotiationController::class, 'listJson'])->name('list');
            Route::get('/{negotiation}', [NegotiationController::class, 'room'])->name('room');
            Route::post('/{negotiation}/message', [NegotiationController::class, 'sendMessage'])->name('message')->middleware('throttle:15,1');
            Route::post('/{negotiation}/propose', [NegotiationController::class, 'proposeTerms'])->name('propose')->middleware('throttle:10,1');
            Route::post('/{negotiation}/agree', [NegotiationController::class, 'agreeTerms'])->name('agree')->middleware('throttle:10,1');
            Route::post('/{negotiation}/finalize', [NegotiationController::class, 'finalizeDeal'])->name('finalize')->middleware('throttle:5,1');
            Route::post('/{negotiation}/cancel', [NegotiationController::class, 'cancelDeal'])->name('cancel')->middleware('throttle:10,1');
            Route::get('/{negotiation}/messages', [NegotiationController::class, 'getMessages'])->name('messages');
        });

        /*
        |------------------------------------------------------------------
        | 3.7 Cost Ledger shared routes (Farmer <-> Logistics)
        |------------------------------------------------------------------
        */
        Route::middleware(['role:farmer,logistics_partner'])->group(function () {
            Route::get('/pooling/cost-ledger', [CostLedgerController::class, 'farmerIndex'])->middleware('role:farmer')->name('pooling.cost-ledger.farmer-index');
            Route::get('/pooling/{poolingJob}/cost-ledger', [CostLedgerController::class, 'show'])->name('pooling.cost-ledger');
            Route::post('/pooling/{poolingJob}/cost-ledger/{harvestId}/upload-receipt', [CostLedgerController::class, 'uploadReceipt'])->middleware('throttle:10,1')->name('pooling.cost-ledger.upload-receipt');
            Route::post('/pooling/{poolingJob}/cost-ledger/{harvestId}/mark-paid', [CostLedgerController::class, 'markPaid'])->middleware('throttle:10,1')->name('pooling.cost-ledger.mark-paid');
            Route::post('/pooling/{poolingJob}/cost-ledger/{harvestId}/confirm-quantity', [CostLedgerController::class, 'confirmQuantity'])->middleware('throttle:10,1')->name('pooling.cost-ledger.confirm-quantity');
        });

        Route::middleware(['role:farmer,logistics_partner'])->group(function () {
            Route::post('/pooling/{poolingJob}/accept', [PoolingJobController::class, 'acceptProposal'])->name('pooling.accept')->middleware('throttle:30,1', 'role:farmer');
            Route::post('/pooling/{poolingJob}/reject', [PoolingJobController::class, 'rejectProposal'])->name('pooling.reject')->middleware('throttle:30,1', 'role:farmer');
            Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
        });

        /*
        | 4.0 Telemetry Cross-Domain Endpoint Fallbacks
        |------------------------------------------------------------------
        */
        Route::post('/tracking/stream', [TrackingController::class, 'store'])->name('tracking.stream')->middleware(['driver', 'throttle:12,1']);

        /*
        | 5.0 Administration Console Hub
        |------------------------------------------------------------------
        | Core authentication validation handled directly inside Admin controllers.
        */
        Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {

            // Standard User Security Control
            Route::get('/users', [AdminUserController::class, 'users'])->name('users');
            Route::post('/users/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('users.status')->middleware('throttle:30,1');
            Route::post('/users', [AdminUserController::class, 'storeUser'])->name('users.store')->middleware('throttle:10,1');
            Route::put('/users/{user}', [AdminUserController::class, 'updateUser'])->name('users.update')->middleware('throttle:30,1');

            // Verification Modules
            Route::get('/farmers', [AdminVerificationController::class, 'farmers'])->name('farmers');
            Route::post('/farmers/{user}/verify', [AdminVerificationController::class, 'verifyFarmer'])->name('farmers.verify')->middleware('throttle:30,1');
            Route::post('/farmers/{user}/reject', [AdminVerificationController::class, 'rejectFarmer'])->name('farmers.reject')->middleware('throttle:30,1');

            Route::get('/farmer-documents', [AdminFarmerDocumentController::class, 'index'])->name('farmer-documents');
            Route::patch('/farmer-documents/{document}/approve', [AdminFarmerDocumentController::class, 'approve'])->middleware('throttle:30,1')->name('farmer-documents.approve');
            Route::patch('/farmer-documents/{document}/reject', [AdminFarmerDocumentController::class, 'reject'])->middleware('throttle:30,1')->name('farmer-documents.reject');

            Route::get('/logistics', [AdminVerificationController::class, 'logistics'])->name('logistics');
            Route::post('/logistics/{user}/verify', [AdminVerificationController::class, 'verifyLogistics'])->name('logistics.verify')->middleware('throttle:30,1');
            Route::post('/logistics/{user}/reject', [AdminVerificationController::class, 'rejectLogistics'])->name('logistics.reject')->middleware('throttle:30,1');

            Route::get('/logistics-documents', [AdminLogisticsDocumentController::class, 'index'])->name('logistics-documents');
            Route::patch('/logistics-documents/{document}/approve', [AdminLogisticsDocumentController::class, 'approve'])->middleware('throttle:30,1')->name('logistics-documents.approve');
            Route::patch('/logistics-documents/{document}/reject', [AdminLogisticsDocumentController::class, 'reject'])->middleware('throttle:30,1')->name('logistics-documents.reject');

            Route::get('/buyers', [AdminVerificationController::class, 'buyers'])->name('buyers');
            Route::post('/buyers/{user}/verify', [AdminVerificationController::class, 'verifyBuyer'])->name('buyers.verify')->middleware('throttle:30,1');
            Route::post('/buyers/{user}/reject', [AdminVerificationController::class, 'rejectBuyer'])->name('buyers.reject')->middleware('throttle:30,1');

            // Global Oversight Logs & Metrics
            Route::get('/harvests', [AdminHarvestController::class, 'harvests'])->name('harvests');
            Route::get('/drivers', [AdminVerificationController::class, 'drivers'])->name('drivers');
            Route::post('/drivers/{user}/verify-identity', [AdminVerificationController::class, 'verifyDriverIdentity'])->middleware('throttle:30,1')->name('drivers.verify-identity');
            Route::post('/drivers/{user}/reject-identity', [AdminVerificationController::class, 'rejectDriverIdentity'])->middleware('throttle:30,1')->name('drivers.reject-identity');
            Route::get('/audit-logs', [AdminAuditController::class, 'auditLogs'])->name('audit-logs');
            Route::get('/analytics', [AdminDashboardController::class, 'analytics'])->name('analytics');
            Route::get('/export/users', [AdminUserController::class, 'exportUsers'])->name('export.users');
            Route::get('/export/harvests', [AdminHarvestController::class, 'exportHarvests'])->name('export.harvests');
            Route::post('/crops/{crop}/baseline-price', [AdminHarvestController::class, 'updateBaselinePrice'])->middleware('throttle:30,1')->name('baseline-price');

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
