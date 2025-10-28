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
     * Récupérer la liste des clients (sans annotations Swagger)
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
     * Créer un nouveau client (sans annotations Swagger)
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
     * Récupérer un client spécifique (sans annotations Swagger)
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
     * Modifier un client (sans annotations Swagger)
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
     * Supprimer un client (sans annotations Swagger)
     */
    public function destroy(string $id)
    {
        // Logique pour supprimer le client
        return response()->json([
            'message' => 'Client supprimé avec succès'
        ]);
    }
}