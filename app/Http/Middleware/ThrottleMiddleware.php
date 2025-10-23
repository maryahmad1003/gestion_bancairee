<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ThrottleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->user() ? $request->user()->id : null;
        $ip = $request->ip();

        // Limitation par utilisateur (10 requêtes/jour)
        if ($userId) {
            $userKey = "throttle:user:{$userId}";
            $userRequests = Cache::get($userKey, 0);

            if ($userRequests >= 10) {
                return response()->json([
                    'error' => 'Limite de requêtes dépassée pour l\'utilisateur (10/jour)'
                ], 429);
            }

            Cache::put($userKey, $userRequests + 1, now()->endOfDay());
        }

        // Limitation par IP (100 requêtes/minute)
        $ipKey = "throttle:ip:{$ip}";
        $ipRequests = Cache::get($ipKey, 0);

        if ($ipRequests >= 100) {
            return response()->json([
                'error' => 'Limite de requêtes dépassée pour l\'IP (100/minute)'
            ], 429);
        }

        Cache::put($ipKey, $ipRequests + 1, now()->addMinute());

        return $next($request);
    }
}