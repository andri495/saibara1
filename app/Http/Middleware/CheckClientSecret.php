<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckClientSecret
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $secret = $request->header('X-Client-Secret') ?? $request->get('client_secret');

        if (! $secret || $secret !== config('app.api_client_secret')) {
            // // Log::warning('Client secret missing/invalid', [
            // //     'ip' => $request->ip(),
            // //     'path' => $request->path(),
            // //     'ua' => $request->userAgent(),
            // //     'header_secret' => $secret ? 'present' : 'missing',
            // // ]);
            // // Log::info('ClientSecret: '.$request->header('X-Client-Secret'));
            // $secret = $request->header('X-Client-Secret') ?? $request->get('client_secret');
            // $expected = env('API_CLIENT_SECRET');

            // Log::info('Secret Comparison Debug', [
            //     'received' => $secret,
            //     'expected' => $expected,
            //     'match' => $secret === $expected,
            //     'received_length' => strlen($secret ?? ''),
            //     'expected_length' => strlen($expected ?? ''),
            // ]);
            return response()->json(['message' => 'Unauthorized (client secret)'], 401);
        }

        return $next($request);
    }
}
