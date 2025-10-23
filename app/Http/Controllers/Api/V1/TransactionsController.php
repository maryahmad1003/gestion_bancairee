<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Logique pour récupérer la liste des transactions
        return response()->json([
            'data' => [
                [
                    'id' => 1,
                    'montant' => 100.00,
                    'type' => 'debit',
                    'description' => 'Paiement facture',
                    'compte_bancaire_id' => 1,
                    'date_transaction' => '2023-10-23'
                ]
            ],
            'message' => 'Liste des transactions récupérée avec succès'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation des données
        $validated = $request->validate([
            'montant' => 'required|numeric|min:0.01',
            'type' => 'required|in:debit,credit',
            'description' => 'required|string|max:255',
            'compte_bancaire_id' => 'required|integer|exists:comptes_bancaires,id'
        ]);

        // Logique pour créer une nouvelle transaction
        return response()->json([
            'data' => array_merge(['id' => 2, 'date_transaction' => now()->toDateString()], $validated),
            'message' => 'Transaction créée avec succès'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Logique pour récupérer une transaction spécifique
        return response()->json([
            'data' => [
                'id' => $id,
                'montant' => 100.00,
                'type' => 'debit',
                'description' => 'Paiement facture',
                'compte_bancaire_id' => 1,
                'date_transaction' => '2023-10-23'
            ],
            'message' => 'Transaction récupérée avec succès'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validation des données
        $validated = $request->validate([
            'description' => 'sometimes|string|max:255'
        ]);

        // Logique pour mettre à jour la transaction
        return response()->json([
            'data' => [
                'id' => $id,
                'montant' => 100.00,
                'type' => 'debit',
                'description' => $validated['description'] ?? 'Paiement facture',
                'compte_bancaire_id' => 1,
                'date_transaction' => '2023-10-23'
            ],
            'message' => 'Transaction mise à jour avec succès'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Logique pour supprimer la transaction
        return response()->json([
            'message' => 'Transaction supprimée avec succès'
        ]);
    }
}