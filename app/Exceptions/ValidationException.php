<?php

namespace App\Exceptions;

use ErrorCodeEnum;

/**
 * Validation Exception
 * 
 * Thrown when input validation fails.
 * Used for invalid mobile format, OTP format, device info, etc.
 * 
 * HTTP Status: 422 (Unprocessable Entity)
 */
class ValidationException extends ApplicationException
{
    /**
     * Field-level validation errors
     */
    protected array $errors = [];

    /**
     * Constructor
     * 
     * @param string $message Validation error message
     * @param array $errors Field-level validation errors
     * @param ErrorCodeEnum|null $errorCode Error code (defaults to VALIDATION_FAILED)
     */
    public function __construct(
        string $message = 'Validation failed',
        array $errors = [],
        ?ErrorCodeEnum $errorCode = null
    ) {
        $this->errors = $errors;
        $errorCode = $errorCode ?? ErrorCodeEnum::VALIDATION_FAILED;

        parent::__construct($message, $errorCode, 422);
    }

    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
