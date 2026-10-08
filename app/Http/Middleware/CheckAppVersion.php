<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckAppVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $minVersion = (int) env('MIN_APP_VERSION', 27);
        $clientVersion = $request->header('X-App-Version');

        if (! $clientVersion || (int) $clientVersion < $minVersion) {
            // Log::info('Blocked outdated app version', [
            //     'ip' => $request->ip(),
            //     'path' => $request->path(),
            //     'ua' => $request->userAgent(),
            //     'client_version' => $clientVersion,
            //     'min_version' => $minVersion,
            // ]);
            // Log::info('AppVersion: '.$request->header('X-App-Version'));

            return response()->json(['message' => 'App version outdated. Please update.'], 426);
        }

        return $next($request);
    }
}
