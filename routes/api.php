<?php

use App\Http\Controllers\Admin\CategoriesControlle;
use App\Http\Controllers\Admin\ProductControlle;
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
        Route::post('/category', [CategoriesControlle::class,'store']);
        Route::put('/category/{id}', [CategoriesControlle::class,'update']);
        Route::delete('/category/{id}', [CategoriesControlle::class,'delete']);
        Route::get('/category', [CategoriesControlle::class,'index']);

        Route::post('/products', [ProductControlle::class,'store']);
        Route::PUT('/products/{id}', [ProductControlle::class,'update']);
        Route::delete('/products/{id}', [ProductControlle::class,'destroy']);
        Route::get('/products', [ProductControlle::class,'index']);
        Route::get('/products/name/{name}', [ProductControlle::class, 'showByName']);
        Route::get('/categories/{id}/products', [ProductControlle::class, 'productsByCategory']);
        Route::get('/products/filter', [ProductControlle::class, 'filterProducts']);
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
