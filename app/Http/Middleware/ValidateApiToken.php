<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiToken = $request->header('X-API-TOKEN');
        $expectedToken = env('EXTERNAL_API_TOKEN', 'default-static-token-change-me');

        if (! $apiToken || $apiToken !== $expectedToken) {
            Log::info('Invalid or missing API token', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'ua' => $request->userAgent(),
                'provided_token' => $apiToken ? '[REDACTED]' : 'MISSING',
            ]);

            return response()->json([
                'message' => 'Invalid or missing API token',
            ], 401);
        }

        return $next($request);
    }
}
