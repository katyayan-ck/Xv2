<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ErrorCodeEnum;
use App\Exceptions\ApplicationException;
use App\Exceptions\AuthorizationException;
use App\Http\Controllers\BaseController;
use App\Http\Resources\AlertResource;
use App\Http\Resources\MessageResource;
use App\Http\Resources\NotificationResource;
use App\Models\Core\Alert;
use App\Models\Core\Message;
use App\Models\Core\Notification;
use App\Models\User;
use App\Models\Core\UserDeviceToken;
use App\Services\FirebaseService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="Notification and messaging endpoints for managing notifications, alerts, devices, and messages"
 * )
 */
class NotificationController extends BaseController
{
    /**
     * Constructor with service injection
     *
     * @param NotificationService $notificationService
     * @param FirebaseService $firebaseService
     */
    public function __construct(
        private NotificationService $notificationService,
        private FirebaseService $firebaseService
    ) {
        $this->middleware('auth:sanctum')->except([]);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // DEVICE MANAGEMENT ENDPOINTS
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Register device FCM token
     *
     * Registers a new device with FCM token for push notifications.
     * Each device must have a unique device_id. Multiple devices per user are supported.
     *
     * Device Registration:
     * - device_id: Unique identifier (UUID or similar)
     * - platform: iOS, Android, or Web
     * - fcm_token: Firebase Cloud Messaging token
     * - metadata: Optional device-specific information
     *
     * @OA\Post(
     *     path="/devices/register",
     *     operationId="registerDevice",
     *     tags={"Notifications"},
     *     summary="Register device FCM token",
     *     description="Register a new device with FCM token for push notifications",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"device_id","device_name","platform","fcm_token"},
     *             @OA\Property(property="device_id", type="string", example="550e8400-e29b-41d4-a716-446655440000", description="UUID format"),
     *             @OA\Property(property="device_name", type="string", example="iPhone 14 Pro", description="Human-readable device name"),
     *             @OA\Property(property="platform", type="string", enum={"iOS","Android","Web"}, example="iOS"),
     *             @OA\Property(property="platform_version", type="string", nullable=true, example="16.2"),
     *             @OA\Property(property="fcm_token", type="string", example="eJzlPc9u4sYWf4VV91JV7YTwY8gUd5AosZLSXvRNvViZ+JdxzHjyLJNhZBgMhhPSRRZZdJEHUhYiUcosUIZMRE", description="Firebase Cloud Messaging token"),
     *             @OA\Property(property="metadata", type="object", nullable=true, example={"os_version":"16.2","app_version":"1.0.0"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Device registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=201),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S201"),
     *             @OA\Property(property="message", type="string", example="Device registered successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="device_id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="device_name", type="string", example="iPhone 14 Pro"),
     *                 @OA\Property(property="platform", type="string", example="iOS"),
     *                 @OA\Property(property="fcm_token", type="string"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="last_used_at", type="string", format="date-time", example="2025-12-24T18:30:00Z")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time", example="2025-12-24T18:30:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerDevice(Request $request): JsonResponse
    {
        try {
            // Validate request input
            $validated = $request->validate([
                'device_id' => 'required|string|max:255',
                'device_name' => 'required|string|max:255',
                'platform' => 'required|string|in:iOS,Android,Web',
                'platform_version' => 'nullable|string|max:50',
                'fcm_token' => 'required|string|min:10',
                'metadata' => 'nullable|array',
            ], [
                'device_id.required' => 'Device ID is required.',
                'device_id.max' => 'Device ID must not exceed 255 characters.',
                'platform.in' => 'Platform must be iOS, Android, or Web.',
                'fcm_token.min' => 'FCM token is invalid.',
            ]);

            Log::info('Device registration initiated', [
                'device_id' => $validated['device_id'],
                'platform' => $validated['platform'],
                'user_id' => auth('sanctum')->id(),
                'ip_address' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Register device via service
            $device = $this->firebaseService->registerDeviceToken(
                user: auth('sanctum')->user(),
                deviceId: $validated['device_id'],
                deviceName: $validated['device_name'],
                platform: $validated['platform'],
                fcmToken: $validated['fcm_token'],
                metadata: $validated['metadata'] ?? []
            );

            Log::info('Device registered successfully', [
                'device_id' => $validated['device_id'],
                'user_id' => auth('sanctum')->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Log audit trail
            $this->logAudit(
                'register',
                'Device',
                ['device_id' => $validated['device_id']],
                $device->id,
                'success'
            );

            return $this->successResponse(
                $device,
                'Device registered successfully',
                201
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Device Registration', [
                'device_id' => $request->input('device_id'),
                'platform' => $request->input('platform'),
                'ip_address' => $request->ip(),
            ]);
        }
    }

    /**
     * Get all user devices
     *
     * Retrieves paginated list of all registered devices for the authenticated user.
     * Devices are sorted by last used date (most recent first).
     *
     * @OA\Get(
     *     path="/devices",
     *     operationId="getDevices",
     *     tags={"Notifications"},
     *     summary="Get all registered devices",
     *     description="Retrieve paginated list of all user devices with FCM tokens",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Devices retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="Devices retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="device_id", type="string"),
     *                     @OA\Property(property="device_name", type="string"),
     *                     @OA\Property(property="platform", type="string"),
     *                     @OA\Property(property="is_active", type="boolean"),
     *                     @OA\Property(property="last_used_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=10),
     *                     @OA\Property(property="total", type="integer", example=5),
     *                     @OA\Property(property="last_page", type="integer", example=1)
     *                 )
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDevices(Request $request): JsonResponse
    {
        try {
            Log::info('Devices retrieval initiated', [
                'user_id' => auth('sanctum')->id(),
                'ip_address' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Get paginated devices
            $devices = auth('sanctum')->user()
                ->deviceTokens()
                ->orderByDesc('last_used_at')
                ->paginate(10);

            Log::info('Devices retrieved successfully', [
                'user_id' => auth('sanctum')->id(),
                'device_count' => $devices->count(),
                'timestamp' => now()->toIso8601String(),
            ]);

            return $this->paginatedResponse(
                $devices->items(),
                $devices,
                'Devices retrieved successfully',
                200
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Devices Retrieval', [
                'user_id' => auth('sanctum')->id(),
                'ip_address' => $request->ip(),
            ]);
        }
    }

    /**
     * Revoke single device
     *
     * Revokes a specific device and its FCM token. User will no longer receive
     * push notifications on this device.
     *
     * Authorization: User can only revoke their own devices.
     *
     * @OA\Delete(
     *     path="/devices/{id}",
     *     operationId="revokeDevice",
     *     tags={"Notifications"},
     *     summary="Revoke single device",
     *     description="Revoke a specific device FCM token. Device will no longer receive notifications",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Device ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Device revoked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="Device revoked successfully"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Device belongs to another user"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Device not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @param int $id Device ID
     * @return JsonResponse
     */
    public function revokeDevice(int $id): JsonResponse
    {
        try {
            // Find device
            $device = UserDeviceToken::findOrFail($id);

            // Check authorization - user can only revoke their own devices
            if ($device->user_id !== auth('sanctum')->id()) {
                Log::warning('Unauthorized device revocation attempt', [
                    'device_id' => $id,
                    'user_id' => auth('sanctum')->id(),
                    'owner_id' => $device->user_id,
                    'timestamp' => now()->toIso8601String(),
                ]);

                return $this->forbiddenResponse(
                    'revoke',
                    'Device'
                );
            }

            Log::info('Device revocation initiated', [
                'device_id' => $id,
                'user_id' => auth('sanctum')->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Revoke device via service
            $this->firebaseService->revokeDevice($device);

            Log::info('Device revoked successfully', [
                'device_id' => $id,
                'user_id' => auth('sanctum')->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Log audit trail
            $this->logAudit(
                'revoke',
                'Device',
                ['device_id' => $device->device_id],
                $device->id,
                'success'
            );

            return $this->successResponse(
                null,
                'Device revoked successfully',
                200
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Device', $id);
        } catch (Throwable $e) {
            return $this->handleException($e, 'Device Revocation', [
                'device_id' => $id,
                'user_id' => auth('sanctum')->id(),
            ]);
        }
    }

    /**
     * Revoke all devices
     *
     * Revokes all devices for the authenticated user in a single operation.
     * User will be logged out from all devices.
     *
     * @OA\Post(
     *     path="/devices/revoke-all",
     *     operationId="revokeAllDevices",
     *     tags={"Notifications"},
     *     summary="Logout from all devices",
     *     description="Revoke all user devices FCM tokens at once. User will be logged out everywhere",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="All devices revoked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="All devices revoked successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="revoked_count", type="integer", example=5)
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @return JsonResponse
     */
    public function revokeAllDevices(): JsonResponse
    {
        try {
            Log::info('Revoke all devices initiated', [
                'user_id' => auth('sanctum')->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Revoke all devices via service
            $count = $this->firebaseService->revokeAllUserDevices(auth('sanctum')->user());

            Log::info('All devices revoked successfully', [
                'user_id' => auth('sanctum')->id(),
                'revoked_count' => $count,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Log audit trail
            $this->logAudit(
                'revoke-all',
                'Device',
                ['count' => $count],
                null,
                'success'
            );

            return $this->successResponse(
                ['revoked_count' => $count],
                'All devices revoked successfully',
                200
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Revoke All Devices', [
                'user_id' => auth('sanctum')->id(),
            ]);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // NOTIFICATION ENDPOINTS
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Get all notifications
     *
     * Retrieves paginated list of notifications for the authenticated user.
     * Supports filtering by read status, type, and priority.
     *
     * Query Parameters:
     * - page: Page number (default: 1)
     * - per_page: Items per page (default: 20)
     * - is_read: Filter by read status (true/false)
     * - type: Filter by notification type
     * - priority: Filter by priority level
     *
     * @OA\Get(
     *     path="/notifications",
     *     operationId="getNotifications",
     *     tags={"Notifications"},
     *     summary="Get all notifications",
     *     description="Retrieve paginated list of user notifications with optional filtering",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="is_read",
     *         in="query",
     *         description="Filter by read status (true/false)",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by notification type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="priority",
     *         in="query",
     *         description="Filter by priority (low, medium, high)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"low","medium","high"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notifications retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="Notifications retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/NotificationResource")
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="pagination", type="object")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getNotifications(Request $request): JsonResponse
    {
        try {
            Log::info('Notifications retrieval initiated', [
                'user_id' => auth('sanctum')->id(),
                'filters' => $request->query(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $query = auth('sanctum')->user()
                ->notifications()
                ->orderByDesc('created_at');

            // Filter by read status
            if ($request->has('is_read')) {
                $isRead = filter_var($request->get('is_read'), FILTER_VALIDATE_BOOLEAN);
                $query->where('is_read', $isRead);
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->get('type'));
            }

            // Filter by priority
            if ($request->has('priority')) {
                $query->where('priority', $request->get('priority'));
            }

            // Get paginated results
            $notifications = $query->paginate(20);

            Log::info('Notifications retrieved successfully', [
                'user_id' => auth('sanctum')->id(),
                'count' => $notifications->count(),
                'timestamp' => now()->toIso8601String(),
            ]);

            return $this->paginatedResponse(
                NotificationResource::collection($notifications)->resolve(),
                $notifications,
                'Notifications retrieved successfully',
                200
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Notifications Retrieval', [
                'user_id' => auth('sanctum')->id(),
                'filters' => $request->query(),
            ]);
        }
    }

    /**
     * Get unread notifications
     *
     * Retrieves paginated list of unread notifications for the authenticated user.
     * Supports pagination and sorting.
     *
     * Pagination Parameters:
     * - page: Current page number (default: 1)
     * - per_page: Items per page (default: 20, max: 100)
     * - sort_by: Field to sort by (default: created_at)
     * - sort_order: asc or desc (default: desc)
     *
     * @OA\Get(
     *     path="/notifications/unread",
     *     operationId="getUnreadNotifications",
     *     tags={"Notifications"},
     *     summary="Get unread notifications",
     *     description="Retrieve paginated unread notifications",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=20, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort field",
     *         @OA\Schema(type="string", default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort order",
     *         @OA\Schema(type="string", enum={"asc","desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unread notifications retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="Unread notifications retrieved"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(
     *                     property="notifications",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/NotificationResource")
     *                 ),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="total", type="integer"),
     *                     @OA\Property(property="last_page", type="integer")
     *                 )
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUnreadNotifications(): JsonResponse
    {
        try {
            Log::info('Unread notifications retrieval initiated', [
                'user_id' => auth('sanctum')->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Get unread notifications
            $notifications = auth('sanctum')->user()
                ->notifications()
                ->where('is_read', false)
                ->orderByDesc('created_at')
                ->paginate(20);

            // Get total unread count
            $unreadCount = $this->notificationService->getUnreadCount(auth('sanctum')->user());

            Log::info('Unread notifications retrieved successfully', [
                'user_id' => auth('sanctum')->id(),
                'unread_count' => $unreadCount,
                'timestamp' => now()->toIso8601String(),
            ]);

            return $this->paginatedResponse(
                [
                    'notifications' => NotificationResource::collection($notifications)->resolve(),
                    'unread_count' => $unreadCount,
                ],
                $notifications,
                'Unread notifications retrieved',
                200
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Unread Notifications Retrieval', [
                'user_id' => auth('sanctum')->id(),
            ]);
        }
    }

    /**
     * Mark notification as read
     *
     * Marks a single notification as read.
     *
     * Authorization: User can only mark their own notifications as read.
     *
     * @OA\Post(
     *     path="/notifications/{id}/read",
     *     operationId="markNotificationAsRead",
     *     tags={"Notifications"},
     *     summary="Mark notification as read",
     *     description="Mark a specific notification as read",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notification ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="Notification marked as read"),
     *             @OA\Property(property="data", ref="#/components/schemas/NotificationResource"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @param int $id Notification ID
     * @return JsonResponse
     */
    public function markNotificationAsRead(int $id): JsonResponse
    {
        try {
            // Find notification
            $notification = Notification::findOrFail($id);

            // Check authorization
            if ($notification->user_id !== auth('sanctum')->id()) {
                Log::warning('Unauthorized notification read attempt', [
                    'notification_id' => $id,
                    'user_id' => auth('sanctum')->id(),
                    'owner_id' => $notification->user_id,
                    'timestamp' => now()->toIso8601String(),
                ]);

                return $this->forbiddenResponse('read', 'Notification');
            }

            Log::info('Marking notification as read', [
                'notification_id' => $id,
                'user_id' => auth('sanctum')->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Mark as read
            $notification->markAsRead();

            // Log audit trail
            $this->logAudit(
                'read',
                'Notification',
                ['notification_id' => $id],
                $notification->id,
                'success'
            );

            return $this->successResponse(
                new NotificationResource($notification),
                'Notification marked as read',
                200
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Notification', $id);
        } catch (Throwable $e) {
            return $this->handleException($e, 'Mark Notification As Read', [
                'notification_id' => $id,
                'user_id' => auth('sanctum')->id(),
            ]);
        }
    }

    /**
     * Mark all notifications as read
     *
     * Marks all unread notifications as read for the authenticated user.
     *
     * @OA\Post(
     *     path="/notifications/mark-all-read",
     *     operationId="markAllNotificationsAsRead",
     *     tags={"Notifications"},
     *     summary="Mark all notifications as read",
     *     description="Mark all unread notifications as read in one operation",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="All notifications marked as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="All notifications marked as read"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="marked_count", type="integer", example=5)
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @return JsonResponse
     */
    public function markAllNotificationsAsRead(): JsonResponse
    {
        try {
            Log::info('Mark all notifications as read initiated', [
                'user_id' => auth('sanctum')->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Mark all as read via service
            $count = $this->notificationService->markAllAsRead(auth('sanctum')->user());

            Log::info('All notifications marked as read', [
                'user_id' => auth('sanctum')->id(),
                'marked_count' => $count,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Log audit trail
            $this->logAudit(
                'mark-all-read',
                'Notification',
                ['count' => $count],
                null,
                'success'
            );

            return $this->successResponse(
                ['marked_count' => $count],
                'All notifications marked as read',
                200
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Mark All Notifications As Read', [
                'user_id' => auth('sanctum')->id(),
            ]);
        }
    }

    /**
     * Delete notification
     *
     * Permanently deletes a notification.
     *
     * Authorization: User can only delete their own notifications.
     *
     * @OA\Delete(
     *     path="/notifications/{id}",
     *     operationId="deleteNotification",
     *     tags={"Notifications"},
     *     summary="Delete notification",
     *     description="Permanently delete a specific notification",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notification ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="Notification deleted successfully"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @param int $id Notification ID
     * @return JsonResponse
     */
    public function deleteNotification(int $id): JsonResponse
    {
        try {
            // Find notification
            $notification = Notification::findOrFail($id);

            // Check authorization
            if ($notification->user_id !== auth('sanctum')->id()) {
                Log::warning('Unauthorized notification deletion attempt', [
                    'notification_id' => $id,
                    'user_id' => auth('sanctum')->id(),
                    'owner_id' => $notification->user_id,
                    'timestamp' => now()->toIso8601String(),
                ]);

                return $this->forbiddenResponse('delete', 'Notification');
            }

            Log::info('Notification deletion initiated', [
                'notification_id' => $id,
                'user_id' => auth('sanctum')->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Delete notification
            $notification->delete();

            // Log audit trail
            $this->logAudit(
                'delete',
                'Notification',
                ['notification_id' => $id],
                $id,
                'success'
            );

            Log::info('Notification deleted successfully', [
                'notification_id' => $id,
                'user_id' => auth('sanctum')->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            return $this->successResponse(
                null,
                'Notification deleted successfully',
                200
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Notification', $id);
        } catch (Throwable $e) {
            return $this->handleException($e, 'Notification Deletion', [
                'notification_id' => $id,
                'user_id' => auth('sanctum')->id(),
            ]);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // ALERTS ENDPOINTS
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Get all alerts
     *
     * Retrieves paginated list of alerts for the authenticated user.
     * Supports filtering by severity and read status.
     *
     * Query Parameters:
     * - severity: Filter by alert severity (critical, high, medium, low)
     * - is_read: Filter by read status (true/false)
     *
     * @OA\Get(
     *     path="/alerts",
     *     operationId="getAlerts",
     *     tags={"Notifications"},
     *     summary="Get all alerts",
     *     description="Retrieve paginated list of user alerts with optional filtering",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="severity",
     *         in="query",
     *         description="Filter by severity",
     *         required=false,
     *         @OA\Schema(type="string", enum={"critical","high","medium","low"})
     *     ),
     *     @OA\Parameter(
     *         name="is_read",
     *         in="query",
     *         description="Filter by read status",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Alerts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="Alerts retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/AlertResource")
     *             ),
     *             @OA\Property(property="meta", type="object"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAlerts(Request $request): JsonResponse
    {
        try {
            Log::info('Alerts retrieval initiated', [
                'user_id' => auth('sanctum')->id(),
                'filters' => $request->query(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $query = auth('sanctum')->user()
                ->alerts()
                ->orderByDesc('created_at');

            // Filter by severity
            if ($request->has('severity')) {
                $query->where('severity', $request->get('severity'));
            }

            // Filter by read status
            if ($request->has('is_read')) {
                $isRead = filter_var($request->get('is_read'), FILTER_VALIDATE_BOOLEAN);
                $query->where('is_read', $isRead);
            }

            // Get paginated results
            $alerts = $query->paginate(20);

            Log::info('Alerts retrieved successfully', [
                'user_id' => auth('sanctum')->id(),
                'count' => $alerts->count(),
                'timestamp' => now()->toIso8601String(),
            ]);

            return $this->paginatedResponse(
                AlertResource::collection($alerts)->resolve(),
                $alerts,
                'Alerts retrieved successfully',
                200
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Alerts Retrieval', [
                'user_id' => auth('sanctum')->id(),
                'filters' => $request->query(),
            ]);
        }
    }

    /**
     * Mark alert as read
     *
     * Marks a single alert as read.
     *
     * Authorization: User can only mark their own alerts as read.
     *
     * @OA\Post(
     *     path="/alerts/{id}/read",
     *     operationId="markAlertAsRead",
     *     tags={"Notifications"},
     *     summary="Mark alert as read",
     *     description="Mark a specific alert as read",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Alert ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Alert marked as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="Alert marked as read"),
     *             @OA\Property(property="data", ref="#/components/schemas/AlertResource"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Alert not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @param int $id Alert ID
     * @return JsonResponse
     */
    public function markAlertAsRead(int $id): JsonResponse
    {
        try {
            // Find alert
            $alert = Alert::findOrFail($id);

            // Check authorization
            if ($alert->user_id !== auth('sanctum')->id()) {
                Log::warning('Unauthorized alert read attempt', [
                    'alert_id' => $id,
                    'user_id' => auth('sanctum')->id(),
                    'owner_id' => $alert->user_id,
                    'timestamp' => now()->toIso8601String(),
                ]);

                return $this->forbiddenResponse('read', 'Alert');
            }

            Log::info('Marking alert as read', [
                'alert_id' => $id,
                'user_id' => auth('sanctum')->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Mark as read
            $alert->markAsRead();

            // Log audit trail
            $this->logAudit(
                'read',
                'Alert',
                ['alert_id' => $id],
                $alert->id,
                'success'
            );

            return $this->successResponse(
                new AlertResource($alert),
                'Alert marked as read',
                200
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Alert', $id);
        } catch (Throwable $e) {
            return $this->handleException($e, 'Mark Alert As Read', [
                'alert_id' => $id,
                'user_id' => auth('sanctum')->id(),
            ]);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // MESSAGES ENDPOINTS
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Get conversation with user
     *
     * Retrieves paginated message history between the authenticated user and another user.
     * Messages are sorted by creation date (newest first).
     *
     * @OA\Get(
     *     path="/messages/user/{user_id}",
     *     operationId="getConversation",
     *     tags={"Notifications"},
     *     summary="Get conversation with user",
     *     description="Retrieve paginated message history with another user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="User ID to get conversation with",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Conversation retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="Conversation retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="messages", type="array",
     *                     @OA\Items(ref="#/components/schemas/MessageResource")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @param int $userId User ID
     * @return JsonResponse
     */
    public function getConversation(int $userId): JsonResponse
    {
        try {
            Log::info('Conversation retrieval initiated', [
                'user_id' => auth('sanctum')->id(),
                'other_user_id' => $userId,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Get other user
            $otherUser = User::findOrFail($userId);

            // Get messages between users
            $messages = Message::conversation(auth('sanctum')->id(), $userId)
                ->orderByDesc('created_at')
                ->paginate(50);

            Log::info('Conversation retrieved successfully', [
                'user_id' => auth('sanctum')->id(),
                'other_user_id' => $userId,
                'message_count' => $messages->count(),
                'timestamp' => now()->toIso8601String(),
            ]);

            return $this->paginatedResponse(
                [
                    'user' => $otherUser,
                    'messages' => MessageResource::collection($messages)->resolve(),
                ],
                $messages,
                'Conversation retrieved successfully',
                200
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('User', $userId);
        } catch (Throwable $e) {
            return $this->handleException($e, 'Conversation Retrieval', [
                'user_id' => auth('sanctum')->id(),
                'other_user_id' => $userId,
            ]);
        }
    }

    /**
     * Send message to user
     *
     * Sends a new message to another user. Supports text and attachments.
     *
     * Request body properties:
     * - message_text: Message content (required, max 5000 chars)
     * - message_type: Type of message (text, image, file, voice)
     * - attachments: Array of attachment data
     * - reply_to_id: Optional ID of message being replied to
     *
     * @OA\Post(
     *     path="/messages/user/{user_id}",
     *     operationId="sendMessage",
     *     tags={"Notifications"},
     *     summary="Send message to user",
     *     description="Send a new message to another user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="Recipient user ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *    @OA\RequestBody(
     * required=true,
     *@OA\JsonContent(
     *    required={"message_text"},
     *   @OA\Property(property="message_text", type="string", maxLength=2000, example="Hello, how are you?"),
     *    @OA\Property(property="message_type", type="string", enum={"text","image","document"}, example="text"),
     *    @OA\Property(property="attachments", type="array", nullable=true, @OA\Items(type="string"), description="Array of attachment URLs")
     * )
     *),
     *     @OA\Response(
     *         response=201,
     *         description="Message sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=201),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S201"),
     *             @OA\Property(property="message", type="string", example="Message sent successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/MessageResource"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot send message to yourself"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @param Request $request
     * @param int $userId Recipient user ID
     * @return JsonResponse
     */
    public function sendMessage(Request $request, int $userId): JsonResponse
    {
        try {
            // Validate request input
            $validated = $request->validate([
                'message_text' => 'required|string|max:5000',
                'message_type' => 'nullable|string|in:text,image,file,voice',
                'attachments' => 'nullable|array',
                'reply_to_id' => 'nullable|integer|exists:messages,id',
            ], [
                'message_text.required' => 'Message text is required.',
                'message_text.max' => 'Message must not exceed 5000 characters.',
                'message_type.in' => 'Invalid message type.',
                'reply_to_id.exists' => 'Reply message not found.',
            ]);

            Log::info('Message sending initiated', [
                'sender_id' => auth('sanctum')->id(),
                'receiver_id' => $userId,
                'message_type' => $validated['message_type'] ?? 'text',
                'ip_address' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Get receiver user
            $receiver = User::findOrFail($userId);

            // Prevent self-messaging
            if ($receiver->id === auth('sanctum')->id()) {
                Log::warning('Self-messaging attempt', [
                    'user_id' => auth('sanctum')->id(),
                    'timestamp' => now()->toIso8601String(),
                ]);

                return $this->errorResponse(
                    ErrorCodeEnum::VALIDATION_FAILED,
                    'Cannot send message to yourself.',
                    null,
                    400
                );
            }

            // Send message via service
            $message = $this->notificationService->sendMessage(
                sender: auth('sanctum')->user(),
                receiver: $receiver,
                messageText: $validated['message_text'],
                messageType: $validated['message_type'] ?? 'text',
                attachments: $validated['attachments'] ?? []
            );

            Log::info('Message sent successfully', [
                'message_id' => $message->id,
                'sender_id' => auth('sanctum')->id(),
                'receiver_id' => $userId,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Log audit trail
            $this->logAudit(
                'send',
                'Message',
                ['receiver_id' => $userId],
                $message->id,
                'success'
            );

            return $this->successResponse(
                new MessageResource($message),
                'Message sent successfully',
                201
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('User', $userId);
        } catch (Throwable $e) {
            return $this->handleException($e, 'Send Message', [
                'sender_id' => auth('sanctum')->id(),
                'receiver_id' => $userId,
                'ip_address' => $request->ip(),
            ]);
        }
    }

    /**
     * Mark message as read
     *
     * Marks a received message as read.
     *
     * Authorization: Only the message receiver can mark messages as read.
     *
     * @OA\Post(
     *     path="/messages/{id}/read",
     *     operationId="markMessageAsRead",
     *     tags={"Notifications"},
     *     summary="Mark message as read",
     *     description="Mark a received message as read",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Message ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message marked as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="Message marked as read"),
     *             @OA\Property(property="data", ref="#/components/schemas/MessageResource"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Only receiver can mark as read"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Message not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @param int $id Message ID
     * @return JsonResponse
     */
    public function markMessageAsRead(int $id): JsonResponse
    {
        try {
            // Find message
            $message = Message::findOrFail($id);

            // Check authorization - only receiver can mark as read
            if ($message->receiver_id !== auth('sanctum')->id()) {
                Log::warning('Unauthorized message read attempt', [
                    'message_id' => $id,
                    'user_id' => auth('sanctum')->id(),
                    'receiver_id' => $message->receiver_id,
                    'timestamp' => now()->toIso8601String(),
                ]);

                return $this->forbiddenResponse('read', 'Message');
            }

            Log::info('Marking message as read', [
                'message_id' => $id,
                'user_id' => auth('sanctum')->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Mark as read
            $message->markAsRead();

            // Log audit trail
            $this->logAudit(
                'read',
                'Message',
                ['message_id' => $id],
                $message->id,
                'success'
            );

            return $this->successResponse(
                new MessageResource($message),
                'Message marked as read',
                200
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Message', $id);
        } catch (Throwable $e) {
            return $this->handleException($e, 'Mark Message As Read', [
                'message_id' => $id,
                'user_id' => auth('sanctum')->id(),
            ]);
        }
    }
}
