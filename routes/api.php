<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    //Route for authentication
    Route::prefix('auth')->group(function () {
        Route::middleware('guest')->group(function () {
            Route::post('/login', [AuthController::class, 'login'])->name('login');
        });
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });
});
