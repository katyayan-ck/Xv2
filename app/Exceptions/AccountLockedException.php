<?php

namespace App\Exceptions;

use App\Enums\ErrorCodeEnum;

/**
 * Account Locked Exception
 * 
 * Thrown when user account is locked due to:
 * - Too many failed OTP verification attempts (5 attempts)
 * - Too many OTP requests (3 requests per 15 minutes)
 * 
 * Account is locked for 30 minutes.
 * 
 * HTTP Status: 403 (Forbidden)
 */
class AccountLockedException extends ApplicationException
{
    /**
     * Duration in minutes for which account is locked
     */
    private int $lockDurationMinutes = 30;

    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param int $lockDurationMinutes How long account is locked
     */
    public function __construct(
        string $message = 'Account locked due to multiple failed attempts',
        int $lockDurationMinutes = 30
    ) {
        $this->lockDurationMinutes = $lockDurationMinutes;

        parent::__construct(
            $message,
            ErrorCodeEnum::AUTH_FORBIDDEN,
            403
        );
    }

    /**
     * Get lock duration in minutes
     * 
     * @return int
     */
    public function getLockDurationMinutes(): int
    {
        return $this->lockDurationMinutes;
    }
}
