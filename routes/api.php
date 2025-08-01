<?php

use App\Http\Controllers\api\AuthControlle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthControlle::class,'register']);
Route::post('/login', [AuthControlle::class, 'login']);
