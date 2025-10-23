<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log de la requête entrante
        $this->logRequest($request);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // en millisecondes

        // Log de la réponse
        $this->logResponse($request, $response, $duration);

        return $response;
    }

    /**
     * Logger les informations de la requête
     */
    private function logRequest(Request $request): void
    {
        $logData = [
            'timestamp' => now()->toISOString(),
            'host' => $request->getHost(),
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'operation' => $this->getOperationName($request),
            'resource' => $this->getResourceName($request),
            'user_id' => auth()->id() ?? 'guest',
        ];

        Log::channel('operations')->info('API Request', $logData);
    }

    /**
     * Logger les informations de la réponse
     */
    private function logResponse(Request $request, Response $response, float $duration): void
    {
        $logData = [
            'timestamp' => now()->toISOString(),
            'host' => $request->getHost(),
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'operation' => $this->getOperationName($request),
            'resource' => $this->getResourceName($request),
            'user_id' => auth()->id() ?? 'guest',
            'response_size' => strlen($response->getContent()),
        ];

        $level = $response->isSuccessful() ? 'info' : 'warning';
        Log::channel('operations')->$level('API Response', $logData);
    }

    /**
     * Déterminer le nom de l'opération
     */
    private function getOperationName(Request $request): string
    {
        $method = $request->getMethod();
        $uri = $request->getRequestUri();

        // Extraire le nom de l'opération basé sur la méthode et l'URI
        if (str_contains($uri, '/comptes')) {
            switch ($method) {
                case 'GET':
                    return str_contains($uri, '/comptes/') ? 'CONSULTER_COMPTE' : 'LISTER_COMPTES';
                case 'POST':
                    return 'CREER_COMPTE';
                case 'PUT':
                case 'PATCH':
                    return 'MODIFIER_COMPTE';
                case 'DELETE':
                    return 'SUPPRIMER_COMPTE';
            }
        }

        if (str_contains($uri, '/clients')) {
            switch ($method) {
                case 'GET':
                    return str_contains($uri, '/clients/') ? 'CONSULTER_CLIENT' : 'LISTER_CLIENTS';
                case 'POST':
                    return 'CREER_CLIENT';
                case 'PUT':
                case 'PATCH':
                    return 'MODIFIER_CLIENT';
                case 'DELETE':
                    return 'SUPPRIMER_CLIENT';
            }
        }

        if (str_contains($uri, '/transactions')) {
            switch ($method) {
                case 'GET':
                    return str_contains($uri, '/transactions/') ? 'CONSULTER_TRANSACTION' : 'LISTER_TRANSACTIONS';
                case 'POST':
                    return 'CREER_TRANSACTION';
            }
        }

        return 'OPERATION_INCONNUE';
    }

    /**
     * Déterminer le nom de la ressource
     */
    private function getResourceName(Request $request): string
    {
        $uri = $request->getRequestUri();

        if (str_contains($uri, '/comptes')) {
            return 'COMPTE_BANCAIRE';
        }

        if (str_contains($uri, '/clients')) {
            return 'CLIENT';
        }

        if (str_contains($uri, '/transactions')) {
            return 'TRANSACTION';
        }

        return 'RESSOURCE_INCONNUE';
    }
}
