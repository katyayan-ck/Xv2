<?php

namespace App\Exceptions;

use App\Enums\ErrorCodeEnum;

/**
 * Authentication Exception
 * 
 * Thrown for authentication-related errors:
 * - User not found
 * - Invalid OTP
 * - OTP expired
 * - Device limit exceeded
 * 
 * HTTP Status: 401 (Unauthorized)
 */
class AuthenticationException extends ApplicationException
{
    /**
     * Constructor
     * 
     * @param ErrorCodeEnum|string $errorCode Error code enum or message
     * @param string|null $message Error message (if first param is ErrorCodeEnum)
     * @param int|null $statusCode HTTP status code override
     */
    public function __construct(
        ErrorCodeEnum|string $errorCode = ErrorCodeEnum::AUTH_UNAUTHORIZED,
        ?string $message = null,
        ?int $statusCode = null
    ) {
        // Handle both forms of construction
        if (is_string($errorCode)) {
            // If first param is string, treat it as message
            $message = $errorCode;
            $errorCode = ErrorCodeEnum::AUTH_UNAUTHORIZED;
        }

        $message = $message ?? $errorCode->message();
        $statusCode = $statusCode ?? $errorCode->statusCode();

        parent::__construct($message, $errorCode, $statusCode);
    }
}
