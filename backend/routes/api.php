<?php

use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\AccountController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\PublicContactController;
use App\Http\Controllers\Api\V1\PublicContentController;
use App\Http\Controllers\Api\V1\AdminContentController;
use App\Http\Controllers\Api\V1\AdminMenuController;
use App\Http\Controllers\Api\V1\AdminUsersController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Middleware\EnsureActiveToken;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->name('api.v1.auth.')->group(function (): void {
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:3,1')->name('register');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:3,1')->name('forgot-password');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->middleware('throttle:6,1')->name('reset-password');
    Route::middleware([EnsureActiveToken::class, 'auth:sanctum'])->group(function (): void {
        Route::get('/me', [MeController::class, 'show'])->name('me');
        Route::patch('/me', [AccountController::class, 'updateProfile'])->name('me.update');
        Route::put('/me/password', [AccountController::class, 'updatePassword'])->name('me.password');
        Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');
        Route::post('/logout-all', [LogoutController::class, 'destroyAll'])->name('logout-all');
    });
});

Route::prefix('v1/public')->name('api.v1.public.')->group(function (): void {
    Route::get('/settings', [PublicContentController::class, 'settings'])->name('settings');
    Route::get('/site-settings', [PublicContentController::class, 'settings'])->name('site-settings');
    Route::get('/meta', [PublicContentController::class, 'meta'])->name('meta');
    Route::get('/page-meta/{route}', [PublicContentController::class, 'meta'])->where('route', '.*')->name('page-meta');
    Route::get('/stats', [PublicContentController::class, 'stats'])->name('stats');
    Route::get('/values', [PublicContentController::class, 'values'])->name('values');
    Route::get('/services', [PublicContentController::class, 'services'])->name('services');
    Route::get('/properties/suggestions', [PublicContentController::class, 'propertySuggestions'])->name('properties.suggestions');
    Route::get('/properties', [PublicContentController::class, 'properties'])->name('properties');
    Route::get('/sustainability', [PublicContentController::class, 'sustainability'])->name('sustainability');
    Route::get('/sustainability-pillars', [PublicContentController::class, 'sustainability'])->name('sustainability-pillars');
    Route::get('/testimonials', [PublicContentController::class, 'testimonials'])->name('testimonials');
    Route::get('/menus/{location?}', [PublicContentController::class, 'menus'])->name('menus');
    Route::get('/airbnb-listings', [PublicContentController::class, 'airbnbListings'])->name('airbnb-listings');
    Route::post('/contact', [PublicContactController::class, 'store'])->middleware('throttle:6,1')->name('contact');
});

Route::prefix('v1/admin')->name('api.v1.admin.')->middleware(['auth:sanctum', 'can:view-content'])->group(function (): void {
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::get('/menus', [AdminMenuController::class, 'index'])->name('menus.index');
    Route::post('/menus', [AdminMenuController::class, 'store'])->name('menus.store');
    Route::post('/menus/reorder', [AdminMenuController::class, 'reorder'])->name('menus.reorder');
    Route::get('/menus/{id}', [AdminMenuController::class, 'show'])->whereNumber('id')->name('menus.show');
    Route::patch('/menus/{id}', [AdminMenuController::class, 'update'])->whereNumber('id')->name('menus.update');
    Route::delete('/menus/{id}', [AdminMenuController::class, 'destroy'])->whereNumber('id')->name('menus.destroy');
    Route::get('/users', [AdminUsersController::class, 'index'])->name('users.index');
    Route::patch('/users/{id}', [AdminUsersController::class, 'update'])->whereNumber('id')->name('users.update');
    Route::delete('/users/{id}', [AdminUsersController::class, 'destroy'])->whereNumber('id')->name('users.destroy');
    Route::get('/{resource}', [AdminContentController::class, 'index'])->name('index');
    Route::get('/{resource}/{id}', [AdminContentController::class, 'show'])->name('show');
    Route::post('/{resource}', [AdminContentController::class, 'store'])->name('store');
    Route::patch('/{resource}/{id}', [AdminContentController::class, 'update'])->name('update');
    Route::delete('/{resource}/{id}', [AdminContentController::class, 'destroy'])->name('destroy');
    Route::post('/{resource}/reorder', [AdminContentController::class, 'reorder'])->name('reorder');
});