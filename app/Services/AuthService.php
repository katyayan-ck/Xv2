<?php

namespace App\Services;

use App\Exceptions\AccountLockedException;
use App\Exceptions\AuthenticationException;
use App\Exceptions\RateLimitException;
use App\Exceptions\ValidationException;
use App\Models\Core\DeviceSession;
use App\Models\Core\OtpAttemptLog;
use App\Models\Core\OtpToken;
use App\Models\User;
use App\Services\OtpNotificationService;
use Carbon\Carbon;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Enums\ErrorCodeEnum;
use Illuminate\Support\Str;

/**
 * Authentication Service
 * 
 * Handles OTP-based authentication flow:
 * 1. Request OTP - Validates mobile, generates OTP, sends via email/SMS
 * 2. Verify OTP - Validates OTP, creates device session, issues Sanctum token
 * 3. User Details - Retrieves authenticated user profile
 * 4. Logout - Revokes authentication token
 * 
 * Features:
 * - Rate limiting (3 OTP requests per 15 minutes)
 * - Account locking (after 5 failed attempts, locked for 30 minutes)
 * - Device session management (max 5 devices per user)
 * - Email & SMS notifications
 * - Comprehensive logging and audit trail
 */
class AuthService
{
    /**
     * OTP Configuration
     */
    private const OTP_LENGTH = 6;
    private const OTP_EXPIRY_MINUTES = 10;
    private const MAX_OTP_REQUESTS = 5;
    private const OTP_REQUEST_WINDOW_MINUTES = 15;
    private const MAX_OTP_ATTEMPTS = 5;
    private const OTP_ATTEMPT_WINDOW_MINUTES = 15;
    private const ACCOUNT_LOCK_DURATION_MINUTES = 30;
    private const DEVICE_LIMIT = 2;
    private const TOKEN_EXPIRY_HOURS = 24;

    /**
     * HTTP Request instance
     */
    protected Request $request;

    /**
     * Notification Service instance
     */
    protected OtpNotificationService $notificationService;

    /**
     * Constructor
     * 
     * @param Request $request HTTP request (injected by Laravel)
     * @param OtpNotificationService $notificationService Notification service
     */
    public function __construct(Request $request, OtpNotificationService $notificationService)
    {
        $this->request = $request;
        $this->notificationService = $notificationService;
    }

    /**
     * Request OTP for login
     * 
     * Generates a 6-digit OTP, stores it in database with hash,
     * implements rate limiting, and sends via Email and SMS.
     * 
     * Validates:
     * - Mobile format (10 digits, numeric)
     * - User exists with this mobile (CHECKS DATABASE)
     * - Account not locked
     * - Rate limit not exceeded (3 requests per 15 minutes)
     * 
     * @param string $mobile Mobile number (10 digits)
     * @return array Success response with expires_at
     * @throws ValidationException If mobile invalid
     * @throws AuthenticationException If user not found
     * @throws AccountLockedException If account locked
     * @throws RateLimitException If rate limit exceeded
     */
    public function requestOtp(string $mobile): array
    {
        try {
            // Clean mobile number - remove non-digits
            $mobile = preg_replace('/[^0-9]/', '', $mobile);

            // Validate mobile format
            if (!$this->isValidMobile($mobile)) {
                throw new ValidationException(
                    'Invalid mobile number format',
                    ['mobile' => 'Mobile must be exactly 10 digits']
                );
            }

            // ✅ CHECK IF USER EXISTS IN DATABASE
            $user = User::where('mobile', $mobile)
                ->whereNull('deleted_at')
                ->first();

            if (!$user) {
                Log::warning('OTP request for non-existent mobile', [
                    'mobile' => $mobile,
                    'ip_address' => $this->request->ip(),
                ]);

                // ✅ Throw authentication exception - user not found
                throw new AuthenticationException(
                    ErrorCodeEnum::AUTH_USER_NOT_FOUND,
                    "Mobile number '{$mobile}' not registered in system"
                );
            }

            // Check if account is locked
            $lockKey = "account_lock:{$mobile}";
            if (Cache::has($lockKey)) {
                Log::warning('Attempt to request OTP for locked account', [
                    'mobile' => $mobile,
                    'user_id' => $user->id,
                    'ip_address' => $this->request->ip(),
                ]);

                throw new AccountLockedException(
                    'Account locked due to multiple failed attempts'
                );
            }

            // Check rate limit: max 3 OTP requests per 15 minutes
            $rateLimitKey = "otp_request:{$mobile}";
            $requestCount = Cache::get($rateLimitKey, 0);

            if ($requestCount >= self::MAX_OTP_REQUESTS) {
                Log::warning('OTP request rate limit exceeded', [
                    'mobile' => $mobile,
                    'user_id' => $user->id,
                    'requests' => $requestCount,
                ]);

                throw new RateLimitException(
                    'OTP requests',
                    self::MAX_OTP_REQUESTS,
                    self::OTP_REQUEST_WINDOW_MINUTES * 60
                );
            }

            // Generate 6-digit OTP
            $otp = str_pad(random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
            $expiresAt = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

            // Delete previous OTP for this mobile
            OtpToken::where('mobile', $mobile)->delete();

            // Create new OTP token with hash
            $otpToken = OtpToken::create([
                'user_id' => $user->id,
                'mobile' => $mobile,
                'otp_hash' => Hash::make($otp),
                'expires_at' => $expiresAt,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Log the request
            OtpAttemptLog::create([
                'user_id' => $user->id,
                'mobile' => $mobile,
                'action' => 'request',
                'ip_address' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // ✅ SEND OTP VIA EMAIL AND SMS
            try {
                $this->notificationService->sendViaEmailAndSms(
                    $user->email,
                    $mobile,
                    $otp
                );

                Log::info('OTP notification sent', [
                    'user_id' => $user->id,
                    'mobile' => $mobile,
                    'method' => 'email_and_sms',
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send OTP notification', [
                    'user_id' => $user->id,
                    'mobile' => $mobile,
                    'error' => $e->getMessage(),
                ]);
                // Don't break flow - OTP still created even if notification fails
            }

            // Increment rate limit counter
            Cache::put(
                $rateLimitKey,
                $requestCount + 1,
                now()->addMinutes(self::OTP_REQUEST_WINDOW_MINUTES)
            );

            Log::info('OTP requested successfully', [
                'user_id' => $user->id,
                'mobile' => $mobile,
                'expires_at' => $expiresAt,
            ]);

            return [
                'success' => true,
                'data' => [
                    'mobile' => $mobile,
                    'expires_at' => $expiresAt->toIso8601String(),
                    'expires_in_minutes' => self::OTP_EXPIRY_MINUTES,
                    'otp' => config('app.debug') ? $otp : null, // Only in debug mode
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Error requesting OTP', [
                'mobile' => $mobile ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify OTP and create authenticated session
     * 
     * Validates OTP, creates device session, generates Sanctum token,
     * and handles device limit checks.
     * 
     * @param string $mobile Mobile number (10 digits)
     * @param string $otp OTP code (6 digits)
     * @param string $deviceId Unique device identifier (UUID)
     * @param string $deviceName Device name/model
     * @param string $platform Platform (iOS, Android, Web)
     * @return array Success response with token and user data
     * @throws ValidationException If inputs invalid
     * @throws AuthenticationException If OTP invalid/expired
     * @throws AccountLockedException If account locked
     */
    public function verifyOtp(
        string $mobile,
        string $otp,
        string $deviceId,
        string $deviceName,
        string $platform
    ): array {
        try {
            // Clean mobile number - remove non-digits
            $mobile = preg_replace('/[^0-9]/', '', $mobile);

            // Validate inputs
            if (!$this->isValidMobile($mobile)) {
                throw new ValidationException(
                    'Invalid mobile number format',
                    ['mobile' => ['Mobile must be exactly 10 digits']]
                );
            }

            if (strlen($otp) != self::OTP_LENGTH || !ctype_digit($otp)) {
                throw new ValidationException(
                    'Invalid OTP format',
                    ['otp' => ['OTP must be 6 digits']]
                );
            }

            if (empty($deviceId) || empty($deviceName) || empty($platform)) {
                throw new ValidationException(
                    'Missing device information',
                    [
                        'device_id' => ['Device ID is required'],
                        'device_name' => ['Device name is required'],
                        'platform' => ['Platform is required'],
                    ]
                );
            }

            // FIND USER IN DATABASE
            $user = User::where('mobile', $mobile)
                ->whereNull('deleted_at')
                ->first();

            if (!$user) {
                throw new AuthenticationException(
                    ErrorCodeEnum::AUTH_USER_NOT_FOUND,
                    'Mobile number ' . $mobile . ' not registered'
                );
            }

            // Check if account is locked
            $lockKey = 'account_lock_' . $mobile;
            if (Cache::has($lockKey)) {
                \Log::warning('OTP verification attempt on locked account', [
                    'user_id' => $user->id,
                    'mobile' => $mobile,
                    'ip_address' => $this->request->ip(),
                ]);

                throw new AccountLockedException(
                    'Account locked due to multiple failed attempts'
                );
            }

            // Check rate limit - max 5 verification attempts per 15 minutes
            $failureKey = 'otp_failures_' . $mobile;
            $failureCount = Cache::get($failureKey, 0);

            if ($failureCount >= self::MAX_OTP_ATTEMPTS) {
                \Log::warning('OTP verification rate limit exceeded', [
                    'user_id' => $user->id,
                    'mobile' => $mobile,
                    'attempts' => $failureCount,
                ]);

                // Lock account for 30 minutes
                Cache::put(
                    $lockKey,
                    true,
                    now()->addMinutes(self::ACCOUNT_LOCK_DURATION_MINUTES)
                );

                OtpAttemptLog::create([
                    'user_id' => $user->id,
                    'mobile' => $mobile,
                    'action' => 'verify_blocked',
                    'ip_address' => $this->request->ip(),
                    'user_agent' => $this->request->userAgent(),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                throw new AccountLockedException(
                    'Account locked due to too many failed OTP attempts'
                );
            }

            // GET LATEST OTP TOKEN (not expired, not used)
            $otpToken = OtpToken::where('user_id', $user->id)
                ->where('mobile', $mobile)
                ->where('expires_at', '>', now())
                ->whereNull('used_at')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$otpToken) {
                \Log::warning('OTP not found or expired', [
                    'user_id' => $user->id,
                    'mobile' => $mobile,
                ]);

                // Increment failure count
                Cache::put(
                    $failureKey,
                    $failureCount + 1,
                    now()->addMinutes(self::OTP_ATTEMPT_WINDOW_MINUTES)
                );

                OtpAttemptLog::create([
                    'user_id' => $user->id,
                    'mobile' => $mobile,
                    'action' => 'verify_failed',
                    'ip_address' => $this->request->ip(),
                    'user_agent' => $this->request->userAgent(),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                throw new AuthenticationException(
                    ErrorCodeEnum::AUTH_OTP_EXPIRED,
                    'OTP expired. Request a new one'
                );
            }

            // VERIFY OTP HASH
            if (!Hash::check($otp, $otpToken->otp_hash)) {
                \Log::warning('Invalid OTP provided', [
                    'user_id' => $user->id,
                    'mobile' => $mobile,
                ]);

                // Increment failure count
                $newFailureCount = $failureCount + 1;
                Cache::put(
                    $failureKey,
                    $newFailureCount,
                    now()->addMinutes(self::OTP_ATTEMPT_WINDOW_MINUTES)
                );

                // Lock account after 5 failures
                if ($newFailureCount >= self::MAX_OTP_ATTEMPTS) {
                    Cache::put(
                        $lockKey,
                        true,
                        now()->addMinutes(self::ACCOUNT_LOCK_DURATION_MINUTES)
                    );
                }

                OtpAttemptLog::create([
                    'user_id' => $user->id,
                    'mobile' => $mobile,
                    'action' => 'verify_failed',
                    'ip_address' => $this->request->ip(),
                    'user_agent' => $this->request->userAgent(),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                throw new AuthenticationException(
                    ErrorCodeEnum::AUTH_INVALID_OTP,
                    'Invalid OTP'
                );
            }

            // ✅ CRITICAL: Create device session FIRST (before token)
            $deviceSession = DeviceSession::create([
                'user_id' => $user->id,
                'device_id' => $deviceId,
                'device_name' => $deviceName,
                'platform' => $platform,
                'ip_address' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'last_active_at' => now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // ✅ CRITICAL: Mark OTP as used
            $otpToken->update([
                'used_at' => now(),
                'updated_by' => $user->id,
            ]);

            // ✅ CRITICAL FIX: Create token WITH device_id in abilities!
            // Format: ['*', 'device_id:xxxxx'] - this is what ValidateDevice middleware looks for
            $token = $user->createToken(
                'api-token-' . $deviceId,  // Unique token name per device
                ['*', 'device_id:' . $deviceId]  // ← IMPORTANT: Add device_id to abilities!
            )->plainTextToken;

            // Clear failure count on success
            Cache::forget($failureKey);

            \Log::info('OTP verified and token created', [
                'user_id' => $user->id,
                'mobile' => $mobile,
                'device_id' => $deviceId,
                'token_created_at' => now()->toIso8601String(),
            ]);

            return [
                'success' => true,
                'http_status' => 200,
                'data' => [
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'user' => [
                        'id' => $user->id,
                        'code' => $user->code,
                        'name' => $user->name,
                        'email' => $user->email,
                        'mobile' => $user->mobile,
                        'is_active' => $user->is_active,
                        'created_at' => $user->created_at?->toIso8601String(),
                    ],
                    'device' => [
                        'id' => $deviceSession->id,
                        'device_id' => $deviceSession->device_id,
                        'device_name' => $deviceSession->device_name,
                        'platform' => $deviceSession->platform,
                        'last_active_at' => $deviceSession->last_active_at?->toIso8601String(),
                    ],
                ],
            ];
        } catch (AuthenticationException $e) {
            \Log::warning('OTP verification failed - ' . $e->getMessage(), [
                'mobile' => $mobile ?? null,
                'device_id' => $deviceId ?? null,
            ]);
            throw $e;
        } catch (ValidationException $e) {
            \Log::warning('OTP verification validation failed', [
                'mobile' => $mobile ?? null,
                'errors' => $e->getErrors(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Error verifying OTP', [
                'mobile' => $mobile ?? null,
                'device_id' => $deviceId ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get current authenticated user details
     * 
     * @param User $user The authenticated user
     * @return array User details with sessions and roles
     */
    public function getUserDetails(User $user): array
    {
        try {
            // Refresh user from database
            $user = User::find($user->id);
            if (!$user) {
                throw new AuthenticationException(
                    ErrorCodeEnum::AUTH_USER_NOT_FOUND,
                    'User not found'
                );
            }

            // Get device sessions
            $devices = DeviceSession::where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->select('id', 'device_id', 'device_name', 'platform', 'last_active_at', 'created_at')
                ->get()
                ->map(fn($d) => [
                    'id' => $d->id,
                    'device_id' => $d->device_id,
                    'device_name' => $d->device_name,
                    'platform' => $d->platform,
                    'last_active_at' => $d->last_active_at,
                    'created_at' => $d->created_at,
                ])
                ->toArray();

            // Get role assignments
            $roleAssignments = $user->roleAssignments()
                ->where('is_current', true)
                ->where(function ($q) {
                    $q->whereNull('to_date')->orWhere('to_date', '>', now());
                })
                ->select('id', 'role_id', 'from_date', 'to_date', 'is_current')
                ->get()
                ->map(fn($a) => [
                    'id' => $a->id,
                    'role_id' => $a->role_id,
                    'from_date' => $a->from_date,
                    'to_date' => $a->to_date,
                    'is_current' => (bool) $a->is_current,
                ])
                ->toArray();

            // Get current token
            $currentToken = $user->currentAccessToken();

            Log::info('User details retrieved', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return [
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'code' => $user->code ?? null,
                    'is_active' => (bool) $user->is_active,
                    'created_at' => $user->created_at,
                    'last_login_at' => $user->last_login_at,
                    'role_assignments' => $roleAssignments,
                    'devices' => $devices,
                    'current_token' => $currentToken ? [
                        'abilities' => $currentToken->abilities,
                        'last_used_at' => $currentToken->last_used_at,
                    ] : null,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Error retrieving user details', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Logout user and revoke current token
     * 
     * @param User $user The authenticated user
     * @return array Success response
     */
    public function logout(User $user): array
    {
        try {
            $mobile = $user->mobile ?? 'unknown';

            // Revoke current token
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }

            // Log logout
            OtpAttemptLog::create([
                'user_id' => $user->id,
                'mobile' => $mobile,
                'action' => 'logout',
                'ip_address' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            Log::info('User logged out', [
                'user_id' => $user->id,
                'mobile' => $mobile,
            ]);

            return [
                'success' => true,
                'message' => 'Logged out successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Error during logout', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate mobile number format
     * 
     * Checks if mobile is 10-digit numeric (Indian format)
     * 
     * @param string $mobile Mobile number
     * @return bool True if valid format
     */
    private function isValidMobile(string $mobile): bool
    {
        // Remove any non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', $mobile);

        // Check if it's 10 digits and numeric
        return strlen($cleaned) === 10 && is_numeric($cleaned);
    }
}
