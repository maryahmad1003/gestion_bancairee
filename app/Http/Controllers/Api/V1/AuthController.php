<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authentifier un utilisateur et retourner les tokens
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
        ]);

        // Vérifier les credentials
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Les informations d\'identification sont incorrectes.'],
            ]);
        }

        $user = Auth::user();

        // Vérifier si l'utilisateur est actif
        if ($user->statut !== 'actif') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Votre compte n\'est pas actif.'],
            ]);
        }

        // Créer le token avec les scopes appropriés
        $token = $user->createToken('API Token', $user->permissions);

        // Ajouter les claims personnalisés
        $token->token->forceFill([
            'role' => $user->role,
            'permissions' => $user->permissions,
        ])->save();

        // Créer la réponse avec le cookie
        $response = response()->json([
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => $user->permissions,
            ],
            'access_token' => $token->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->token->expires_at,
        ]);

        // Stocker le token dans un cookie sécurisé
        $response->cookie(
            'access_token',
            $token->accessToken,
            60 * 24 * 7, // 7 jours
            '/',
            null,
            true, // secure
            true  // httpOnly
        );

        return $response;
    }

    /**
     * Rafraîchir le token d'accès
     */
    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $user = $request->user();

        // Révoquer l'ancien token
        $request->user()->token()->revoke();

        // Créer un nouveau token
        $token = $user->createToken('API Token', $user->permissions);

        // Ajouter les claims personnalisés
        $token->token->forceFill([
            'role' => $user->role,
            'permissions' => $user->permissions,
        ])->save();

        return response()->json([
            'message' => 'Token rafraîchi avec succès',
            'access_token' => $token->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->token->expires_at,
        ]);
    }

    /**
     * Déconnecter l'utilisateur et invalider les tokens
     */
    public function logout(Request $request)
    {
        // Révoquer le token actuel
        $request->user()->token()->revoke();

        // Supprimer le cookie
        $response = response()->json([
            'message' => 'Déconnexion réussie'
        ]);

        $response->cookie(Cookie::forget('access_token'));

        return $response;
    }

    /**
     * Obtenir les informations de l'utilisateur connecté
     */
    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('authenticatable'),
        ]);
    }
}