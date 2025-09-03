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
use App\Http\Controllers\Clerk\LoanApplicationsController;
use App\Http\Controllers\Clerk\LoanPaymentsController;
use App\Http\Controllers\Clerk\MemberManagementController;

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

       



        Route::controller(AppliancesLoanController::class)->group(function () {
            Route::get('/appliances-loan/applications', 'index');
            Route::get('/appliances-loan/show/{id}', 'show');
            Route::post('/appliances-loan/approved/{id}', 'approvedApplication');
            Route::patch('/appliances-loan/reject/{id}', 'rejectApplication');
        });

        Route::get('/loan-payments', [LoanPaymentController::class, 'getLoanPayment']);


        Route::apiResource('/clerk-management', ManageClerkController::class);
    });

    Route::middleware('role:loan_clerk')->prefix('/loan-clerk')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Loan Clerk Dashboard']);
        });
        //membership requests store  routes
        Route::post('/requests', [MembershipApprovalController::class, 'store']);

        //membership requests view routes
       // Route::get('/requests/pending', [MembershipApprovalController::class, 'getPendingRequests']);
       // Route::get('/requests/approved', [MembershipApprovalController::class, 'getApprovedRequests']);
       // Route::get('/requests/rejected', [MembershipApprovalController::class, 'getRejectedRequest']);
       // Route::get('/requests/filter', [MembershipApprovalController::class, 'filterAndSortRequests']);
        Route::get('/requests/all', [MembershipApprovalController::class, 'getAllRequests']);
        Route::put('/requests/{id}/status', [MembershipApprovalController::class, 'updateStatus']);
        Route::get('/requests/{id}', [MembershipApprovalController::class, 'showMemberRequests']);

        //member management routes
        Route::get('/members', [MemberManagementController::class, 'displayAllMembers']);
        Route::get('/members/count', [MemberManagementController::class, 'countTotalMembers']);
        Route::get('/members/{loanId}/balance', [MemberManagementController::class, 'showMemberBalance']);
        Route::get('/members/active/count', [MemberManagementController::class, 'countActiveMember']);
        Route::get('/members/inactive/count', [MemberManagementController::class, 'countInactiveMember']);
        Route::get('/members/pending/count', [MemberManagementController::class, 'countPendingMember']);
        Route::get('/members/{id}', [MemberManagementController::class, 'getMemberDetails']);
        Route::put('/members/{Id}', [MemberManagementController::class, 'updateMember']);
        Route::delete('/members/{Id}', [MemberManagementController::class, 'deleteMember']);
        Route::get('/members/search', [MemberManagementController::class, 'searchMember']);

        //loan applications routes
        Route::get('/loan-applications', [LoanApplicationsController::class, 'displayLoanApplication']);
        Route::get('/loan-applications/{id}', [LoanApplicationsController::class, 'showLoanApplication']);
        Route::put('/loan-applications/{id}/status', [LoanApplicationsController::class, 'updateLoanApplication']);
        Route::delete('/loan-applications/{id}', [LoanApplicationsController::class, 'deleteLoanApplication']);
        Route::get('/loan-applications/search', [LoanApplicationsController::class, 'searchLoanApplications']);
        Route::get('/loan-applications/status/count', [LoanApplicationsController::class, 'countLoanApplicationsByStatus']);
        Route::get('/loan-applications/all/count', [LoanApplicationsController::class, 'countTotalLoanApplications']);

        //loan payments routes
        Route::get('/loan-payments', [LoanPaymentsController::class, 'displayPayMents']);
    });

    Route::middleware('role:member')->prefix('/member')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'dashboardData']);
        Route::get('/loan-monitoring', [LoanMonitoringController::class, 'index']);
        Route::get('/loan-monitoring/{loanId}', [LoanMonitoringController::class, 'show']);
        Route::get('/appliances', [AppliancesController::class, 'index']);
        Route::get('/past-application', [AppliancesController::class, 'passApplication']);
    });
});
