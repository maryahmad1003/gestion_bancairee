<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Contracts\DatabaseServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionsController extends Controller
{
    protected DatabaseServiceInterface $databaseService;

    public function __construct(DatabaseServiceInterface $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    /**
     * Récupérer la liste des transactions (sans annotations Swagger)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Transaction::query();

        // Filtres
        if ($request->has('date_debut') && $request->has('date_fin')) {
            $dateDebut = $request->date_debut;
            $dateFin = $request->date_fin;

            // Si c'est aujourd'hui, chercher dans la base locale
            if ($dateDebut === today()->toDateString() && $dateFin === today()->toDateString()) {
                $query->whereDate('date_transaction', today())
                      ->where('est_archive', false);
            } else {
                // Sinon, chercher dans Neon (sans couplage fort)
                $transactions = $this->databaseService->retrieve('transactions_archivees', [
                    'date_transaction' => [$dateDebut, $dateFin],
                    'compte_bancaire_id' => $user->authenticatable->comptesBancaires->pluck('id')->toArray()
                ]);

                return response()->json([
                    'data' => $transactions,
                    'message' => 'Transactions historiques récupérées avec succès'
                ]);
            }
        }

        // Filtrage par compte si l'utilisateur est un client
        if ($user->authenticatable_type === 'App\Models\Client') {
            $compteIds = $user->authenticatable->comptesBancaires->pluck('id');
            $query->whereIn('compte_bancaire_id', $compteIds);
        }

        $transactions = $query->with('compteBancaire')->paginate(15);

        return response()->json([
            'data' => $transactions,
            'message' => 'Liste des transactions récupérée avec succès'
        ]);
    }

    /**
     * Créer une nouvelle transaction (sans annotations Swagger)
     */
    public function store(Request $request)
    {
        // Validation des données
        $validated = $request->validate([
            'montant' => 'required|numeric|min:0.01',
            'type_transaction' => 'required|in:debit,credit,virement_emis',
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'compte_bancaire_id' => 'required|exists:comptes_bancaires,id',
            'compte_bancaire_destinataire_id' => 'nullable|exists:comptes_bancaires,id',
            'devise' => 'nullable|string|size:3|default:EUR',
        ]);

        // Créer la transaction (l'observer gérera les vérifications)
        $transaction = Transaction::create(array_merge($validated, [
            'date_transaction' => now(),
            'statut' => 'validee', // L'observer peut changer cela si nécessaire
        ]));

        return response()->json([
            'data' => $transaction->load('compteBancaire'),
            'message' => 'Transaction créée avec succès'
        ], 201);
    }

    /**
     * Récupérer une transaction spécifique (sans annotations Swagger)
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
     * Mettre à jour une transaction (sans annotations Swagger)
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
     * Supprimer une transaction (sans annotations Swagger)
     */
    public function destroy(string $id)
    {
        // Logique pour supprimer la transaction
        return response()->json([
            'message' => 'Transaction supprimée avec succès'
        ]);
    }
}