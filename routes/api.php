<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AppliancesLoanController;
use App\Http\Controllers\Admin\InventoryManagementController;
use App\Http\Controllers\Admin\LoanPaymentController;
use App\Http\Controllers\Admin\ManageClerkController;
use App\Http\Controllers\Clerk\MembershipApprovalController;
use App\Http\Controllers\Member\AppliancesController;
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\LoanMonitoringController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Clerk\InventoryScannerController;
use App\Http\Controllers\Clerk\LoanApplicationsController;
use App\Http\Controllers\Clerk\LoanPaymentsController;
use App\Http\Controllers\Clerk\MemberManagementController;
use App\Http\Controllers\Member\LoanApplicationController;
use App\Http\Controllers\Member\MembershipApplyController;
use App\Http\Controllers\Member\MemberProfileController;

Route::get('/', function () {
    return 'API IS WORKING';
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/resend-verification-code', [AuthController::class, 'resendVerificationCode']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/change-password', [AuthController::class, 'changePassword']);

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin')->prefix('/admin')->group(function () {
        Route::controller(AnalyticsController::class)->group(function () {
            Route::get('/dashboard', 'dashboardData');
            Route::get('/sales-analytics', 'salesAnalytics');
            Route::get('/loan-analytics', 'loanAnalytics');
            Route::get('/members-analytics', 'memberAnalytics');
            Route::get('/dividend-analytics', 'dividendAnalytics');
        });
        Route::post('/category', [InventoryManagementController::class, 'storeCategory']);
        Route::put('/category/{id}', [InventoryManagementController::class, 'updateCategory']);
        Route::delete('/category/{id}', [InventoryManagementController::class, 'deleteCategory']);
        Route::get('/category', [InventoryManagementController::class, 'indexCategory']);

        Route::post('/products', [InventoryManagementController::class, 'storeProduct']);
        Route::PUT('/products/{id}', [InventoryManagementController::class, 'updateProduct']);
        Route::delete('/products/{id}', [InventoryManagementController::class, 'destroyProduct']);
        Route::get('/products', [InventoryManagementController::class, 'indexProduct']);
        Route::get('/products/name/{name}', [InventoryManagementController::class, 'showByName']);
        Route::get('/categories/{id}/products', [InventoryManagementController::class, 'productsByCategory']);
        Route::get('/products/filter', [InventoryManagementController::class, 'filterProducts']);

        Route::post('/requests', [MembershipApprovalController::class, 'store']);
        Route::get('/requests/pending', [MembershipApprovalController::class, 'getPendingRequests']);
        Route::get('/requests/approved', [MembershipApprovalController::class, 'getApprovedRequests']);
        Route::get('/requests/rejected', [MembershipApprovalController::class, 'getRejectedRequests']);
        Route::get('/requests/all', [MembershipApprovalController::class, 'getAllRequests']);
        Route::get('/requests/filter', [MembershipApprovalController::class, 'filterAndSortRequests']);
        Route::get('/requests/{id}', [MembershipApprovalController::class, 'show']);

        // Members listing (approved members)
        Route::get('/members', function () {
            return \App\Models\Member::orderByDesc('created_at')->get();
        });

        Route::put('/requests/{id}/status', [MembershipApprovalController::class, 'updateStatus']);



        Route::controller(AppliancesLoanController::class)->group(function () {
            Route::get('/appliances-loan/applications', 'index');
            Route::get('/appliances-loan/show/{id}', 'show');
            Route::post('/appliances-loan/approved/{id}', 'approvedApplication');
            Route::post('/appliances-loan/reject/{id}', 'rejectApplication');
        });

        Route::get('/loan-payments', [LoanPaymentController::class, 'getLoanPayment']);


        Route::apiResource('/clerk-management', ManageClerkController::class);

        Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index']);
        Route::get('/payments/{id}', [\App\Http\Controllers\Admin\PaymentController::class, 'show']);
        Route::put('/payments/{id}/status', [\App\Http\Controllers\Admin\PaymentController::class, 'updateStatus']);
    });

    Route::middleware('role:loan_clerk')->prefix('/loan_clerk')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Loan Clerk Dashboard']);
        });


        // Membership requests management for loan clerks
        Route::get('/requests/pending', [MembershipApprovalController::class, 'getPendingRequests']);
        Route::get('/requests/approved', [MembershipApprovalController::class, 'getApprovedRequests']);
        Route::get('/requests/rejected', [MembershipApprovalController::class, 'getRejectedRequests']);
        Route::get('/requests/all', [MembershipApprovalController::class, 'getAllRequests']);
        Route::get('/requests/filter', [MembershipApprovalController::class, 'filterAndSortRequests']);
        Route::get('/requests/{id}', [MembershipApprovalController::class, 'show']);
        Route::put('/requests/{id}/status', [MembershipApprovalController::class, 'updateStatus']);

        // Members listing for loan clerks
        Route::get('/members', function () {
            return \App\Models\Member::orderByDesc('created_at')->get();
        });



        Route::controller(InventoryScannerController::class)->prefix('inventory')->group(function () {
            // Categories
            Route::post('/categories', 'storeCategory');
            Route::put('/categories/{id}', 'updateCategory');
            Route::delete('/categories/{id}', 'deleteCategory');
            Route::get('/categories', 'indexCategory');

            // Products
            Route::post('/products', 'storeProduct');
            Route::put('/products/{id}', 'updateProduct');
            Route::delete('/products/{id}', 'destroyProduct');
            Route::get('/products', 'indexProduct');
            Route::get('/products/barcode/{barcode}', 'showByBarcode');  // For scanning
            Route::patch('/products/{id}/stock', 'updateStock');  // Quick stock update via scan
            Route::get('/products/filter', 'filterProducts');
        });

        // Appliances loan applications (clerk view uses same controller formatting)
        Route::get('/appliances-loan/applications', [\App\Http\Controllers\Admin\AppliancesLoanController::class, 'index']);
        Route::get('/appliances-loan/show/{id}', [\App\Http\Controllers\Admin\AppliancesLoanController::class, 'show']);
        Route::post('/appliances-loan/approved/{id}', [\App\Http\Controllers\Admin\AppliancesLoanController::class, 'approvedApplication']);
        Route::post('/appliances-loan/reject/{id}', [\App\Http\Controllers\Admin\AppliancesLoanController::class, 'rejectApplication']);

        // Loan payments (reuse admin controller for consistency)
        Route::get('/appliances-loan/payments', [\App\Http\Controllers\Admin\LoanPaymentController::class, 'getLoanPayment']);

        Route::get('/payments', [\App\Http\Controllers\Clerk\PaymentController::class, 'index']);
        Route::get('/payments/{id}', [\App\Http\Controllers\Clerk\PaymentController::class, 'show']);
        Route::put('/payments/{id}/status', [\App\Http\Controllers\Clerk\PaymentController::class, 'updateStatus']);
    });

    Route::middleware('role:member')->prefix('/member')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'dashboardData']);
        Route::get('/loan-monitoring', [LoanMonitoringController::class, 'index']);
        Route::get('/loan-monitoring/{loanId}', [LoanMonitoringController::class, 'show']);
        Route::get('/appliances', [AppliancesController::class, 'index']);
        Route::get('/past-application', [AppliancesController::class, 'passApplication']);
        Route::get('/profile', [MemberProfileController::class, 'show']);

        Route::post('/membership-apply', [MembershipApplyController::class, 'applyForMembership']);

        Route::post('/payment', [\App\Http\Controllers\Member\PaymentController::class, 'store']);
        Route::get('/payment/{id}', [\App\Http\Controllers\Member\PaymentController::class, 'show']);
    });
});
