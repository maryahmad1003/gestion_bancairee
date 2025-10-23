<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ComptesBancairesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Logique pour récupérer la liste des comptes bancaires
        return response()->json([
            'data' => [
                [
                    'id' => 1,
                    'numero' => 'FR1234567890123456789012345',
                    'solde' => 1500.50,
                    'devise' => 'EUR',
                    'client_id' => 1
                ]
            ],
            'message' => 'Liste des comptes bancaires récupérée avec succès'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation des données
        $validated = $request->validate([
            'numero' => 'required|string|unique:comptes_bancaires',
            'solde' => 'required|numeric|min:0',
            'devise' => 'required|string|size:3',
            'client_id' => 'required|integer|exists:clients,id'
        ]);

        // Logique pour créer un nouveau compte bancaire
        return response()->json([
            'data' => array_merge(['id' => 2], $validated),
            'message' => 'Compte bancaire créé avec succès'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Logique pour récupérer un compte bancaire spécifique
        return response()->json([
            'data' => [
                'id' => $id,
                'numero' => 'FR1234567890123456789012345',
                'solde' => 1500.50,
                'devise' => 'EUR',
                'client_id' => 1
            ],
            'message' => 'Compte bancaire récupéré avec succès'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validation des données
        $validated = $request->validate([
            'solde' => 'sometimes|numeric|min:0',
            'devise' => 'sometimes|string|size:3'
        ]);

        // Logique pour mettre à jour le compte bancaire
        return response()->json([
            'data' => [
                'id' => $id,
                'numero' => 'FR1234567890123456789012345',
                'solde' => $validated['solde'] ?? 1500.50,
                'devise' => $validated['devise'] ?? 'EUR',
                'client_id' => 1
            ],
            'message' => 'Compte bancaire mis à jour avec succès'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Logique pour supprimer le compte bancaire
        return response()->json([
            'message' => 'Compte bancaire supprimé avec succès'
        ]);
    }
}