<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminLocationController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ChallengeController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PostEngagementController;
use App\Http\Controllers\Api\PresenceController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/presence/ping', [PresenceController::class, 'ping']);
    Route::get('/users/me/karma', [ProfileController::class, 'karma']);
    Route::post('/users/me/photos', [ProfileController::class, 'storePhoto']);
    Route::delete('/users/me/photos/{photoId}', [ProfileController::class, 'destroyPhoto']);

    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{targetName}', [FavoriteController::class, 'destroy']);

    Route::get('/chats', [ChatController::class, 'index']);
    Route::post('/chats/open', [ChatController::class, 'open']);
    Route::get('/chats/{chat}/messages', [ChatController::class, 'messages']);
    Route::post('/chats/{chat}/messages', [ChatController::class, 'send']);

    Route::get('/challenges/pending', [ChallengeController::class, 'pending']);
    Route::post('/challenges', [ChallengeController::class, 'store']);
    Route::post('/challenges/{challenge}/answer', [ChallengeController::class, 'answer']);
    Route::post('/challenges/{challenge}/counter-propose', [ChallengeController::class, 'counterPropose']);
    Route::post('/challenges/{challenge}/counter-review', [ChallengeController::class, 'counterReview']);

    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/locations/nearby', [LocationController::class, 'nearby']);
    Route::get('/locations/{location}/stories', [StoryController::class, 'index']);
    Route::get('/map', MapController::class);

    Route::get('/posts/nearby', [PostController::class, 'nearby']);
    Route::post('/posts/{post}/like', [PostEngagementController::class, 'toggleLike']);
    Route::post('/posts/{post}/io-cero', [PostEngagementController::class, 'toggleIoCero']);
    Route::get('/posts/{post}/io-cero-users', [PostEngagementController::class, 'ioCeroUsers']);
    Route::post('/posts/{post}/verify-answer', [ChallengeController::class, 'verifyClassic']);
    Route::post('/posts/{post}/counter-propose', [ChallengeController::class, 'counterProposeClassic']);
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::apiResource('posts', PostController::class);

    Route::middleware(EnsureAdmin::class)
        ->prefix('admin')
        ->group(function (): void {
            Route::apiResource('locations', AdminLocationController::class);
        });
});
