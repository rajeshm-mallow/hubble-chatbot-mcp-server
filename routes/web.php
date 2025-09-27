<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JwtController;

Route::get('/', function () {
    return view('welcome');
});

// JWT Token routes for testing
Route::prefix('jwt')->group(function () {
    Route::post('/generate', [JwtController::class, 'generateToken']);
    Route::get('/generate', [JwtController::class, 'generateTokenGet']); // Simple GET endpoint
    Route::get('/verify', [JwtController::class, 'verifyToken']);
});
