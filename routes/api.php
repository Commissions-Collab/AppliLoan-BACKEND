<?php
use App\Http\Controllers\Member\AppliancesController;
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\LoanMonitoringController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin')->prefix('/admin')->group(function () {
        Route::get('dashboard', function () {
            return response()->json(['message' => 'Admin Dashboard']);
        });
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
