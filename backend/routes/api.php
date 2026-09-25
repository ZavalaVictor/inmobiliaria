<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\ClienteController;
use App\Http\Controllers\Api\V1\PropietarioController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'inmobiliaria-api',
    ]);
});

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:auth-login');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:auth-forgot-password');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:auth-reset-password');

        Route::middleware(['auth:sanctum', 'account.active'])->group(function (): void {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware(['auth:sanctum', 'account.active'])->group(function (): void {
        Route::apiResource('clientes', ClienteController::class);
        Route::apiResource('propietarios', PropietarioController::class);
    });
});
