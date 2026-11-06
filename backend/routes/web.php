<?php

use App\Http\Controllers\GoogleOAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google', [GoogleOAuthController::class, 'redirect'])->middleware('throttle:6,1')->name('auth.google');
Route::get('/auth/google/callback', [GoogleOAuthController::class, 'callback'])->middleware('throttle:6,1')->name('auth.google.callback');
