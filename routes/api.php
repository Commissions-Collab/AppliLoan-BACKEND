<?php
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

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin')->prefix('/admin')->group(function () {
        Route::controller(AnalyticsController::class)->group(function () {
            Route::get('/dashboard', 'dashboardData');
            Route::get('/sales-analytics', 'salesAnalytics');
            Route::get('/loan-analytics', 'loanAnalytics');
            Route::get('/members-analytics', 'memberAnalytics');
        });
        Route::post('/category', [InventortManagementControlle::class,'storeCategory']);
        Route::put('/category/{id}', [InventortManagementControlle::class,'updateCategory']);
        Route::delete('/category/{id}', [InventortManagementControlle::class,'deleteCategory']);
        Route::get('/category', [InventortManagementControlle::class,'indexCategory']);

        Route::post('/products', [InventortManagementControlle::class,'storeProduct']);
        Route::PUT('/products/{id}', [InventortManagementControlle::class,'updateProduct']);
        Route::delete('/products/{id}', [InventortManagementControlle::class,'destroyProduct']);
        Route::get('/products', [InventortManagementControlle::class,'indexProduct']);
        Route::get('/products/name/{name}', [InventortManagementControlle::class, 'showByName']);
        Route::get('/categories/{id}/products', [InventortManagementControlle::class, 'productsByCategory']);
        Route::get('/products/filter', [InventortManagementControlle::class, 'filterProducts']);

        Route::post('/requests', [MembershipApprovalController::class, 'store']);
        Route::get('/requests/pending', [MembershipApprovalController::class, 'getPendingRequests']);
        Route::get('/requests/approved', [MembershipApprovalController::class, 'getApprovedRequests']);
        Route::get('/requests/rejected', [MembershipApprovalController::class, 'getRejectedRequest']);
        Route::get('/requests/all', [MembershipApprovalController::class, 'getAllRequests']);
        Route::get('/requests/filter', [MembershipApprovalController::class, 'filterAndSortRequests']);

        Route::put('/requests/{id}/status', [MembershipApprovalController::class, 'updateStatus']);


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
