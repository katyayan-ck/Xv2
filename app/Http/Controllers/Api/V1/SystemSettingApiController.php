<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Core\SystemSetting;
use App\Services\SystemSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="System Settings",
 *     description="API endpoints for retrieving application system settings"
 * )
 */
class SystemSettingApiController extends Controller
{
    public function __construct(private SystemSettingService $settingService) {}

    /**
     * Get all system settings grouped by topic
     *
     * @OA\Get(
     *     path="/api/system-settings",
     *     summary="List all system settings grouped by topic",
     *     description="Returns all active system settings organized by topic/category",
     *     tags={"System Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved settings",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "site": {
     *                     "Basic Settings": {
     *                         "site.name": "My Dealership CRM",
     *                         "site.slogan": "Drive Your Success",
     *                         "site.url": "https://crm.example.com"
     *                     },
     *                     "Logo Settings": {
     *                         "site.logo.header": "/images/logo-header.png",
     *                         "site.logo.footer": "/images/logo-footer.png",
     *                         "site.logo.login": "/images/logo-login.png"
     *                     }
     *                 },
     *                 "dealership": {
     *                     "Contact": {
     *                         "dealership.name": "ABC Motors",
     *                         "dealership.phone": "+91-8800000000",
     *                         "dealership.email": "info@abcmotors.com"
     *                     }
     *                 },
     *                 "pricing": {
     *                     "Tax Rates": {
     *                         "pricing.gst_rate": 18,
     *                         "pricing.tds_rate": 1
     *                     }
     *                 }
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        return response()->json($this->settingService->allByTopic());
    }

    /**
     * Get settings for specific topic
     *
     * @OA\Get(
     *     path="/api/system-settings/topic/{topic}",
     *     summary="Get settings for a specific topic",
     *     description="Returns all settings for a given topic/category",
     *     tags={"System Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="topic",
     *         in="path",
     *         required=true,
     *         description="Topic code (e.g., 'site', 'dealership', 'pricing')",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved topic settings",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "Basic Settings": {
     *                     "site.name": "My Dealership CRM",
     *                     "site.slogan": "Drive Your Success"
     *                 },
     *                 "Logo Settings": {
     *                     "site.logo.header": "/images/logo-header.png"
     *                 }
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Topic not found"
     *     )
     * )
     */
    public function topic(string $topic): JsonResponse
    {
        $settings = $this->settingService->topic($topic);

        if (empty($settings)) {
            return response()->json(['message' => "Topic '{$topic}' not found"], 404);
        }

        return response()->json($settings);
    }

    /**
     * Get a single setting by key
     *
     * @OA\Get(
     *     path="/api/system-settings/{key}",
     *     summary="Get a single setting by key",
     *     description="Returns the value of a specific setting key",
     *     tags={"System Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         required=true,
     *         description="Setting key (e.g., 'site.name')",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved setting",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "key": "site.name",
     *                 "value": "My Dealership CRM"
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Setting not found"
     *     )
     * )
     */
    public function show(string $key): JsonResponse
    {
        $value = $this->settingService->get($key);

        if ($value === null) {
            return response()->json(['message' => "Setting '{$key}' not found"], 404);
        }

        return response()->json([
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Update a setting (admin only)
     *
     * @OA\Put(
     *     path="/api/system-settings/{key}",
     *     summary="Update a setting value (admin only)",
     *     description="Updates the value of a specific setting key",
     *     tags={"System Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         required=true,
     *         description="Setting key (e.g., 'site.name')",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Setting value",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"value"},
     *             @OA\Property(
     *                 property="value",
     *                 type="string",
     *                 description="New value for the setting"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Setting updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "message": "Setting updated successfully",
     *                 "key": "site.name",
     *                 "value": "New Site Name"
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Setting not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, string $key): JsonResponse
    {
        // Admin authorization check
        if (!auth()->check() || !auth()->user()->hasAnyRole(['admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'value' => 'required',
        ]);

        try {
            $setting = $this->settingService->set($key, $request->input('value'));

            return response()->json([
                'message' => 'Setting updated successfully',
                'key' => $key,
                'value' => $setting->value,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get site-specific settings
     *
     * @OA\Get(
     *     path="/api/system-settings/category/site",
     *     summary="Get site configuration settings",
     *     description="Returns all site-specific settings (name, logo, footer, theme, etc.)",
     *     tags={"System Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved site settings"
     *     )
     * )
     */
    public function siteSettings(): JsonResponse
    {
        return response()->json($this->settingService->getSiteSettings());
    }

    /**
     * Get dealership-specific settings
     *
     * @OA\Get(
     *     path="/api/system-settings/category/dealership",
     *     summary="Get dealership information settings",
     *     description="Returns dealership name, address, contact details, etc.",
     *     tags={"System Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved dealership settings"
     *     )
     * )
     */
    public function dealershipSettings(): JsonResponse
    {
        return response()->json($this->settingService->getDealershipSettings());
    }

    /**
     * Get pricing-specific settings
     *
     * @OA\Get(
     *     path="/api/system-settings/category/pricing",
     *     summary="Get pricing configuration settings",
     *     description="Returns tax rates, GST, TDS, and other pricing-related settings",
     *     tags={"System Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved pricing settings"
     *     )
     * )
     */
    public function pricingSettings(): JsonResponse
    {
        return response()->json($this->settingService->getPricingSettings());
    }

    /**
     * Export all settings as JSON
     *
     * @OA\Get(
     *     path="/api/system-settings/export/json",
     *     summary="Export all settings as JSON",
     *     description="Returns all settings in JSON format for backup or migration",
     *     tags={"System Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Settings exported successfully"
     *     )
     * )
     */
    public function exportJson(): JsonResponse
    {
        if (!auth()->check() || !auth()->user()->hasAnyRole(['admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(SystemSetting::allForExport());
    }

    /**
     * Import settings from JSON
     *
     * @OA\Post(
     *     path="/api/system-settings/import/json",
     *     summary="Import settings from JSON (admin only)",
     *     description="Imports system settings from a JSON file",
     *     tags={"System Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="JSON file with settings",
     *         @OA\JsonContent(
     *             type="array",
     *             items=@OA\Items(
     *                 type="object",
     *                 required={"key", "value"},
     *                 @OA\Property(property="key", type="string"),
     *                 @OA\Property(property="value", type="string"),
     *                 @OA\Property(property="topic", type="string"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Settings imported successfully"
     *     )
     * )
     */
    public function importJson(Request $request): JsonResponse
    {
        if (!auth()->check() || !auth()->user()->hasAnyRole(['admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'settings' => 'required|array',
        ]);

        $imported = 0;
        $failed = [];

        foreach ($request->input('settings', []) as $setting) {
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
            } catch (\Exception $e) {
                $failed[] = [
                    'key' => $setting['key'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => 'Import completed',
            'imported' => $imported,
            'failed' => $failed,
        ]);
    }
}
