<?php

use App\Http\Controllers\Auth\InstagramController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/instagram', [InstagramController::class, 'redirect'])
    ->name('auth.instagram');

Route::get('/auth/instagram/callback', [InstagramController::class, 'callback'])
    ->name('auth.instagram.callback');
