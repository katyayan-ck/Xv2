<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ErrorCodeEnum;
use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AuthController
 * 
 * Handles OTP-based authentication for mobile and web applications.
 * Provides endpoints for requesting OTP, verifying OTP, and managing user sessions.
 * 
 * Features:
 * - OTP request with rate limiting (3 requests per 15 minutes)
 * - OTP verification with attempt limiting (5 attempts per 15 minutes)
 * - Device session management for mobile apps
 * - Bearer token generation using Laravel Sanctum
 * - Comprehensive error handling with standard error codes
 * 
 * Authentication Flow:
 * 1. User requests OTP with mobile number → OTP sent via SMS/Email
 * 2. User verifies OTP with mobile + OTP + device info → Token issued
 * 3. User includes token in Authorization header for subsequent requests
 * 4. User can logout to revoke token
 * 
 * @category API Controllers
 * @package App\Http\Controllers\Api\V1
 * @author VDMS Development Team
 * @version 2.0
 */
class AuthController extends \App\Http\Controllers\BaseController
{
    protected AuthService $authService;

    /**
     * Constructor with service injection
     * 
     * @param AuthService $authService Authentication service
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Request OTP for login
     * 
     * Validates mobile number and sends OTP via SMS/Email if user exists.
     * Implements rate limiting: maximum 3 requests per 15 minutes per mobile.
     *
     * @OA\Post(
     *     path="/api/v1/auth/request-otp",
     *     operationId="requestOtp",
     *     tags={"Authentication"},
     *     summary="Request OTP for login",
     *     description="Send OTP to registered mobile and email for authentication",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mobile"},
     *             @OA\Property(property="mobile", type="string", example="9876543210")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S200")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Mobile not registered"),
     *     @OA\Response(response=429, description="Rate limit exceeded"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     * 
     * @param Request $request HTTP request with mobile field
     * @return JsonResponse Standard JSON response with OTP status
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
                'timestamp' => now(),
            ]);

            // Call service to request OTP
            $result = $this->authService->requestOtp($validated['mobile']);

            Log::info('OTP sent successfully', [
                'mobile' => $validated['mobile'],
                'timestamp' => now(),
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

    /**
     * Verify OTP and create authenticated session
     * 
     * Validates OTP code and device information to create user session.
     * Implements attempt limiting: maximum 5 attempts per 15 minutes.
     * Issues Sanctum bearer token for subsequent API requests.
     *
     * @OA\Post(
     *     path="/api/v1/auth/verify-otp",
     *     operationId="verifyOtp",
     *     tags={"Authentication"},
     *     summary="Verify OTP and bind device for login",
     *     description="Verify OTP code and create authenticated session with device binding",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mobile","otp","device_id","device_name","platform"},
     *             @OA\Property(property="mobile", type="string", example="9876543210"),
     *             @OA\Property(property="otp", type="string", example="123456"),
     *             @OA\Property(property="device_id", type="string", example="uuid-1234"),
     *             @OA\Property(property="device_name", type="string", example="iPhone 14 Pro"),
     *             @OA\Property(property="platform", type="string", enum={"iOS","Android","Web"}, example="iOS")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful"),
     *     @OA\Response(response=401, description="Invalid or expired OTP"),
     *     @OA\Response(response=403, description="Account locked"),
     *     @OA\Response(response=404, description="Mobile not registered"),
     *     @OA\Response(response=422, description="Validation failed")
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
                'timestamp' => now(),
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
                'timestamp' => now(),
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

    /**
     * Get current authenticated user details
     * 
     * Retrieves the profile information of the currently authenticated user.
     * Requires valid Bearer token in Authorization header.
     *
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     operationId="getMe",
     *     tags={"Authentication"},
     *     summary="Get current user details",
     *     description="Retrieve authenticated user profile information",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="User details retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthorized - No valid token"),
     *     @OA\Response(response=500, description="Internal server error")
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
                    'timestamp' => now(),
                ]);

                return $this->unauthorizedResponse('No authenticated user found');
            }

            Log::info('User profile accessed', [
                'user_id' => $user->id,
                'timestamp' => now(),
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
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     operationId="logout",
     *     tags={"Authentication"},
     *     summary="Logout and revoke token",
     *     description="Invalidate current session token and logout user",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Logged out successfully"),
     *     @OA\Response(response=401, description="Unauthorized - No valid token"),
     *     @OA\Response(response=500, description="Internal server error")
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
                    'timestamp' => now(),
                ]);

                return $this->unauthorizedResponse('No authenticated user found');
            }

            Log::info('User logout initiated', [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'timestamp' => now(),
            ]);

            // Call service to logout
            $result = $this->authService->logout($user);

            Log::info('User logged out successfully', [
                'user_id' => $user->id,
                'timestamp' => now(),
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
