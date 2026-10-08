<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Predis\Connection\ConnectionException;
use Illuminate\Redis\RedisException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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

    public function render($request, Throwable $exception)
    {
        // Handle Redis connection errors
        if ($exception instanceof ConnectionException || $exception instanceof RedisException) {
            \Log::error('Redis connection error: ' . $exception->getMessage());
            
            // Clear cache to prevent further errors
            try {
                \Artisan::call('cache:clear');
            } catch (\Exception $e) {
                \Log::error('Failed to clear cache: ' . $e->getMessage());
            }
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 503,
                    'message' => 'Service temporarily unavailable. Please try again.'
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }
            
            return back()
                ->with('error', 'Terjadi gangguan sementara. Silakan coba lagi.')
                ->with('toast_type', 'warning');
        }

        // Handle JWT exceptions
        if ($exception instanceof TokenExpiredException) {
            return response()->json([
                'status' => 401,
                'message' => 'The token has expired. Please refresh the token or log in again.'
            ], Response::HTTP_UNAUTHORIZED);
        } elseif ($exception instanceof TokenInvalidException) {
            return response()->json([
                'status' => 401,
                'message' => 'The token is invalid. Please log in again.'
            ], Response::HTTP_UNAUTHORIZED);
        } elseif ($exception instanceof JWTException) {
            // This will catch all other JWT exceptions including blacklisted tokens
            return response()->json([
                'status' => 401,
                'message' => $exception->getMessage() ?: 'Token error. Please log in again.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Handle ValidationException
        if ($exception instanceof ValidationException) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed.',
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Handle ModelNotFoundException
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'status' => 404,
                'message' => 'The requested resource was not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Handle database QueryException for web requests
        if ($exception instanceof QueryException && !$request->expectsJson()) {
            $errorCode = $exception->errorInfo[1] ?? null;

            // Duplicate entry (unique constraint violation)
            if ($errorCode == 1062) {
                $message = $this->parseDuplicateMessage($exception->getMessage());
                return back()
                    ->withInput()
                    ->with('error', $message)
                    ->with('toast_type', 'error');
            }

            // Foreign key constraint violation
            if ($errorCode == 1451 || $errorCode == 1452) {
                return back()
                    ->withInput()
                    ->with('error', 'Data tidak bisa dihapus karena masih digunakan oleh data lain.')
                    ->with('toast_type', 'warning');
            }

            // Generic DB error for web
            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan. Silakan coba lagi.')
                ->with('toast_type', 'error');
        }

        // For API requests, always return JSON
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 500,
                'message' => 'Internal server error',
                'error' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return parent::render($request, $exception);
    }

    private function parseDuplicateMessage(string $message): string
    {
        if (preg_match("/Duplicate entry '(.+?)' for key/", $message, $matches)) {
            $value = $matches[1];
            // Trim auto-generated composite key values to keep message short
            $display = mb_strlen($value) > 40 ? mb_substr($value, 0, 40) . '...' : $value;
            return "Data '{$display}' sudah ada. Silakan periksa kembali inputan Anda.";
        }

        return 'Data sudah ada dalam sistem. Silakan gunakan data yang berbeda.';
    }
}
