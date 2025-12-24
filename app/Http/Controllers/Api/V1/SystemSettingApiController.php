<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ErrorCodeEnum;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Core\SystemSetting;
use App\Services\SystemSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SystemSettingApiController
 * 
 * Provides REST API endpoints for managing system settings and configuration.
 * Allows retrieval and management of application-wide settings organized by topics.
 * 
 * Features:
 * - List all system settings organized by topic/category
 * - Retrieve settings by topic (e.g., site, dealership, pricing)
 * - Get individual setting by key
 * - Update settings (admin only)
 * - Export settings as JSON
 * - Import settings from JSON
 * - Comprehensive error handling with standard error codes
 * 
 * @category API Controllers
 * @package App\Http\Controllers\Api\V1
 * @author VDMS Development Team
 * @version 2.0
 */
class SystemSettingApiController extends \App\Http\Controllers\BaseController
{
    protected SystemSettingService $settingService;

    /**
     * Constructor with service injection
     * 
     * @param SystemSettingService $settingService Settings service
     */
    public function __construct(SystemSettingService $settingService)
    {
        $this->settingService = $settingService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all system settings grouped by topic
     * 
     * Retrieves all active system settings organized by topic/category.
     *
     * @OA\Get(
     *     path="/api/v1/system-settings",
     *     operationId="indexSettings",
     *     tags={"System Settings"},
     *     summary="List all system settings grouped by topic",
     *     description="Returns all active system settings organized by topic/category",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successfully retrieved all settings"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     * 
     * @return JsonResponse All settings organized by topic
     */
    public function index(): JsonResponse
    {
        try {
            Log::info('All settings requested', [
                'user_id' => Auth::id(),
                'timestamp' => now(),
            ]);

            $settings = $this->settingService->allByTopic();

            return $this->successResponse(
                $settings,
                'Settings retrieved successfully',
                200
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Retrieve All Settings', [
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Get settings for a specific topic
     * 
     * Retrieves all settings that belong to a specific topic/category.
     *
     * @OA\Get(
     *     path="/api/v1/system-settings/topic/{topic}",
     *     operationId="showTopic",
     *     tags={"System Settings"},
     *     summary="Get settings for a specific topic",
     *     description="Returns all settings for the specified topic",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="topic", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Successfully retrieved topic settings"),
     *     @OA\Response(response=404, description="Topic not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     * 
     * @param string $topic Topic/category name
     * @return JsonResponse Settings for the specified topic
     */
    public function topic(string $topic): JsonResponse
    {
        try {
            Log::info('Topic settings requested', [
                'topic' => $topic,
                'user_id' => Auth::id(),
                'timestamp' => now(),
            ]);

            $settings = $this->settingService->topic($topic);

            if (empty($settings)) {
                throw new ResourceNotFoundException("Topic '{$topic}'");
            }

            return $this->successResponse(
                $settings,
                "Settings for topic '{$topic}' retrieved successfully",
                200
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Retrieve Topic Settings', [
                'topic' => $topic,
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Get a single setting by key
     * 
     * Retrieves the value of a specific setting identified by its key.
     *
     * @OA\Get(
     *     path="/api/v1/system-settings/key/{key}",
     *     operationId="showKey",
     *     tags={"System Settings"},
     *     summary="Get a single setting by key",
     *     description="Returns the value of a specific setting",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="key", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Successfully retrieved setting"),
     *     @OA\Response(response=404, description="Setting not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     * 
     * @param string $key Setting key to retrieve
     * @return JsonResponse Setting key-value pair
     */
    public function show(string $key): JsonResponse
    {
        try {
            Log::info('Single setting requested', [
                'key' => $key,
                'user_id' => Auth::id(),
                'timestamp' => now(),
            ]);

            $value = $this->settingService->get($key);

            if ($value === null) {
                throw new ResourceNotFoundException("Setting", $key);
            }

            return $this->successResponse(
                ['key' => $key, 'value' => $value],
                'Setting retrieved successfully',
                200
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Retrieve Single Setting', [
                'key' => $key,
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Update a setting value (Admin Only)
     * 
     * Updates the value of a specific setting. Requires admin or superadmin role.
     *
     * @OA\Put(
     *     path="/api/v1/system-settings/key/{key}",
     *     operationId="updateSetting",
     *     tags={"System Settings"},
     *     summary="Update a setting value (admin only)",
     *     description="Updates the value of a specific setting",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="key", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"value"},
     *             @OA\Property(property="value", type="string", example="New Value")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Setting updated successfully"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=404, description="Setting not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     * 
     * @param Request $request HTTP request with value field
     * @param string $key Setting key to update
     * @return JsonResponse Updated setting
     */
    public function update(Request $request, string $key): JsonResponse
    {
        try {
            // Check authorization
            if (!Auth::check() || !Auth::user()->hasAnyRole(['admin', 'superadmin'])) {
                Log::warning('Unauthorized setting update attempt', [
                    'key' => $key,
                    'user_id' => Auth::id(),
                    'ip_address' => $request->ip(),
                ]);

                throw new AuthorizationException('update', 'system settings');
            }

            // Validate input
            $validated = $request->validate([
                'value' => 'required|string',
            ]);

            Log::info('Setting update initiated', [
                'key' => $key,
                'user_id' => Auth::id(),
                'timestamp' => now(),
            ]);

            // Update setting
            $setting = $this->settingService->set($key, $validated['value']);

            Log::info('Setting updated successfully', [
                'key' => $key,
                'user_id' => Auth::id(),
                'timestamp' => now(),
            ]);

            // Log audit trail
            $this->logAudit('update', 'SystemSetting', ['value' => $validated['value']], null, 'success');

            return $this->successResponse(
                ['key' => $key, 'value' => $setting->value],
                'Setting updated successfully',
                200
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Update Setting', [
                'key' => $key,
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Get site-specific settings
     * 
     * Convenience endpoint for site configuration settings.
     *
     * @OA\Get(
     *     path="/api/v1/system-settings/category/site",
     *     operationId="getSiteSettings",
     *     tags={"System Settings"},
     *     summary="Get site configuration settings",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successfully retrieved site settings"),
     *     @OA\Response(response=404, description="Settings not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     * 
     * @return JsonResponse Site settings
     */
    public function siteSettings(): JsonResponse
    {
        return $this->topic('site');
    }

    /**
     * Get dealership-specific settings
     * 
     * Convenience endpoint for dealership information settings.
     *
     * @OA\Get(
     *     path="/api/v1/system-settings/category/dealership",
     *     operationId="getDealershipSettings",
     *     tags={"System Settings"},
     *     summary="Get dealership information settings",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successfully retrieved dealership settings"),
     *     @OA\Response(response=404, description="Settings not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     * 
     * @return JsonResponse Dealership settings
     */
    public function dealershipSettings(): JsonResponse
    {
        return $this->topic('dealership');
    }

    /**
     * Get pricing-specific settings
     * 
     * Convenience endpoint for pricing configuration settings.
     *
     * @OA\Get(
     *     path="/api/v1/system-settings/category/pricing",
     *     operationId="getPricingSettings",
     *     tags={"System Settings"},
     *     summary="Get pricing configuration settings",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successfully retrieved pricing settings"),
     *     @OA\Response(response=404, description="Settings not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     * 
     * @return JsonResponse Pricing settings
     */
    public function pricingSettings(): JsonResponse
    {
        return $this->topic('pricing');
    }

    /**
     * Export all settings as JSON
     * 
     * Exports all system settings for backup or migration (admin only).
     *
     * @OA\Get(
     *     path="/api/v1/system-settings/export/json",
     *     operationId="exportJson",
     *     tags={"System Settings"},
     *     summary="Export all settings as JSON",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Settings exported successfully"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     * 
     * @return JsonResponse Exported settings
     */
    public function exportJson(): JsonResponse
    {
        try {
            // Check authorization
            if (!Auth::check() || !Auth::user()->hasAnyRole(['admin', 'superadmin'])) {
                throw new AuthorizationException('export', 'system settings');
            }

            Log::info('Settings export initiated', [
                'user_id' => Auth::id(),
                'timestamp' => now(),
            ]);

            $settings = SystemSetting::all()->toArray();

            // Log audit trail
            $this->logAudit('export', 'SystemSetting', [], null, 'success');

            return $this->successResponse(
                $settings,
                'Settings exported successfully',
                200
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Export Settings', [
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Import settings from JSON
     * 
     * Imports system settings from JSON array (admin only).
     *
     * @OA\Post(
     *     path="/api/v1/system-settings/import/json",
     *     operationId="importJson",
     *     tags={"System Settings"},
     *     summary="Import settings from JSON",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"settings"},
     *             @OA\Property(property="settings", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="key", type="string"),
     *                     @OA\Property(property="value", type="string"),
     *                     @OA\Property(property="topic", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Import completed"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     * 
     * @param Request $request HTTP request with settings array
     * @return JsonResponse Import result with success/failure counts
     */
    public function importJson(Request $request): JsonResponse
    {
        try {
            // Check authorization
            if (!Auth::check() || !Auth::user()->hasAnyRole(['admin', 'superadmin'])) {
                throw new AuthorizationException('import', 'system settings');
            }

            // Validate input
            $validated = $request->validate([
                'settings' => 'required|array',
                'settings.*.key' => 'required|string',
                'settings.*.value' => 'required|string',
                'settings.*.topic' => 'nullable|string',
            ]);

            Log::info('Settings import initiated', [
                'count' => count($validated['settings']),
                'user_id' => Auth::id(),
                'timestamp' => now(),
            ]);

            $imported = 0;
            $failed = 0;
            $errors = [];

            // Import each setting
            foreach ($validated['settings'] as $setting) {
                try {
                    SystemSetting::updateOrCreate(
                        ['key' => $setting['key'] ?? null],
                        [
                            'value' => $setting['value'] ?? null,
                            'topic' => $setting['topic'] ?? null,
                            'type' => $setting['type'] ?? 'string',
                        ]
                    );
                    $imported++;
                } catch (Throwable $e) {
                    $failed++;
                    $errors[] = [
                        'key' => $setting['key'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Failed to import setting', [
                        'key' => $setting['key'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Settings import completed', [
                'imported' => $imported,
                'failed' => $failed,
                'user_id' => Auth::id(),
            ]);

            // Log audit trail
            $this->logAudit(
                'import',
                'SystemSetting',
                ['imported' => $imported, 'failed' => $failed],
                null,
                $failed === 0 ? 'success' : 'partial'
            );

            return $this->successResponse(
                [
                    'imported' => $imported,
                    'failed' => $failed,
                    'errors' => !empty($errors) ? $errors : null,
                ],
                'Import completed',
                200
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Import Settings', [
                'user_id' => Auth::id(),
            ]);
        }
    }
}
