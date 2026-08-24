<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AdminLocationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuthPasswordController;
use App\Http\Controllers\Api\ChallengeController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DevPushTestController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PostEngagementController;
use App\Http\Controllers\Api\PresenceController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PushTokenController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\UserNotificationController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureNotSuspended;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/auth/forgot-password', [AuthPasswordController::class, 'forgot'])->middleware('throttle:5,1');
Route::post('/auth/reset-password', [AuthPasswordController::class, 'reset'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', EnsureNotSuspended::class])->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/me', [ProfileController::class, 'update']);
    Route::patch('/me/public-profile', [ProfileController::class, 'updatePublicProfile']);
    Route::delete('/me', [AccountController::class, 'destroy'])->middleware('throttle:5,1');
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/email/verification-notification', [EmailVerificationController::class, 'store'])->middleware('throttle:3,1');
    Route::patch('/auth/password', [AuthPasswordController::class, 'update'])->middleware('throttle:5,1');
    Route::put('/me/push-tokens/{deviceId}', [PushTokenController::class, 'upsert']);
    Route::delete('/me/push-tokens/{deviceId}', [PushTokenController::class, 'destroy']);
    Route::get('/notifications', [UserNotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [UserNotificationController::class, 'unreadCount']);
    Route::patch('/notifications/read-all', [UserNotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{notification}/read', [UserNotificationController::class, 'markRead']);
    Route::post('/dev/push/test', DevPushTestController::class)->middleware('throttle:5,1');
    Route::post('/presence/ping', [PresenceController::class, 'ping']);
    Route::get('/users/me/stats', [ProfileController::class, 'stats']);
    Route::get('/users/me/posts', [ProfileController::class, 'posts']);
    Route::get('/users/me/karma', [ProfileController::class, 'karma']);
    Route::get('/users/search', [ProfileController::class, 'searchUsers']);
    Route::get('/users/{user}/public-profile', [ProfileController::class, 'publicProfile']);
    Route::post('/users/me/photos', [ProfileController::class, 'storePhoto']);
    Route::delete('/users/me/photos/{photoId}', [ProfileController::class, 'destroyPhoto']);

    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{targetName}', [FavoriteController::class, 'destroy']);

    Route::get('/chats', [ChatController::class, 'index']);
    Route::post('/chats/open', [ChatController::class, 'open'])->middleware('throttle:messages');
    Route::get('/chats/{chat}/messages', [ChatController::class, 'messages']);
    Route::post('/chats/{chat}/messages', [ChatController::class, 'send'])->middleware('throttle:messages');
    Route::post('/chats/{chat}/reveal-identity', [ChatController::class, 'revealIdentity'])->middleware('throttle:5,1');
    Route::delete('/chats/{chat}', [ChatController::class, 'destroy']);

    Route::get('/challenges/pending', [ChallengeController::class, 'pending']);
    Route::post('/challenges', [ChallengeController::class, 'store'])->middleware('throttle:challenges');
    Route::post('/challenges/{challenge}/answer', [ChallengeController::class, 'answer'])->middleware('throttle:challenge-answers');
    Route::post('/challenges/{challenge}/counter-propose', [ChallengeController::class, 'counterPropose'])->middleware('throttle:counterproposals');
    Route::post('/challenges/{challenge}/counter-review', [ChallengeController::class, 'counterReview'])->middleware('throttle:counterproposals');

    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/locations/story-feed', [LocationController::class, 'storyFeed']);
    Route::get('/locations/nearby', [LocationController::class, 'nearby']);
    Route::post('/locations/{location}/verify-access', [LocationController::class, 'verifyAccess'])
        ->middleware('throttle:10,1');
    Route::get('/locations/{location}/stories', [StoryController::class, 'index']);
    Route::get('/map', MapController::class);

    Route::get('/posts/feed', [PostController::class, 'feed']);
    Route::get('/posts/nearby', [PostController::class, 'nearby']);
    Route::post('/posts/{post}/like', [PostEngagementController::class, 'toggleLike'])->middleware('throttle:engagements');
    Route::get('/posts/{post}/likes', [PostEngagementController::class, 'likes']);
    Route::post('/posts/{post}/io-cero', [PostEngagementController::class, 'toggleIoCero'])->middleware('throttle:engagements');
    Route::post('/posts/{post}/community-vote', [PostEngagementController::class, 'communityVote'])->middleware('throttle:engagements');
    Route::get('/posts/{post}/io-cero-users', [PostEngagementController::class, 'ioCeroUsers']);
    Route::post('/posts/{post}/verify-answer', [ChallengeController::class, 'verifyClassic'])->middleware('throttle:challenge-answers');
    Route::post('/posts/{post}/counter-propose', [ChallengeController::class, 'counterProposeClassic'])->middleware('throttle:counterproposals');
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->middleware('throttle:comments');
    Route::post('/posts', [PostController::class, 'store'])->middleware('throttle:posts-create');
    Route::apiResource('posts', PostController::class)->except('store');

    Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:reports');

    Route::middleware(EnsureAdmin::class)
        ->prefix('admin')
        ->group(function (): void {
            Route::apiResource('locations', AdminLocationController::class);
        });
});
