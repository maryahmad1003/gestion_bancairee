<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Versionnement des API
Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    // Routes pour les comptes bancaires avec middleware personnalisé
    Route::middleware(['auth:sanctum', 'throttle:api', 'App\Http\Middleware\RatingMiddleware'])
        ->group(function () {
            /**
             * @OA\Get(
             *     path="/api/v1/comptes",
             *     summary="Récupérer la liste des comptes bancaires",
             *     description="Permet à l'admin de récupérer tous les comptes ou au client de récupérer ses comptes. Liste uniquement les comptes non supprimés de type chèque ou épargne actifs.",
             *     operationId="getComptes",
             *     tags={"Comptes Bancaires"},
             *     security={{"bearerAuth":{}}},
             *     @OA\Parameter(
             *         name="type_compte",
             *         in="query",
             *         description="Filtrer par type de compte (cheque, epargne)",
             *         required=false,
             *         @OA\Schema(type="string", enum={"cheque", "epargne"})
             *     ),
             *     @OA\Parameter(
             *         name="archive",
             *         in="query",
             *         description="Récupérer les comptes épargne archivés depuis le cloud",
             *         required=false,
             *         @OA\Schema(type="boolean")
             *     ),
             *     @OA\Parameter(
             *         name="telephone",
             *         in="query",
             *         description="Téléphone du client pour les comptes archivés",
             *         required=false,
             *         @OA\Schema(type="string")
             *     ),
             *     @OA\Response(
             *         response=200,
             *         description="Liste des comptes récupérée avec succès",
             *         @OA\JsonContent(
             *             @OA\Property(property="success", type="boolean", example=true),
             *             @OA\Property(property="message", type="string"),
             *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CompteBancaire")),
             *             @OA\Property(property="pagination", ref="#/components/schemas/Pagination"),
             *             @OA\Property(property="timestamp", type="string", format="date-time")
             *         )
             *     ),
             *     @OA\Response(response=403, description="Accès non autorisé"),
             *     @OA\Response(response=500, description="Erreur serveur")
             * )
             */
            /**
             * @OA\Post(
             *     path="/api/v1/comptes",
             *     summary="Créer un compte bancaire avec client",
             *     description="Crée un compte bancaire pour un client existant ou nouveau. Génère automatiquement numéro de compte, mot de passe et code. Envoie un email d'authentification et un SMS avec le code.",
             *     operationId="createCompteBancaire",
             *     tags={"Comptes Bancaires"},
             *     security={{"bearerAuth":{}}},
             *     @OA\RequestBody(
             *         required=true,
             *         @OA\JsonContent(
             *             required={"nom", "prenom", "email", "telephone", "date_naissance"},
             *             @OA\Property(property="nom", type="string", example="Dupont", description="Nom du client"),
             *             @OA\Property(property="prenom", type="string", example="Jean", description="Prénom du client"),
             *             @OA\Property(property="email", type="string", format="email", example="jean.dupont@email.com", description="Email unique du client"),
             *             @OA\Property(property="telephone", type="string", example="+221771234567", description="Numéro de téléphone sénégalais valide"),
             *             @OA\Property(property="date_naissance", type="string", format="date", example="1990-01-01", description="Date de naissance"),
             *             @OA\Property(property="adresse", type="string", example="123 Rue de la Paix", description="Adresse du client"),
             *             @OA\Property(property="ville", type="string", example="Paris", description="Ville"),
             *             @OA\Property(property="code_postal", type="string", example="75001", description="Code postal"),
             *             @OA\Property(property="pays", type="string", example="France", description="Pays"),
             *             @OA\Property(property="type_compte", type="string", enum={"courant", "epargne"}, example="courant", description="Type de compte"),
             *             @OA\Property(property="devise", type="string", example="EUR", description="Devise du compte"),
             *             @OA\Property(property="decouvert_autorise", type="number", format="float", example=500.00, description="Découvert autorisé")
             *         )
             *     ),
             *     @OA\Response(
             *         response=201,
             *         description="Compte bancaire créé avec succès",
             *         @OA\JsonContent(
             *             @OA\Property(property="success", type="boolean", example=true),
             *             @OA\Property(property="message", type="string", example="Compte bancaire créé avec succès. Notifications envoyées."),
             *             @OA\Property(property="data", ref="#/components/schemas/CompteBancaire"),
             *             @OA\Property(property="timestamp", type="string", format="date-time")
             *         )
             *     ),
             *     @OA\Response(response=400, description="Données invalides"),
             *     @OA\Response(response=422, description="Erreur de validation - numéro de téléphone invalide ou email déjà utilisé"),
             *     @OA\Response(response=500, description="Erreur serveur")
             * )
             */
            Route::get('comptes', [App\Http\Controllers\Api\V1\ComptesBancairesController::class, 'index']);
            Route::post('comptes', [App\Http\Controllers\Api\V1\ComptesBancairesController::class, 'store']);

            // Routes spécifiques pour le blocage des comptes épargne
            Route::post('comptes/{compte_bancaire}/bloquer', [App\Http\Controllers\Api\V1\ComptesBancairesController::class, 'bloquer']);
            Route::post('comptes/{compte_bancaire}/debloquer', [App\Http\Controllers\Api\V1\ComptesBancairesController::class, 'debloquer']);
        });

    // Routes pour les clients avec middleware personnalisé
    Route::middleware(['auth:sanctum', 'throttle:api', 'App\Http\Middleware\LoggingMiddleware'])
        ->group(function () {
            Route::patch('clients/{client}', [App\Http\Controllers\Api\V1\ClientsController::class, 'update']);
        });

    // Routes singleton pour les ressources
    Route::middleware(['auth:sanctum', 'throttle:api'])
        ->group(function () {
            // Routes singleton pour les utilisateurs
            Route::get('user', [App\Http\Controllers\Api\V1\UsersController::class, 'show'])->name('user.show');
            Route::patch('user', [App\Http\Controllers\Api\V1\UsersController::class, 'update'])->name('user.update');
        });

    // Exemples de routes suivant les conventions
    // Utiliser des noms au pluriel, minuscules avec tirets
    Route::apiResource('comptes-bancaires', 'App\Http\Controllers\Api\V1\ComptesBancairesController');
    Route::apiResource('transactions', 'App\Http\Controllers\Api\V1\TransactionsController');
    Route::apiResource('clients', 'App\Http\Controllers\Api\V1\ClientsController');
});
