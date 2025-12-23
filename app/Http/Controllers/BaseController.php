<?php

namespace App\Http\Controllers;

use App\Enums\ErrorCodeEnum;
use App\Exceptions\ApplicationException;
use App\Exceptions\AuthenticationException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ValidationException;
use Exception;
use Illuminate\Auth\AuthenticationException as LaravelAuthException;
use Illuminate\Auth\Access\AuthorizationException as LaravelAuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * BaseController
 * 
 * Enhanced base controller with robust exception/error handling.
 * Provides unified response formatting and error management.
 * 
 * Key Features:
 * - Centralized error code management via ErrorCodeEnum
 * - Custom exception handling for all error types
 * - Proper distinction between Exception and Error
 * - Contextual logging with audit trails
 * - Consistent JSON response format across all endpoints
 * - Support for field-level validation errors
 * 
 * @package App\Http\Controllers
 * @author VDMS Development Team
 * @version 2.0
 */
abstract class BaseController extends Controller
{
    use AuthorizesRequests;

    // ═══════════════════════════════════════════════════════════════════════════════
    // SUCCESS RESPONSES
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Return successful response
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @param array|null $meta Additional metadata
     * @return JsonResponse
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Operation successful',
        int $statusCode = 200,
        ?array $meta = null
    ): JsonResponse {
        return response()->json([
            'http_status' => $statusCode,
            'success' => true,
            'code' => 'S' . str_pad($statusCode, 3, '0', STR_PAD_LEFT),
            'message' => $message,
            'data' => $data,
            'meta' => array_merge(
                ['timestamp' => now()->toIso8601String()],
                $meta ?? []
            ),
        ], $statusCode);
    }

    /**
     * Return paginated response
     * 
     * @param mixed $data Paginated items
     * @param object $paginator Laravel paginator instance
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @return JsonResponse
     */
    protected function paginatedResponse(
        mixed $data,
        object $paginator,
        string $message = 'Data retrieved successfully',
        int $statusCode = 200
    ): JsonResponse {
        return $this->successResponse(
            $data,
            $message,
            $statusCode,
            [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ]
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // ERROR RESPONSES
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Return error response
     * 
     * @param ErrorCodeEnum $errorCode Error code enum
     * @param string $message Error message
     * @param array|null $errors Field-level validation errors
     * @param int|null $statusCode HTTP status code (uses error code default if null)
     * @return JsonResponse
     */
    protected function errorResponse(
        ErrorCodeEnum $errorCode,
        ?string $message = null,
        ?array $errors = null,
        ?int $statusCode = null
    ): JsonResponse {
        $finalMessage = $message ?? $errorCode->message();
        $finalStatusCode = $statusCode ?? $errorCode->statusCode();

        $response = [
            'http_status' => $finalStatusCode,
            'success' => false,
            'code' => $errorCode->value,
            'message' => $finalMessage,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        $response['timestamp'] = now()->toIso8601String();

        return response()->json($response, $finalStatusCode);
    }

    /**
     * Return validation error response
     * 
     * @param array $errors Field-level validation errors
     * @param string|null $message Custom message
     * @return JsonResponse
     */
    protected function validationErrorResponse(
        array $errors,
        ?string $message = null
    ): JsonResponse {
        return $this->errorResponse(
            ErrorCodeEnum::VALIDATION_FAILED,
            $message ?? 'Validation failed. Please check the errors.',
            $errors,
            422
        );
    }

    /**
     * Return unauthorized response
     * 
     * @param string|null $message Custom message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(?string $message = null): JsonResponse
    {
        return $this->errorResponse(
            ErrorCodeEnum::AUTH_UNAUTHORIZED,
            $message
        );
    }

    /**
     * Return forbidden response
     * 
     * @param string|null $action Action that was forbidden
     * @param string|null $resource Resource being accessed
     * @return JsonResponse
     */
    protected function forbiddenResponse(
        ?string $action = null,
        ?string $resource = null
    ): JsonResponse {
        $message = 'Access forbidden.';
        if ($action && $resource) {
            $message = "You are not authorized to {$action} {$resource}.";
        }

        return $this->errorResponse(ErrorCodeEnum::AUTH_FORBIDDEN, $message);
    }

    /**
     * Return not found response
     * 
     * @param string $resource Resource name
     * @param mixed|null $identifier Resource identifier
     * @return JsonResponse
     */
    protected function notFoundResponse(
        string $resource,
        mixed $identifier = null
    ): JsonResponse {
        $message = "{$resource} not found.";
        if ($identifier !== null) {
            $message = "{$resource} with ID '{$identifier}' not found.";
        }

        return $this->errorResponse(ErrorCodeEnum::RESOURCE_NOT_FOUND, $message);
    }

    /**
     * Return duplicate entry response
     * 
     * @param string $message Custom message
     * @return JsonResponse
     */
    protected function duplicateEntryResponse(string $message): JsonResponse
    {
        return $this->errorResponse(
            ErrorCodeEnum::VALIDATION_DUPLICATE_ENTRY,
            $message,
            null,
            409
        );
    }

    /**
     * Return rate limit exceeded response
     * 
     * @param int $retryAfterSeconds Seconds to wait before retry
     * @return JsonResponse
     */
    protected function rateLimitResponse(int $retryAfterSeconds = 60): JsonResponse
    {
        return response()->json([
            'http_status' => 429,
            'success' => false,
            'code' => ErrorCodeEnum::AUTH_OTP_RATE_LIMIT->value,
            'message' => "Too many requests. Please try again in {$retryAfterSeconds} seconds.",
            'retry_after_seconds' => $retryAfterSeconds,
            'timestamp' => now()->toIso8601String(),
        ], 429)
            ->header('Retry-After', $retryAfterSeconds);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // EXCEPTION HANDLING
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Handle any exception/error and return appropriate response
     * 
     * This is the main exception handler called from catch blocks.
     * It handles both Exceptions and Errors, with proper type checking.
     * 
     * @param Throwable $exception Exception or Error object
     * @param string|null $context Context where exception occurred (for logging)
     * @param array|null $data Additional context data
     * @return JsonResponse
     */
    protected function handleException(
        Throwable $exception,
        ?string $context = null,
        ?array $data = null
    ): JsonResponse {
        // Log the exception first
        $this->logException($exception, $context, $data);

        // Handle application exceptions
        if ($exception instanceof ApplicationException) {
            return $this->errorResponse(
                $exception->getErrorCode(),
                $exception->getMessage(),
                $exception->getErrors() ?: null,
                $exception->getStatusCode()
            );
        }

        // Handle Laravel validation exceptions
        if ($exception instanceof LaravelValidationException) {
            return $this->validationErrorResponse($exception->errors());
        }

        // Handle Laravel authentication exceptions
        if ($exception instanceof LaravelAuthException) {
            return $this->unauthorizedResponse('Authentication failed.');
        }

        // Handle Laravel authorization exceptions
        if ($exception instanceof LaravelAuthorizationException) {
            return $this->forbiddenResponse();
        }

        // Handle database exceptions
        if (
            $exception instanceof \PDOException ||
            $exception instanceof \Illuminate\Database\QueryException
        ) {
            return $this->errorResponse(
                ErrorCodeEnum::DATABASE_QUERY_FAILED,
                config('app.debug') ? $exception->getMessage() : 'Database error occurred.'
            );
        }

        // Handle timeout exceptions
        if (
            str_contains($exception->getMessage(), 'timeout') ||
            str_contains($exception->getMessage(), 'Timeout')
        ) {
            return $this->errorResponse(
                ErrorCodeEnum::SERVICE_TIMEOUT,
                'Request timed out. Please try again.'
            );
        }

        // Handle general exceptions
        return $this->errorResponse(
            ErrorCodeEnum::SYSTEM_ERROR,
            config('app.debug') ? $exception->getMessage() : 'An error occurred. Please contact support.'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // LOGGING & AUDIT
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Log exception with full context
     * 
     * @param Throwable $exception Exception or Error
     * @param string|null $context Context information
     * @param array|null $data Additional context data
     * @return void
     */
    protected function logException(
        Throwable $exception,
        ?string $context = null,
        ?array $data = null
    ): void {
        $logContext = [
            'exception' => get_class($exception),
            'exception_type' => class_basename($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'user_id' => auth()->id(),
            'request_path' => request()->path(),
            'request_method' => request()->method(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ];

        // Add context if provided
        if ($context) {
            $logContext['context'] = $context;
        }

        // Add data if provided
        if ($data) {
            $logContext['additional_data'] = $data;
        }

        // Log based on exception type
        if ($exception instanceof ApplicationException) {
            Log::warning("Application Exception: {$context}", $logContext);
        } else {
            Log::error("Exception Occurred: {$context}", $logContext);
        }
    }

    /**
     * Log audit trail for operations
     * 
     * @param string $action Action performed (create, update, delete, etc.)
     * @param string $resource Resource type
     * @param array $changes Changes made
     * @param int|string|null $resourceId Resource identifier
     * @param string|null $status Operation status
     * @return void
     */
    protected function logAudit(
        string $action,
        string $resource,
        array $changes = [],
        int|string|null $resourceId = null,
        ?string $status = 'success'
    ): void {
        $auditData = [
            'action' => $action,
            'resource' => $resource,
            'resource_id' => $resourceId,
            'status' => $status,
            'changes' => $changes,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ];

        Log::info("Audit: {$action} {$resource}", $auditData);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // AUTHORIZATION
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Check authorization and throw exception if not allowed
     * 
     * @param string $ability
     * @param mixed $resource
     * @return void
     * @throws AuthorizationException
     */
    public function authorize(string $ability, mixed $resource = null): void
    {
        if (!$this->canPerform($ability, $resource)) {
            throw new AuthorizationException(
                ErrorCodeEnum::AUTH_FORBIDDEN,
                "You are not authorized to {$ability}."
            );
        }
    }

    /**
     * Check if user can perform action (non-throwing version)
     * 
     * @param string $ability
     * @param mixed $resource
     * @return bool
     */
    protected function canPerform(string $ability, mixed $resource = null): bool
    {
        try {
            parent::authorize($ability, $resource);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
