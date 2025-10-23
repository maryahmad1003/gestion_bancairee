<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Valider les en-têtes requis
        $requiredHeaders = [
            'Authorization' => 'Bearer {jwt_token}',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Language' => 'fr-FR',
            'X-Request-ID' => 'unique-request-id',
            'X-API-Version' => 'v1'
        ];

        foreach ($requiredHeaders as $header => $expected) {
            if (!$request->hasHeader($header)) {
                return response()->json([
                    'error' => "En-tête requis manquant: {$header}",
                    'expected' => $expected
                ], 400);
            }
        }

        // Ajouter les en-têtes de réponse
        $response = $next($request);

        $response->headers->set('Access-Control-Allow-Origin', 'http://front.banque.example.com');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PATCH, DELETE');
        $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type');
        $response->headers->set('X-API-Version', 'v1');

        return $response;
    }
}