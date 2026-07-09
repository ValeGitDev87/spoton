<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminLocationController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/locations/nearby', [LocationController::class, 'nearby']);

    Route::middleware(EnsureAdmin::class)
        ->prefix('admin')
        ->group(function (): void {
            Route::apiResource('locations', AdminLocationController::class);
        });
});
