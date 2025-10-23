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
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
     * Display the specified resource.
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Logique pour supprimer le client
        return response()->json([
            'message' => 'Client supprimé avec succès'
        ]);
    }
}