<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/users",
     *     summary="Récupérer la liste des utilisateurs",
     *     description="Permet à l'admin de récupérer tous les utilisateurs avec possibilité de filtrage par rôle et statut.",
     *     operationId="getUsers",
     *     tags={"Utilisateurs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filtrer par rôle (admin, client)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"admin", "client"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut (actif, inactif)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "inactif"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des utilisateurs récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")),
     *             @OA\Property(property="pagination", ref="#/components/schemas/Pagination"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès non autorisé - réservé aux administrateurs"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filtres optionnels
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $users = $query->paginate(15);

        return response()->json([
            'data' => $users,
            'message' => 'Liste des utilisateurs récupérée avec succès'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users",
     *     summary="Créer un nouvel utilisateur",
     *     description="Crée un nouvel utilisateur dans le système bancaire. Réservé aux administrateurs.",
     *     operationId="createUser",
     *     tags={"Utilisateurs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "role"},
     *             @OA\Property(property="name", type="string", example="Jean Dupont", description="Nom complet de l'utilisateur"),
     *             @OA\Property(property="email", type="string", format="email", example="jean.dupont@email.com", description="Email unique de l'utilisateur"),
     *             @OA\Property(property="password", type="string", format="password", example="password123", description="Mot de passe (sera hashé)"),
     *             @OA\Property(property="role", type="string", enum={"admin", "client"}, example="client", description="Rôle de l'utilisateur"),
     *             @OA\Property(property="statut", type="string", enum={"actif", "inactif"}, example="actif", description="Statut de l'utilisateur"),
     *             @OA\Property(property="client_id", type="string", format="uuid", description="ID du client associé (pour les utilisateurs clients)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Utilisateur créé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=422, description="Erreur de validation - email déjà utilisé"),
     *     @OA\Response(response=403, description="Accès non autorisé - réservé aux administrateurs"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->validated());

        return response()->json([
            'data' => $user,
            'message' => 'Utilisateur créé avec succès'
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/{user}",
     *     summary="Récupérer un utilisateur spécifique",
     *     description="Permet de récupérer les informations détaillées d'un utilisateur spécifique.",
     *     operationId="getUser",
     *     tags={"Utilisateurs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Utilisateur récupéré avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function show(User $user)
    {
        return response()->json([
            'data' => $user,
            'message' => 'Utilisateur récupéré avec succès'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user",
     *     summary="Récupérer l'utilisateur authentifié",
     *     description="Permet à l'utilisateur connecté de récupérer ses propres informations.",
     *     operationId="getAuthenticatedUser",
     *     tags={"Utilisateurs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations de l'utilisateur authentifié récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Utilisateur authentifié récupéré avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function showAuthenticated()
    {
        $user = auth()->user();

        return response()->json([
            'data' => $user,
            'message' => 'Utilisateur authentifié récupéré avec succès'
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/user",
     *     summary="Mettre à jour l'utilisateur authentifié",
     *     description="Permet à l'utilisateur connecté de modifier ses propres informations.",
     *     operationId="updateAuthenticatedUser",
     *     tags={"Utilisateurs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Jean Dupont", description="Nouveau nom complet"),
     *             @OA\Property(property="email", type="string", format="email", example="nouveau.email@email.com", description="Nouvel email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Utilisateur mis à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return response()->json([
            'data' => $user,
            'message' => 'Utilisateur mis à jour avec succès'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/users/{user}",
     *     summary="Supprimer un utilisateur",
     *     description="Supprime un utilisateur du système. Réservé aux administrateurs.",
     *     operationId="deleteUser",
     *     tags={"Utilisateurs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur à supprimer",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Utilisateur supprimé avec succès"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=403, description="Accès non autorisé - réservé aux administrateurs"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }
}
