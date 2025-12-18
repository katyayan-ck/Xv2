<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="VDMS Auth API",
 *     version="1.0.0",
 *     description="Vehic le Dealership Management System - Authentication API",
 *     contact={
 *         "name": "API Support",
 *         "url": "https://waba.insightechindia.in/public/",
 *         "email": "support@bmpl.com"
 *     }
 * )
 *
 * @OA\Server(
 *     url="http://localhost/vdms/public/api/v1",
 *     description="Development Server"
 * )
 *
 * @OA\Server(
 *     url="https://waba.insightechindia.in/public/api/v1",
 *     description="Production Server"
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="token",
 *     securityScheme="sanctum"
 * )
 */
class AuthController extends Controller
{
    private AuthenticationService $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @OA\Post(
     *     path="/auth/request-otp",
     *     tags={"Authentication"},
     *     summary="Request OTP for login",
     *     description="Send OTP to registered mobile and email. Dev: http://localhost/vdms/public/api/v1 | Prod: https://waba.insightechindia.in/public/api/v1",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Mobile number to receive OTP",
     *         @OA\JsonContent(
     *             required={"mobile"},
     *             @OA\Property(property="mobile", type="string", example="9876543210", description="10-digit mobile number (India)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S001"),
     *             @OA\Property(property="message", type="string", example="OTP sent to your registered mobile and email"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="otp", type="string", example="123456", description="OTP (remove in production)"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", example="2025-12-17T02:07:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Mobile not registered",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=404),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="string", example="E002"),
     *             @OA\Property(property="message", type="string", example="Mobile not registered")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Account locked or exceeds rate limit",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=403),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="string", example="E004"),
     *             @OA\Property(property="message", type="string", example="Account locked. Contact admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Rate limit exceeded",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=429),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="string", example="E005"),
     *             @OA\Property(property="message", type="string", example="Too many OTP requests. Try again later")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=500),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="string", example="E500"),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function requestOtp(Request $request): JsonResponse
    {
        // try {
        $validated = $request->validate([
            'mobile' => 'required|string|regex:/^[0-9]{10}$/',
        ], [
            'mobile.required' => 'Mobile number is required',
            'mobile.regex' => 'Mobile must be 10 digits',
        ]);

        $result = $this->authService->requestOtp($validated['mobile']);

        if (!$result['success']) {
            return response()->json([
                'http_status' => $result['error']['http_status'],
                'success' => false,
                'code' => $result['error']['code'],
                'message' => $result['error']['message'],
            ], $result['error']['http_status']);
        }

        return response()->json([
            'http_status' => 200,
            'success' => true,
            'code' => 'S001',
            'message' => 'SMS : OTP Sent Successfully',
            'data' => $result['data'],
        ], 200);
        // } catch (\Illuminate\Validation\ValidationException $e) {
        //     return response()->json([
        //         'http_status' => 422,
        //         'success' => false,
        //         'code' => 'E422',
        //         'message' => 'Validation failed',
        //         'errors' => $e->errors(),
        //     ], 422);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'http_status' => 500,
        //         'success' => false,
        //         'code' => 'E500',
        //         'message' => 'Internal server error',
        //     ], 500);
        // }
    }

    /**
     * @OA\Post(
     *     path="/auth/verify-otp",
     *     tags={"Authentication"},
     *     summary="Verify OTP and bind device for login",
     *     description="Verify OTP code and create session. Dev: http://localhost/vdms/public/api/v1 | Prod: https://waba.insightechindia.in/public/api/v1",
     *     @OA\RequestBody(
     *         required=true,
     *         description="OTP verification data",
     *         @OA\JsonContent(
     *             required={"mobile","otp","device_id","device_name","platform"},
     *             @OA\Property(property="mobile", type="string", example="9876543210", description="10-digit mobile number"),
     *             @OA\Property(property="otp", type="string", example="123456", description="6-digit OTP"),
     *             @OA\Property(property="device_id", type="string", example="uuid-1234-5678-9012", description="Unique device identifier"),
     *             @OA\Property(property="device_name", type="string", example="iPhone 14 Pro", description="Device name"),
     *             @OA\Property(property="platform", type="string", example="iOS", enum={"iOS", "Android", "Web"}, description="Device platform")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S001"),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="1|abcdef123456...", description="Bearer token for subsequent requests"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="Super Admin"),
     *                     @OA\Property(property="email", type="string", example="super.admin@bmpl.com"),
     *                     @OA\Property(property="mobile", type="string", example="9876543210"),
     *                     @OA\Property(property="code", type="string", example="SUP001")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid or expired OTP",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=401),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="string", example="E002"),
     *             @OA\Property(property="message", type="string", example="Invalid OTP")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Account locked due to failed attempts",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=403),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="string", example="E004"),
     *             @OA\Property(property="message", type="string", example="Account locked due to failed attempts")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Mobile not registered",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=404),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="string", example="E002"),
     *             @OA\Property(property="message", type="string", example="Mobile not registered")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=500),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="string", example="E500"),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        // try {
        //print_r($request->all());
        $validated = $request->validate([
            'mobile' => 'required|string|regex:/^[0-9]{10}$/',
            'otp' => 'required|string|regex:/^[0-9]{6}$/',
            'device_id' => 'required|string|max:255',
            'device_name' => 'required|string|max:255',
            'platform' => 'required|string|in:iOS,Android,Web',
        ], [
            'mobile.regex' => 'Mobile must be 10 digits',
            'otp.regex' => 'OTP must be 6 digits',
            'platform.in' => 'Platform must be iOS, Android, or Web',
        ]);

        $result = $this->authService->verifyOtp(
            $validated['mobile'],
            $validated['otp'],
            $validated['device_id'],
            $validated['device_name'],
            $validated['platform']
        );
        //print_r($result);
        if (!$result['success']) {
            return response()->json([
                'http_status' => $result['http_status'],
                'success' => false,
                'code' => $result['code'],
                'message' => $result['message'],
            ], $result['http_status']);
        }

        return response()->json([
            'http_status' => 200,
            'success' => true,
            'code' => 'S001',
            'message' => 'Login successful',
            'data' => $result['data'],
        ], 200);
        // } catch (\Illuminate\Validation\ValidationException $e) {
        //     return response()->json([
        //         'http_status' => 422,
        //         'success' => false,
        //         'code' => 'E422',
        //         'message' => 'Validation failed',
        //         'errors' => $e->errors(),
        //     ], 422);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'http_status' => 500,
        //         'success' => false,
        //         'code' => 'E500',
        //         'message' => 'Internal server error',
        //     ], 500);
        // }
    }

    /**
     * @OA\Get(
     *     path="/auth/me",
     *     tags={"Authentication"},
     *     summary="Get current user details",
     *     description="Retrieve authenticated user details. Dev: http://localhost/vdms/public/api/v1 | Prod: https://waba.insightechindia.in/public/api/v1",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S001"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="Super Admin"),
     *                 @OA\Property(property="email", type="string", example="super.admin@bmpl.com"),
     *                 @OA\Property(property="mobile", type="string", example="9876543210"),
     *                 @OA\Property(property="code", type="string", example="SUP001"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - No valid token provided"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function me(Request $request): JsonResponse
    {
        // try {
        $user = $request->user('sanctum');
        //print_r($user->toarray());

        if (!$user) {
            return response()->json([
                'http_status' => 401,
                'success' => false,
                'code' => 'E001',
                'message' => 'Unauthorized',
            ], 401);
        }

        $result = $this->authService->me($user);
        //print_r($result);
        //return response()->json($result, $result['http_status']);

        return response()->json([
            'http_status' => 200,
            'success' => true,
            'code' => 'S001',
            'data' => $result['data'],
        ], 200);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'http_status' => 500,
        //         'success' => false,
        //         'code' => 'E500',
        //         'message' => 'Internal server error',
        //     ], 500);
        // }
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout and revoke token",
     *     description="Invalidate current session token. Dev: http://localhost/vdms/public/api/v1 | Prod: https://waba.insightechindia.in/public/api/v1",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="http_status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="string", example="S001"),
     *             @OA\Property(property="message", type="string", example="Logged out successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - No valid token provided"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        // try {
        $user = $request->user('sanctum');

        if (!$user) {
            return response()->json([
                'http_status' => 401,
                'success' => false,
                'code' => 'E001',
                'message' => 'Unauthorized',
            ], 401);
        }

        $result = $this->authService->logout($user);

        if (!$result['success']) {
            return response()->json([
                'http_status' => 500,
                'success' => false,
                'code' => 'E500',
                'message' => $result['message'] ?? 'Error during logout',
            ], 500);
        }

        return response()->json([
            'http_status' => 200,
            'success' => true,
            'code' => 'S001',
            'message' => 'Logged out successfully',
        ], 200);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'http_status' => 500,
        //         'success' => false,
        //         'code' => 'E500',
        //         'message' => 'Internal server error',
        //     ], 500);
        // }
    }
}
