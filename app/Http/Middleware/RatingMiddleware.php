<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RatingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si la requête atteint la limite de taux
        if ($request->hasHeader('X-Rate-Limit-Remaining') && $request->header('X-Rate-Limit-Remaining') === '0') {
            // Enregistrer l'utilisateur qui a atteint la limite
            $user = $request->user();
            $ip = $request->ip();
            $userAgent = $request->userAgent();
            $endpoint = $request->path();

            Log::warning('Utilisateur a atteint la limite de taux', [
                'user_id' => $user ? $user->id : null,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'endpoint' => $endpoint,
                'timestamp' => now()->toISOString(),
            ]);

            // Vous pouvez également stocker ces informations dans une base de données
            // pour analyse ultérieure ou actions administratives
        }

        return $next($request);
    }
}