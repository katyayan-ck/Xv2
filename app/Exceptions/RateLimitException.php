<?php

namespace App\Exceptions;

use App\Enums\ErrorCodeEnum;

/**
 * Rate Limit Exception
 * 
 * Thrown when user exceeds rate limits:
 * - Max 3 OTP requests per 15 minutes
 * - Max 5 OTP verification attempts per 15 minutes
 * 
 * HTTP Status: 429 (Too Many Requests)
 */
class RateLimitException extends ApplicationException
{
    /**
     * Number of seconds to wait before retry
     */
    private int $retryAfterSeconds;

    /**
     * Resource that was rate limited (e.g., "OTP requests")
     */
    private string $resource;

    /**
     * Constructor
     * 
     * @param string $resource What was rate limited (e.g., "OTP requests")
     * @param int $limit Maximum allowed
     * @param int $windowSeconds Time window in seconds
     */
    public function __construct(
        string $resource = 'Request',
        int $limit = 3,
        int $windowSeconds = 900
    ) {
        $this->resource = $resource;
        $this->retryAfterSeconds = $windowSeconds;

        $windowMinutes = ceil($windowSeconds / 60);
        $message = "Too many {$resource}. Maximum {$limit} allowed per {$windowMinutes} minutes.";

        parent::__construct(
            $message,
            ErrorCodeEnum::AUTH_OTP_RATE_LIMIT,
            429
        );
    }

    /**
     * Get retry after seconds
     * 
     * @return int
     */
    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }

    /**
     * Get resource name
     * 
     * @return string
     */
    public function getResource(): string
    {
        return $this->resource;
    }
}
