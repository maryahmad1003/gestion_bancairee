<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClientsController extends Controller
{
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
    public function store(Request $request)
    {
        // Validation des données
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:clients',
            'telephone' => 'required|string|max:20',
            'date_naissance' => 'required|date|before:today'
        ]);

        // Logique pour créer un nouveau client
        return response()->json([
            'data' => array_merge(['id' => 2], $validated),
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
     */
    public function update(Request $request, string $id)
    {
        // Validation des données
        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:clients,email,' . $id,
            'telephone' => 'sometimes|string|max:20',
            'date_naissance' => 'sometimes|date|before:today'
        ]);

        // Logique pour mettre à jour le client
        return response()->json([
            'data' => array_merge([
                'id' => $id,
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'email' => 'jean.dupont@example.com',
                'telephone' => '+33123456789',
                'date_naissance' => '1980-01-15'
            ], $validated),
            'message' => 'Client mis à jour avec succès'
        ]);
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