<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminLocationController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/locations/nearby', [LocationController::class, 'nearby']);
    Route::get('/locations/{location}/stories', [StoryController::class, 'index']);
    Route::get('/map', MapController::class);

    Route::get('/posts/nearby', [PostController::class, 'nearby']);
    Route::apiResource('posts', PostController::class);

    Route::middleware(EnsureAdmin::class)
        ->prefix('admin')
        ->group(function (): void {
            Route::apiResource('locations', AdminLocationController::class);
        });
});
