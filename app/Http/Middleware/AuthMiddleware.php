<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    use ApiResponseTrait;

    /**
     * Handle an incoming request.
     *
     * Vérifie si l'utilisateur est connecté via Passport
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est authentifié
        if (!Auth::guard('api')->check()) {
            return $this->errorResponse(
                'Authentification requise. Token d\'accès manquant ou invalide.',
                401
            );
        }

        // Vérifier si l'utilisateur est actif
        $user = Auth::guard('api')->user();
        if ($user->statut !== 'actif') {
            return $this->errorResponse(
                'Votre compte est ' . $user->statut . '. Contactez l\'administrateur.',
                403
            );
        }

        // Ajouter l'utilisateur à la requête pour un accès facile
        $request->merge(['current_user' => $user]);

        return $next($request);
    }
}
