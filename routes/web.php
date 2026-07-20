<?php

use App\Http\Controllers\Web\Admin\BackupController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\LocationController;
use App\Http\Controllers\Web\Admin\PostController;
use App\Http\Controllers\Web\Admin\UserController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\EmailVerificationController;
use App\Http\Controllers\Web\PasswordResetController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/email/verify/{id}/{hash}', EmailVerificationController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::get('/reset-password', [PasswordResetController::class, 'show'])
    ->middleware('guest')
    ->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('password.update');
Route::get('/reset-password/done', [PasswordResetController::class, 'result'])
    ->middleware('guest')
    ->name('password.reset.result');

Route::middleware(['auth', EnsureAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::resource('locations', LocationController::class)->except(['show']);
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('posts', [PostController::class, 'index'])->name('posts.index');
        Route::patch('posts/{post}/status', [PostController::class, 'updateStatus'])->name('posts.update-status');
        Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
        Route::get('backups/{filename}', [BackupController::class, 'download'])->name('backups.download');
    });
