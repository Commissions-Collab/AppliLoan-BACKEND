<?php

use Illuminate\Support\Facades\Route;

// Health check endpoint for production monitoring
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'environment' => config('app.env'),
    ]);
});

// Route::get('/', function () {
//     return view('welcome');
// });
