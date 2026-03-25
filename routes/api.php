<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CreatorController;
use App\Http\Controllers\Api\CreatorLinkController;
use App\Http\Controllers\Api\CreatorProfileController;
use App\Http\Controllers\Api\InstagramInsightsController;
use App\Http\Controllers\Api\InstagramSyncController;
use App\Http\Controllers\Api\PartnerBrandController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

// ── Public ────────────────────────────────────────────────────────────────────
Route::get('/creators/{handle}', [CreatorController::class, 'show'])
    ->where('handle', '@?[a-zA-Z0-9_.]+');

// ── Authenticated ─────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function (): void {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // User
    Route::get('/me', [ProfileController::class, 'show']);
    Route::put('/me', [ProfileController::class, 'update']);

    // Creator profile (contact, theme, niches, badges, social links)
    Route::get('/me/creator-profile', [CreatorProfileController::class, 'show']);
    Route::put('/me/creator-profile', [CreatorProfileController::class, 'update']);

    // Experiences with brands (portfolio)
    Route::get('/me/portfolio', [PortfolioController::class, 'index']);
    Route::post('/me/portfolio', [PortfolioController::class, 'store']);
    Route::post('/me/portfolio/reorder', [PortfolioController::class, 'reorder']);
    Route::put('/me/portfolio/{id}', [PortfolioController::class, 'update']);
    Route::delete('/me/portfolio/{id}', [PortfolioController::class, 'destroy']);

    // Partner brands carousel
    Route::get('/me/partner-brands', [PartnerBrandController::class, 'index']);
    Route::post('/me/partner-brands', [PartnerBrandController::class, 'store']);
    Route::post('/me/partner-brands/reorder', [PartnerBrandController::class, 'reorder']);
    Route::put('/me/partner-brands/{id}', [PartnerBrandController::class, 'update']);
    Route::delete('/me/partner-brands/{id}', [PartnerBrandController::class, 'destroy']);

    // Custom links
    Route::get('/me/links', [CreatorLinkController::class, 'index']);
    Route::post('/me/links', [CreatorLinkController::class, 'store']);
    Route::post('/me/links/reorder', [CreatorLinkController::class, 'reorder']);
    Route::put('/me/links/{id}', [CreatorLinkController::class, 'update']);
    Route::delete('/me/links/{id}', [CreatorLinkController::class, 'destroy']);

    // Instagram
    Route::post('/me/instagram/sync', [InstagramSyncController::class, 'sync']);
    Route::get('/me/instagram/insights', [InstagramInsightsController::class, 'show']);
});
