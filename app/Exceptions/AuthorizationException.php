<?php

namespace App\Exceptions;

use App\Enums\ErrorCodeEnum;

/**
 * AuthorizationException
 * 
 * Thrown for authorization/permission issues
 * (User lacks required permissions/roles)
 * 
 * HTTP Status: 403 (Forbidden)
 * 
 * @package App\Exceptions
 * @author VDMS Development Team
 * @version 2.0
 */
class AuthorizationException extends ApplicationException
{
    /**
     * Create authorization exception
     * 
     * @param string $action Action being performed
     * @param string|null $resource Resource being accessed
     * @param Exception|null $previous Previous exception
     */
    public function __construct(
        string $action = 'perform this action',
        ?string $resource = null,
        ?\Exception $previous = null
    ) {
        $message = "You are not authorized to {$action}";
        if ($resource) {
            $message .= " on {$resource}";
        }
        $message .= '.';

        parent::__construct(
            ErrorCodeEnum::AUTH_FORBIDDEN,
            $message,
            ['action' => $action, 'resource' => $resource],
            [],
            $previous
        );
    }
}
