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
use App\Http\Controllers\Admin\MemberManagementController;
use App\Http\Controllers\Member\LoanApplicationController;
use App\Http\Controllers\Member\MembershipApplyController;
use App\Http\Controllers\Member\MemberProfileController;
use App\Http\Controllers\Member\PaymentController;
use App\Http\Controllers\Clerk\LoanPaymentsController;

Route::get('/', function () {
    return 'API IS WORKING';
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/verify-otp', [AuthController::class, 'verifyEmail']); // Alias for frontend compatibility
Route::post('/resend-verification-code', [AuthController::class, 'resendVerificationCode']);
Route::post('/resend-otp', [AuthController::class, 'resendVerificationCode']); // Alias for frontend compatibility
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


// Change password for authenticated user
Route::post('/change-password', [AuthController::class, 'userChangePassword'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    // Allow any authenticated user (member or not) to submit a loan application
    Route::post('/loan-application', [LoanApplicationController::class, 'storeLoanApplication']);
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
        Route::post('/products/decrement', [InventoryManagementController::class, 'decrementStock']);

        Route::post('/requests', [MembershipApprovalController::class, 'store']);
        Route::get('/requests/pending', [MembershipApprovalController::class, 'getPendingRequests']);
        Route::get('/requests/approved', [MembershipApprovalController::class, 'getApprovedRequests']);
        Route::get('/requests/rejected', [MembershipApprovalController::class, 'getRejectedRequests']);
        Route::get('/requests/all', [MembershipApprovalController::class, 'getAllRequests']);
        Route::get('/requests/by-user/{userId}', [MembershipApprovalController::class, 'getRequestByUser']);
        Route::get('/requests/filter', [MembershipApprovalController::class, 'filterAndSortRequests']);
        Route::get('/requests/{id}', [MembershipApprovalController::class, 'show']);
        Route::delete('/requests/{id}', [MembershipApprovalController::class, 'destroy']);

        // Members listing (approved members) - include user email & user_id
        Route::get('/members', function () {
            return \App\Models\Member::with('user:id,email')
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($m) {
                    return array_merge($m->toArray(), [
                        'email' => optional($m->user)->email,
                        'user_id' => $m->user_id,
                    ]);
                });
        });

        Route::put('/requests/{id}/status', [MembershipApprovalController::class, 'updateStatus']);



        Route::controller(AppliancesLoanController::class)->group(function () {
            Route::get('/appliances-loan/applications', 'index');
            Route::get('/appliances-loan/show/{id}', 'show');
            Route::post('/appliances-loan/approved/{id}', 'approvedApplication');
            Route::post('/appliances-loan/reject/{id}', 'rejectApplication');
        });

        //  Get all loan payments (active loans with payment info)
        Route::get('/loan-payments', [LoanPaymentController::class, 'getLoanPayment']);

        // Get specific loan payment details
        Route::get('/loan-payments/{loanId}/details', [LoanPaymentController::class, 'getLoanPaymentDetails']);

        // Update payment status (approve, reject, pending)
        Route::put('/loan-payments/{paymentId}/update-status', [LoanPaymentController::class, 'updatePaymentStatus']);


        Route::apiResource('/clerk-management', ManageClerkController::class);

        Route::get('/members-management', [MemberManagementController::class, 'displayAllUsers']);
        Route::get('/members-management/{userId}', [MemberManagementController::class, 'getMemberDetails']);
        // Define static route before dynamic {userId} to avoid conflicts when hitting /bulk-delete
        Route::delete('/members-management/bulk-delete', [MemberManagementController::class, 'bulkDeleteUsers']);
        Route::delete('/members-management/{userId}', [MemberManagementController::class, 'deleteMember']);
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
        Route::get('/requests/by-user/{userId}', [MembershipApprovalController::class, 'getRequestByUser']);
        Route::get('/requests/filter', [MembershipApprovalController::class, 'filterAndSortRequests']);
        Route::get('/requests/{id}', [MembershipApprovalController::class, 'show']);
        Route::put('/requests/{id}/status', [MembershipApprovalController::class, 'updateStatus']);

        // Members listing for loan clerks - include user email & user_id (read-only)
        Route::get('/members', function () {
            return \App\Models\Member::with('user:id,email')
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($m) {
                    return array_merge($m->toArray(), [
                        'email' => optional($m->user)->email,
                        'user_id' => $m->user_id,
                    ]);
                });
        });

        // Member detail for clerks by user_id (read-only)
        Route::get('/members/{userId}', function ($userId) {
            $member = \App\Models\Member::with('user:id,email')
                ->where('user_id', $userId)
                ->first();
            if (!$member) {
                return response()->json(['message' => 'Member not found'], 404);
            }
            $data = $member->toArray();
            $data['email'] = optional($member->user)->email;
            $data['user_id'] = $member->user_id;
            return response()->json($data);
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

        //  Display summarized list of payments with loan info
        Route::get('/payments/display', [LoanPaymentsController::class, 'displayPayMents']);
        // Display all payments (with schedule & receiver)
        Route::get('/payments', [LoanPaymentsController::class, 'index']);

        //  Show a specific payment’s details
        Route::get('/payments/{id}/show', [LoanPaymentsController::class, 'show']);

        // Update payment status (pending, approved, rejected)
        Route::put('/payments/{id}/update-status', [LoanPaymentsController::class, 'updateStatus']);

        // Loan summaries for clerk (active loans with payment info)
        Route::get('/loans/summary', [\App\Http\Controllers\Admin\LoanPaymentController::class, 'getLoanPayment']);
        Route::get('/loans/{loanId}/details', [\App\Http\Controllers\Admin\LoanPaymentController::class, 'getLoanPaymentDetails']);
    });

    Route::middleware('role:member')->prefix('/member')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'dashboardData']);
        Route::get('/loan-monitoring', [LoanMonitoringController::class, 'index']);
        Route::get('/loan-monitoring/{loanId}', [LoanMonitoringController::class, 'show']);
        Route::get('/appliances', [AppliancesController::class, 'index']);
        Route::get('/past-application', [AppliancesController::class, 'passApplication']);
        Route::get('/profile', [MemberProfileController::class, 'show']);

        Route::post('/membership-apply', [MembershipApplyController::class, 'applyForMembership']);
        // Kept for backward-compatibility; members can still post here
        Route::post('/loan-application', [LoanApplicationController::class, 'storeLoanApplication']);


        // Create a down payment
        Route::post('/payments/down-payment', [PaymentController::class, 'downPayment']);

        //Make a payment for a specific loan
        Route::post('/payments/{loanId}/make-payment', [PaymentController::class, 'makePayment']);

        // Show a specific payment
        Route::get('/payments/{id}/show', [PaymentController::class, 'show']);

        //View status of a payment
        Route::get('/payments/{id}/status', [PaymentController::class, 'viewStatus']);

        // List all payments for the authenticated member
        Route::get('/payments/list', [PaymentController::class, 'listPayments']);

        //  View full payment history
        Route::get('/payments/history', [PaymentController::class, 'historyPayments']);

        //  List all payment schedules for a specific loan
        Route::get('/payments/{loanId}/schedules', [PaymentController::class, 'paymentSchedules']);
    });
});
