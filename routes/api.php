<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AppliancesLoanController;
use App\Http\Controllers\Admin\InventoryManagementController;
use App\Http\Controllers\Admin\LoanPaymentController;
use App\Http\Controllers\Admin\MembershipApprovalController;
use App\Http\Controllers\Member\AppliancesController;
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\LoanMonitoringController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::get('/', function () {
    return 'API IS WORKING';
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

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
        Route::get('/requests/rejected', [MembershipApprovalController::class, 'getRejectedRequest']);
        Route::get('/requests/all', [MembershipApprovalController::class, 'getAllRequests']);
        Route::get('/requests/filter', [MembershipApprovalController::class, 'filterAndSortRequests']);

        Route::put('/requests/{id}/status', [MembershipApprovalController::class, 'updateStatus']);



        Route::controller(AppliancesLoanController::class)->group(function () {
            Route::get('/appliances-loan/applications', 'index');
            Route::get('/appliances-loan/show/{id}', 'show');
            Route::post('/appliances-loan/approved/{id}', 'approvedApplication');
            Route::patch('/appliances-loan/reject/{id}', 'rejectApplication');
        });

        Route::get('/loan-payments', [LoanPaymentController::class, 'getLoanPayment']);
    });

    Route::middleware('role:loan_clerk')->prefix('/loan-clerk')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Loan Clerk Dashboard']);
        });
    });

    Route::middleware('role:member')->prefix('/member')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'dashboardData']);
        Route::get('/loan-monitoring', [LoanMonitoringController::class, 'index']);
        Route::get('/loan-monitoring/{loanId}', [LoanMonitoringController::class, 'show']);
        Route::get('/appliances', [AppliancesController::class, 'index']);
        Route::get('/past-application', [AppliancesController::class, 'passApplication']);
    });
});
