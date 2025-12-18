<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ValidationException extends Exception
{
    protected $code = 'E_VALIDATION';
    protected $httpStatus = 422;
    protected $errors = [];

    public function __construct(
        string $message = '',
        array $errors = [],
        string $code = 'E_VALIDATION',
        int $httpStatus = 422
    ) {
        $this->code = $code;
        $this->httpStatus = $httpStatus;
        $this->errors = $errors;
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'http_status' => $this->httpStatus,
            'success' => false,
            'code' => $this->code,
            'message' => $this->message,
            'errors' => $this->errors,
        ], $this->httpStatus);
    }
}
