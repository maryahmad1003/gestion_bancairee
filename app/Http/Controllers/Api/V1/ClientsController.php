<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class ClientsController extends Controller
{
    use ApiResponseTrait;
    /**
     * @OA\Get(
     *     path="/api/v1/clients",
     *     summary="Récupérer la liste des clients",
     *     description="Permet à l'admin de récupérer tous les clients ou au client de récupérer ses propres informations. Liste uniquement les clients actifs non supprimés.",
     *     operationId="getClients",
     *     tags={"Clients"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut (actif, inactif, suspendu)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "inactif", "suspendu"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des clients récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Client")),
     *             @OA\Property(property="pagination", ref="#/components/schemas/Pagination"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function index()
    {
        // Logique pour récupérer la liste des clients
        return response()->json([
            'data' => [
                [
                    'id' => 1,
                    'nom' => 'Dupont',
                    'prenom' => 'Jean',
                    'email' => 'jean.dupont@example.com',
                    'telephone' => '+33123456789',
                    'date_naissance' => '1980-01-15'
                ]
            ],
            'message' => 'Liste des clients récupérée avec succès'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/clients",
     *     summary="Créer un nouveau client",
     *     description="Crée un nouveau client dans le système bancaire. Tous les champs sont requis pour la création.",
     *     operationId="createClient",
     *     tags={"Clients"},
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
     *             @OA\Property(property="numero_client", type="string", example="CLI001", description="Numéro client généré automatiquement")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Client créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client créé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Client"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=422, description="Erreur de validation - email ou téléphone déjà utilisé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function store(\App\Http\Requests\StoreClientRequest $request)
    {
        // Les données sont déjà validées par StoreClientRequest
        $validated = $request->validated();

        $client = \App\Models\Client::create($validated);

        return response()->json([
            'data' => $client,
            'message' => 'Client créé avec succès'
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/clients/{id}",
     *     summary="Récupérer un client spécifique",
     *     description="Permet de récupérer les informations détaillées d'un client spécifique.",
     *     operationId="getClient",
     *     tags={"Clients"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client à récupérer",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client récupéré avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Client"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Client non trouvé"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function show(string $id)
    {
        // Logique pour récupérer un client spécifique
        return response()->json([
            'data' => [
                'id' => $id,
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'email' => 'jean.dupont@example.com',
                'telephone' => '+33123456789',
                'date_naissance' => '1980-01-15'
            ],
            'message' => 'Client récupéré avec succès'
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/v1/clients/{id}",
     *     summary="Modifier un client",
     *     description="Modifie les informations d'un client existant. Tous les champs sont optionnels mais au moins un champ doit être fourni.",
     *     operationId="updateClient",
     *     tags={"Clients"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client à modifier",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nom", type="string", example="Dupont", description="Nom du client"),
     *             @OA\Property(property="prenom", type="string", example="Jean", description="Prénom du client"),
     *             @OA\Property(property="email", type="string", format="email", example="jean.dupont@email.com", description="Email unique du client"),
     *             @OA\Property(property="telephone", type="string", example="+221771234567", description="Numéro de téléphone sénégalais valide"),
     *             @OA\Property(property="date_naissance", type="string", format="date", example="1990-01-01", description="Date de naissance"),
     *             @OA\Property(property="adresse", type="string", example="123 Rue de la Paix", description="Adresse du client"),
     *             @OA\Property(property="ville", type="string", example="Paris", description="Ville"),
     *             @OA\Property(property="code_postal", type="string", example="75001", description="Code postal"),
     *             @OA\Property(property="pays", type="string", example="France", description="Pays"),
     *             @OA\Property(property="statut", type="string", enum={"actif", "inactif", "suspendu"}, example="actif", description="Statut du client")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client modifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client modifié avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Client"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides ou aucun champ fourni"),
     *     @OA\Response(response=404, description="Client non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        // Les données sont déjà validées par UpdateClientRequest
        $validated = $request->validated();

        $client->update($validated);

        return $this->successResponse(
            $client->fresh(),
            'Client modifié avec succès'
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/clients/{id}",
     *     summary="Supprimer un client",
     *     description="Supprime un client du système. Cette action est généralement effectuée en soft delete.",
     *     operationId="deleteClient",
     *     tags={"Clients"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client à supprimer",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client supprimé avec succès"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Client non trouvé"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function destroy(string $id)
    {
        // Logique pour supprimer le client
        return response()->json([
            'message' => 'Client supprimé avec succès'
        ]);
    }
}