<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RapatController;
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
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('rapat', RapatController::class)->only(['index', 'show']);
    });
    Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {
        Route::apiResource('rapat', RapatController::class)->except(['index', 'show']);
    });
});
