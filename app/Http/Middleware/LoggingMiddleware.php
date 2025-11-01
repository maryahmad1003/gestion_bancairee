<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoggingMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->isMethod('post')) {
            Log::info('Création ressource', [
                'date' => now(),
                'host' => $request->getHost(),
                'operation' => $request->method(),
                'ressource' => $request->path(),
                'user' => $request->user()?->id
            ]);
        }

        return $response;
    }
}