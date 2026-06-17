<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RapatController;
use App\Http\Controllers\PesertaController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    // Route for authentication
    Route::prefix('auth')->group(function () {
        Route::middleware('guest')->group(function () {
            Route::post('/login', [AuthController::class, 'login'])->name('login');
        });
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // Routes for authenticated users (both admin and teachers)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('my-meetings', [RapatController::class, 'myMeetings']);
        Route::post('rapat/{rapat}/join', [PesertaController::class, 'join']);
        Route::get('rapat/{rapat}/peserta', [PesertaController::class, 'index']);
        
        Route::apiResource('rapat', RapatController::class)->only(['index', 'show']);
    });

    // Admin-only routes
    Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {
        Route::post('rapat/{rapat}/peserta', [PesertaController::class, 'store']);
        Route::delete('rapat/{rapat}/peserta/{user}', [PesertaController::class, 'destroy']);
        
        Route::apiResource('rapat', RapatController::class)->except(['index', 'show']);
    });
});
