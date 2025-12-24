<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Support\Facades\Log;

/**
 * Authentication Controller
 *
 * Handles user authentication via OTP (One-Time Password)
 * - Request OTP: Send OTP to user's registered mobile and email
 * - Verify OTP: Validate OTP and issue authentication token
 * - Get Profile: Retrieve current authenticated user details
 * - Logout: Revoke authentication token
 *
 * Rate Limiting:
 * - OTP Request: Max 3 requests per 15 minutes per mobile
 * - OTP Verification: Max 5 attempts per 15 minutes per mobile
 * - Account Lock: 30 minutes after 5 failed verification attempts
 *
 * All timestamps returned in ISO 8601 format (2025-12-24T18:30:00Z)
 *
 * @OA\Info(
 *     title="VDMS API",
 *     version="1.0.0",
 *     description="Vehicle Dealership Management System API",
 *     contact={
 *         "email": "support@vdms.com"
 *     }
 * )
 *
 * @OA\Server(
 *     url="http://localhost/vdms/public/api/v1",
 *     description="Development Server"
 * )
 * 
 * @OA\Server(
 *     url="https://waba.insightechindia.in/public/api/v1/",
 *     description="Production Server"
 * )
 * 
 *
 * @OA\Components(
 *     @OA\SecurityScheme(
 *         type="http",
 *         scheme="bearer",
 *         bearerFormat="sanctum",
 *         securityScheme="sanctum"
 *     )
 * )
 */
class AuthController extends BaseController
{
    /**
     * Authentication service
     *
     * @var AuthService
     */
    protected AuthService $authService;

    /**
     * Constructor
     *
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    // ╔════════════════════════════════════════════════════════╗
    // ║ PUBLIC ENDPOINTS (No Authentication Required) ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Request OTP for login
     *
     * Validates mobile number and sends OTP via SMS and Email.
     * Implements rate limiting: maximum 3 requests per 15 minutes per mobile.
     *
     * Rate Limits:
     * - 3 requests per 15 minutes per mobile number
     * - Exceeding limit returns HTTP 429 (Too Many Requests)
     *
     * @OA\Post(
     *     path="/auth/request-otp",
     *     operationId="requestOtp",
     *     tags={"Authentication"},
     *     summary="Request OTP for login",
     *     description="Send OTP to registered mobile and email for authentication. Returns human-readable timestamps in ISO 8601 format.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mobile"},
     *             @OA\Property(property="mobile", type="string", maxLength=10, example="9876543210", description="10-digit mobile number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="OTP sent to your registered mobile and email"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="mobile", type="string", example="9876543210"),
     *                 @OA\Property(property="expires_at", type="string", example="2025-12-24T18:35:00Z", description="ISO 8601 UTC format"),
     *                 @OA\Property(property="expires_in_minutes", type="integer", example=5),
     *                 @OA\Property(property="otp", type="string|null", example=null, description="Only shown in debug mode")
     *             ),
     *             @OA\Property(property="timestamp", type="string", example="2025-12-24T18:30:00Z", description="ISO 8601 UTC format")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - invalid mobile format",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=422),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="string", example="VALIDATION_FAILED"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="mobile", type="array",
     *                     @OA\Items(type="string", example="Mobile must be exactly 10 digits.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Mobile not registered in system",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=404),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="string", example="AUTH_USER_NOT_FOUND"),
     *             @OA\Property(property="message", type="string", example="Mobile number '9876543210' not registered in system")
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Rate limit exceeded - too many OTP requests",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=429),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="string", example="RATE_LIMIT_EXCEEDED"),
     *             @OA\Property(property="message", type="string", example="Max 3 OTP requests allowed within 900 seconds")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @param Request $request HTTP request with mobile field
     * @return JsonResponse Standard JSON response with OTP status and expiration
     */
    public function requestOtp(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([
                'mobile' => 'required|string|regex:/^[0-9]{10}$/',
            ], [
                'mobile.required' => 'Mobile number is required.',
                'mobile.regex' => 'Mobile must be exactly 10 digits.',
            ]);

            Log::info('OTP request initiated', [
                'mobile' => $validated['mobile'],
                'ip_address' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Call service to request OTP
            $result = $this->authService->requestOtp($validated['mobile']);

            Log::info('OTP sent successfully', [
                'mobile' => $validated['mobile'],
                'timestamp' => now()->toIso8601String(),
            ]);

            // Log audit trail
            $this->logAudit('request', 'OTP', [], null, 'success');

            // Return success response
            return $this->successResponse(
                $result['data'] ?? [],
                'OTP sent to your registered mobile and email',
                200
            );
        } catch (Throwable $e) {
            // Handle all exceptions uniformly
            return $this->handleException($e, 'OTP Request', [
                'mobile' => $request->input('mobile'),
                'ip_address' => $request->ip(),
            ]);
        }
    }

    // ╔════════════════════════════════════════════════════════╗
    // ║ PUBLIC ENDPOINTS (Continued) ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Verify OTP and create authenticated session
     *
     * Validates OTP code and device information to create user session.
     * Implements attempt limiting: maximum 5 attempts per 15 minutes.
     * Issues Sanctum bearer token for subsequent API requests.
     *
     * Device Binding:
     * - Each device must be uniquely identified by device_id
     * - Maximum 5 devices per user (configurable)
     * - Exceeding limit returns HTTP 403
     *
     * Rate Limits:
     * - 5 verification attempts per 15 minutes per mobile
     * - Account lock: 30 minutes after 5 failed attempts
     *
     * Response includes:
     * - Bearer token (valid for 24 hours)
     * - User profile information
     * - Device session details
     *
     * @OA\Post(
     *     path="/auth/verify-otp",
     *     operationId="verifyOtp",
     *     tags={"Authentication"},
     *     summary="Verify OTP and bind device for login",
     *     description="Verify OTP code and create authenticated session with device binding. Returns human-readable timestamps in ISO 8601 format.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mobile","otp","device_id","device_name","platform"},
     *             @OA\Property(property="mobile", type="string", maxLength=10, example="9876543210"),
     *             @OA\Property(property="otp", type="string", maxLength=6, example="123456"),
     *             @OA\Property(property="device_id", type="string", example="550e8400-e29b-41d4-a716-446655440000", description="UUID format"),
     *             @OA\Property(property="device_name", type="string", example="iPhone 14 Pro"),
     *             @OA\Property(property="platform", type="string", enum={"iOS","Android","Web"}, example="iOS"),
     *             @OA\Property(property="device_info", type="object", description="Optional device details",
     *                 @OA\Property(property="os_version", type="string", example="14.5"),
     *                 @OA\Property(property="app_version", type="string", example="1.0.0")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful - token issued",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="1|abcd1234efgh5678ijkl9012mnop3456", description="Sanctum bearer token"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="mobile", type="string", example="9876543210"),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 ),
     *                 @OA\Property(property="device", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="device_id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="device_name", type="string", example="iPhone 14 Pro"),
     *                     @OA\Property(property="platform", type="string", example="iOS")
     *                 )
     *             ),
     *             @OA\Property(property="timestamp", type="string", example="2025-12-24T18:30:00Z", description="ISO 8601 UTC format")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid or expired OTP",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=401),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="string", example="AUTH_OTP_INVALID"),
     *             @OA\Property(property="message", type="string", example="Invalid OTP code. Please try again")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Account locked or device limit exceeded",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=403),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="string", example="AUTH_FORBIDDEN"),
     *             @OA\Property(property="message", type="string", example="Account locked due to multiple failed attempts")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Mobile not registered",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=404),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="string", example="AUTH_USER_NOT_FOUND")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     )
     * )
     *
     * @param Request $request Request containing mobile, OTP, device info
     * @return JsonResponse Response with token and user data on success
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        try {
            // Validate all required fields
            $validated = $request->validate([
                'mobile' => 'required|string|regex:/^[0-9]{10}$/',
                'otp' => 'required|string|regex:/^[0-9]{6}$/',
                'device_id' => 'required|string|max:255',
                'device_name' => 'required|string|max:255',
                'platform' => 'required|string|in:iOS,Android,Web',
            ], [
                'mobile.regex' => 'Mobile must be 10 digits.',
                'otp.regex' => 'OTP must be 6 digits.',
                'platform.in' => 'Platform must be iOS, Android, or Web.',
            ]);

            Log::info('OTP verification attempted', [
                'mobile' => $validated['mobile'],
                'device_id' => $validated['device_id'],
                'platform' => $validated['platform'],
                'ip_address' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Call service to verify OTP
            $result = $this->authService->verifyOtp(
                $validated['mobile'],
                $validated['otp'],
                $validated['device_id'],
                $validated['device_name'],
                $validated['platform']
            );

            Log::info('User logged in successfully', [
                'user_id' => $result['data']['user']['id'] ?? null,
                'mobile' => $validated['mobile'],
                'platform' => $validated['platform'],
                'timestamp' => now()->toIso8601String(),
            ]);

            // Log audit trail
            $this->logAudit('verify', 'OTP', [], null, 'success');

            // Return success response with token
            return $this->successResponse(
                $result['data'] ?? [],
                'Login successful',
                200
            );
        } catch (Throwable $e) {
            // Handle all exceptions uniformly
            return $this->handleException($e, 'OTP Verification', [
                'mobile' => $request->input('mobile'),
                'device_id' => $request->input('device_id'),
                'ip_address' => $request->ip(),
            ]);
        }
    }

    // ╔════════════════════════════════════════════════════════╗
    // ║ PROTECTED ENDPOINTS (Authentication Required) ║
    // ╚════════════════════════════════════════════════════════╝

    /**
     * Get current authenticated user details
     *
     * Retrieves the profile information of the currently authenticated user.
     * Requires valid Bearer token in Authorization header.
     *
     * Authorization Header:
     * Authorization: Bearer {token}
     *
     * Returns:
     * - User profile (name, email, mobile, etc)
     * - Active role assignments
     * - Device sessions
     * - Current token details
     *
     * @OA\Get(
     *     path="/auth/me",
     *     operationId="getMe",
     *     tags={"Authentication"},
     *     summary="Get current user details",
     *     description="Retrieve authenticated user profile information with human-readable timestamps in ISO 8601 format.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="User profile retrieved"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="mobile", type="string", example="9876543210"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", example="2025-12-24T18:30:00Z", description="ISO 8601 UTC format"),
     *                 @OA\Property(property="last_login_at", type="string", example="2025-12-24T18:30:00Z", description="ISO 8601 UTC format"),
     *                 @OA\Property(property="role_assignments", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="role_id", type="integer"),
     *                         @OA\Property(property="is_current", type="boolean")
     *                     )
     *                 ),
     *                 @OA\Property(property="devices", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="device_id", type="string"),
     *                         @OA\Property(property="device_name", type="string"),
     *                         @OA\Property(property="platform", type="string"),
     *                         @OA\Property(property="last_activity_at", type="string", description="ISO 8601 UTC format")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="timestamp", type="string", example="2025-12-24T18:30:00Z", description="ISO 8601 UTC format")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - No valid token"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @param Request $request HTTP request with user available via middleware
     * @return JsonResponse Current user's profile data
     */
    public function me(Request $request): JsonResponse
    {
        try {
            // Get authenticated user from request
            $user = $request->user('sanctum');

            if (!$user) {
                Log::warning('Unauthorized access attempt to /me endpoint', [
                    'ip_address' => $request->ip(),
                    'timestamp' => now()->toIso8601String(),
                ]);

                return $this->unauthorizedResponse('No authenticated user found');
            }

            Log::info('User profile accessed', [
                'user_id' => $user->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Get user details from service
            $result = $this->authService->getUserDetails($user);

            return $this->successResponse(
                $result['data'] ?? $user,
                'User profile retrieved',
                200
            );
        } catch (Throwable $e) {
            // Handle all exceptions uniformly
            return $this->handleException($e, 'User Details Retrieval', [
                'user_id' => auth('sanctum')->id(),
                'ip_address' => $request->ip(),
            ]);
        }
    }

    /**
     * Logout and revoke current token
     *
     * Invalidates the current authentication token, ending the user's session.
     * Subsequent requests with this token will require re-authentication.
     *
     * The device session is NOT deleted - it remains for device history.
     * Only the API token is revoked.
     *
     * @OA\Post(
     *     path="/auth/logout",
     *     operationId="logout",
     *     tags={"Authentication"},
     *     summary="Logout and revoke token",
     *     description="Invalidate current session token and logout user",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200"),
     *             @OA\Property(property="message", type="string", example="Logged out successfully"),
     *             @OA\Property(property="timestamp", type="string", example="2025-12-24T18:30:00Z", description="ISO 8601 UTC format")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - No valid token"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @param Request $request HTTP request with user available via middleware
     * @return JsonResponse Logout confirmation
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Get authenticated user
            $user = $request->user('sanctum');

            if (!$user) {
                Log::warning('Logout attempt without authentication', [
                    'ip_address' => $request->ip(),
                    'timestamp' => now()->toIso8601String(),
                ]);

                return $this->unauthorizedResponse('No authenticated user found');
            }

            Log::info('User logout initiated', [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Call service to logout
            $result = $this->authService->logout($user);

            Log::info('User logged out successfully', [
                'user_id' => $user->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Log audit trail
            $this->logAudit('logout', 'User', [], $user->id, 'success');

            return $this->successResponse(
                null,
                'Logged out successfully',
                200
            );
        } catch (Throwable $e) {
            // Handle all exceptions uniformly
            return $this->handleException($e, 'Logout', [
                'user_id' => auth('sanctum')->id(),
                'ip_address' => $request->ip(),
            ]);
        }
    }
}
