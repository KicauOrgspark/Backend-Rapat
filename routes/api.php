<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\NotulenController;
use App\Http\Controllers\PesertaController;
use App\Http\Controllers\RapatController;
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
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('my-meetings', [RapatController::class, 'myMeetings']);
        Route::post('rapat/{rapat}/join', [PesertaController::class, 'join']);
        Route::get('rapat/{rapat}/peserta', [PesertaController::class, 'index']);
        Route::get('rapat/{rapat}/notulen', [NotulenController::class, 'GetNotulenByRapatID']);
        
        Route::apiResource('rapat', RapatController::class)->only(['index', 'show']);
    });
        
    // Admin-only routes
    Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {
        Route::get('rapat/{rapat}/laporan', [LaporanController::class, 'ambilLaporan']);
        Route::post('rapat/{rapat}/peserta', [PesertaController::class, 'store']);
        Route::delete('rapat/{rapat}/peserta/{user}', [PesertaController::class, 'destroy']);
        Route::post('rapat/{rapat}/notulen', [NotulenController::class, 'createNotulen']);
        Route::get('users', [AuthController::class, 'users']);
        Route::patch('rapat/{rapat}/peserta/status', [PesertaController::class, 'updateStatusKehadiran']);
        Route::post('add/users', [AuthController::class, 'register']);
        
        Route::apiResource('rapat', RapatController::class)->except(['index', 'show']);
    });
});
