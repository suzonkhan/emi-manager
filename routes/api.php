<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Test API endpoint
Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working!',
        'laravel_version' => app()->version(),
        'timestamp' => now()
    ]);
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'OK', 'service' => 'emi-manager']);
});
