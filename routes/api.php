<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SystemSettingApiController;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/request-otp', [AuthController::class, 'requestOtp']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    });

    Route::middleware(['auth:sanctum', 'validate_device'])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);

            //System Settings
            // Get all settings
            Route::get('/', [SystemSettingApiController::class, 'index'])
                ->name('api.settings.index');

            // Get setting by key
            Route::get('{key}', [SystemSettingApiController::class, 'show'])
                ->where('key', '.*')
                ->name('api.settings.show');

            // Get settings by topic
            Route::get('topic/{topic}', [SystemSettingApiController::class, 'topic'])
                ->name('api.settings.topic');

            // Category shortcuts
            Route::get('category/site', [SystemSettingApiController::class, 'siteSettings'])
                ->name('api.settings.site');

            Route::get('category/dealership', [SystemSettingApiController::class, 'dealershipSettings'])
                ->name('api.settings.dealership');

            Route::get('category/pricing', [SystemSettingApiController::class, 'pricingSettings'])
                ->name('api.settings.pricing');

            // Export/Import
            Route::get('export/json', [SystemSettingApiController::class, 'exportJson'])
                ->middleware(['auth', 'role:admin|super_admin'])
                ->name('api.settings.export.json');

            Route::post('import/json', [SystemSettingApiController::class, 'importJson'])
                ->middleware(['auth', 'role:admin|super_admin'])
                ->name('api.settings.import.json');

            // Admin update
            Route::put('{key}', [SystemSettingApiController::class, 'update'])
                ->middleware(['auth', 'role:admin|super_admin'])
                ->where('key', '.*')
                ->name('api.settings.update');
        });
    });
});
