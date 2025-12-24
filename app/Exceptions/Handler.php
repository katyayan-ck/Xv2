<?php

namespace App\Exceptions;

use App\Enums\ErrorCodeEnum;
use Illuminate\Auth\AuthenticationException as LaravelAuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // Handle all API requests uniformly (paths starting with 'api' or expects JSON)
        if (str_starts_with($request->path(), 'api') || $request->expectsJson()) {
            $status = $this->isHttpException($e) ? $e->getStatusCode() : 500;
            $code = ErrorCodeEnum::SYSTEM_ERROR->value;
            $message = $e->getMessage() ?: 'An unexpected error occurred';
            $data = null;
            $errors = [];

            // If it's an ApplicationException or subclass, use its toJsonResponse
            if ($e instanceof ApplicationException) {
                return $e->toJsonResponse();
            }

            // Map common Laravel/Symfony exceptions to uniform format
            if ($e instanceof LaravelAuthenticationException) {
                $status = 401;
                $code = ErrorCodeEnum::AUTH_UNAUTHORIZED->value;
                $message = 'Unauthorized';
            } elseif ($e instanceof RouteNotFoundException) {
                $status = 404;
                $code = ErrorCodeEnum::RESOURCE_NOT_FOUND->value;
                $message = 'Route not found';
            } elseif ($e instanceof ModelNotFoundException) {
                $status = 404;
                $code = ErrorCodeEnum::RESOURCE_NOT_FOUND->value;
                $message = 'Resource not found';
            } elseif ($e instanceof ValidationException) { // Laravel's built-in, if any
                $status = 422;
                $code = ErrorCodeEnum::VALIDATION_FAILED->value;
                $message = 'Validation failed';
                $errors = $e->errors();
            } elseif ($this->isHttpException($e)) {
                $code = 'HTTP_ERROR_' . $status;
            }

            // Build uniform response
            $response = [
                'http_status' => $status,
                'success' => false,
                'code' => $code,
                'message' => $message,
                'timestamp' => now()->toIso8601String(),
            ];

            // Add errors if present (e.g., validation)
            if (!empty($errors)) {
                $response['errors'] = $errors;
            }

            // Add debug info in local env
            if (app()->environment('local')) {
                $response['debug'] = [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ];
            }

            return response()->json($response, $status);
        }

        // For non-API requests, use default rendering (e.g., web error pages)
        return parent::render($request, $e);
    }

    /**
     * Handle unauthenticated requests (override to prevent redirects in API)
     *
     * @param Request $request
     * @param LaravelAuthenticationException $exception
     * @return JsonResponse
     */
    protected function unauthenticated($request, LaravelAuthenticationException $exception): JsonResponse
    {
        return response()->json([
            'http_status' => 401,
            'success' => false,
            'code' => ErrorCodeEnum::AUTH_UNAUTHORIZED->value,
            'message' => 'Unauthorized',
            'timestamp' => now()->toIso8601String(),
        ], 401);
    }
}
