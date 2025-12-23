<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SystemSettingApiController;

Route::prefix('v1')->group(function () {

    // ╔════════════════════════════════════════════════════════╗
    // ║ PUBLIC AUTH ROUTES (No Authentication Required)       ║
    // ╚════════════════════════════════════════════════════════╝

    Route::prefix('auth')->group(function () {
        Route::post('/request-otp', [AuthController::class, 'requestOtp'])
            ->name('api.auth.request-otp');

        Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
            ->name('api.auth.verify-otp');
    });


    // ╔════════════════════════════════════════════════════════╗
    // ║ PROTECTED ROUTES (Authentication + Device Validation) ║
    // ╚════════════════════════════════════════════════════════╝

    Route::middleware(['auth:sanctum', 'validate_device'])->group(function () {

        // Auth routes (protected)
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me'])
                ->name('api.auth.me');

            Route::post('/logout', [AuthController::class, 'logout'])
                ->name('api.auth.logout');
        });

        // System Settings routes (protected)
        Route::prefix('system-settings')->group(function () {

            // Get all settings
            Route::get('/', [SystemSettingApiController::class, 'index'])
                ->name('api.settings.index');

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

            // Get setting by key (MUST be last - catchall pattern)
            Route::get('{key}', [SystemSettingApiController::class, 'show'])
                ->where('key', '.*')
                ->name('api.settings.show');
        });

        // Admin-only routes
        Route::middleware('role:admin|super_admin')->group(function () {
            Route::prefix('system-settings')->group(function () {

                // Export/Import
                Route::get('export/json', [SystemSettingApiController::class, 'exportJson'])
                    ->name('api.settings.export.json');

                Route::post('import/json', [SystemSettingApiController::class, 'importJson'])
                    ->name('api.settings.import.json');

                // Admin update setting
                Route::put('{key}', [SystemSettingApiController::class, 'update'])
                    ->where('key', '.*')
                    ->name('api.settings.update');
            });
        });
    });
});
