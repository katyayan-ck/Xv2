<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class PermissionException extends Exception
{
    protected $code = 'E_PERMISSION';
    protected $httpStatus = 403;

    public function __construct(
        string $message = '',
        string $code = 'E_PERMISSION',
        int $httpStatus = 403,
        ?Exception $previous = null
    ) {
        $this->code = $code;
        $this->httpStatus = $httpStatus;
        parent::__construct($message, 0, $previous);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'http_status' => $this->httpStatus,
            'success' => false,
            'code' => $this->code,
            'message' => $this->message,
        ], $this->httpStatus);
    }
}
