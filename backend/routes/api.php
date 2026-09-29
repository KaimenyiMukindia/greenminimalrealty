<?php

use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Middleware\EnsureActiveToken;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->name('api.v1.auth.')->group(function (): void {
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:3,1')->name('register');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:3,1')->name('forgot-password');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->middleware('throttle:6,1')->name('reset-password');
    Route::middleware([EnsureActiveToken::class, 'auth:sanctum'])->group(function (): void {
        Route::get('/me', [MeController::class, 'show'])->name('me');
        Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');
        Route::post('/logout-all', [LogoutController::class, 'destroyAll'])->name('logout-all');
    });
});