<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;
use App\Enums\ErrorCodeEnum;

/**
 * Base application exception class
 * 
 * All custom exceptions should extend this class.
 * Provides error code and HTTP status code mapping.
 */
abstract class ApplicationException extends Exception implements Throwable
{
    /**
     * Error code from ErrorCodeEnum
     */
    protected ErrorCodeEnum $errorCode;

    /**
     * HTTP status code
     */
    protected int $statusCode;

    /**
     * Validation errors (if any)
     */
    protected array $errors = [];

    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param ErrorCodeEnum $errorCode Error code enum
     * @param int|null $statusCode HTTP status code (uses error code default if null)
     * @param Throwable|null $previous Previous exception for chaining
     */
    public function __construct(
        string $message,
        ErrorCodeEnum $errorCode = ErrorCodeEnum::SYSTEM_ERROR,
        ?int $statusCode = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode ?? $errorCode->statusCode();
    }

    /**
     * Get error code
     * 
     * @return ErrorCodeEnum
     */
    public function getErrorCode(): ErrorCodeEnum
    {
        return $this->errorCode;
    }

    /**
     * Get HTTP status code
     * 
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get validation errors (if any)
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Set validation errors
     * 
     * @param array $errors
     * @return self
     */
    public function setErrors(array $errors): self
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Convert exception to JSON response
     * 
     * @return JsonResponse
     */
    public function toJsonResponse(): JsonResponse
    {
        $response = [
            'http_status' => $this->statusCode,
            'success' => false,
            'code' => $this->errorCode->value,
            'message' => $this->message,
        ];

        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        $response['timestamp'] = now()->toIso8601String();

        return response()->json($response, $this->statusCode);
    }
}
