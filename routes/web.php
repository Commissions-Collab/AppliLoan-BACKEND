<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Health check endpoint for production monitoring
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'environment' => config('app.env'),
    ]);
});


// Test route to verify the API is working
Route::get('/test', function () {
    return response()->json(['message' => 'Web routes are working']);
});

// Temporary pass-through for frontend calling root paths instead of /api/*
// This forwards the request to the matching /api/* endpoint without redirecting.
Route::any('/login', function (Request $request) {
    return app()->handle(Request::create('/api/login', $request->method(), $request->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent()));
});

Route::any('/register', function (Request $request) {
    return app()->handle(Request::create('/api/register', $request->method(), $request->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent()));
});

Route::any('/verify-email', function (Request $request) {
    return app()->handle(Request::create('/api/verify-email', $request->method(), $request->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent()));
});

Route::any('/verify-otp', function (Request $request) {
    return app()->handle(Request::create('/api/verify-otp', $request->method(), $request->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent()));
});

Route::any('/resend-verification-code', function (Request $request) {
    return app()->handle(Request::create('/api/resend-verification-code', $request->method(), $request->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent()));
});

Route::any('/resend-otp', function (Request $request) {
    return app()->handle(Request::create('/api/resend-otp', $request->method(), $request->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent()));
});

Route::any('/forgot-password', function (Request $request) {
    return app()->handle(Request::create('/api/forgot-password', $request->method(), $request->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent()));
});

Route::any('/reset-password', function (Request $request) {
    return app()->handle(Request::create('/api/reset-password', $request->method(), $request->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent()));
});

Route::any('/loan-application', function (Request $request) {
    return app()->handle(Request::create('/api/loan-application', $request->method(), $request->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent()));
});

Route::any('/user', function (Request $request) {
    return app()->handle(Request::create('/api/user', $request->method(), $request->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent()));
});

Route::any('/admin/{path?}', function (Request $request, $path = '') {
    return app()->handle(Request::create('/api/admin/'.ltrim($path, '/'), $request->method(), $request->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent()));
})->where('path', '.*');

Route::any('/loan_clerk/{path?}', function (Request $request, $path = '') {
    return app()->handle(Request::create('/api/loan_clerk/'.ltrim($path, '/'), $request->method(), $request->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent()));
})->where('path', '.*');

Route::any('/member/{path?}', function (Request $request, $path = '') {
    return app()->handle(Request::create('/api/member/'.ltrim($path, '/'), $request->method(), $request->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent()));
})->where('path', '.*');

// Do not add a manual OPTIONS preflight responder.
// CORS is handled globally by the framework middleware per config/cors.php
