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
     * @OA\Get(
     *     path="/api/v1/transactions",
     *     summary="Récupérer la liste des transactions",
     *     description="Permet de récupérer les transactions d'un client ou d'un admin. Les transactions peuvent être filtrées par date ou récupérées depuis l'archive cloud.",
     *     operationId="getTransactions",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="date_debut",
     *         in="query",
     *         description="Date de début pour le filtrage (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_fin",
     *         in="query",
     *         description="Date de fin pour le filtrage (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="compte_bancaire_id",
     *         in="query",
     *         description="ID du compte bancaire pour filtrer les transactions",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Transaction")),
     *             @OA\Property(property="pagination", ref="#/components/schemas/Pagination"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
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
     * @OA\Post(
     *     path="/api/v1/transactions",
     *     summary="Créer une nouvelle transaction",
     *     description="Crée une nouvelle transaction (dépôt, retrait ou virement). L'observer de transaction gère automatiquement les vérifications de solde et les mises à jour.",
     *     operationId="createTransaction",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"montant", "type_transaction", "libelle", "compte_bancaire_id"},
     *             @OA\Property(property="montant", type="number", format="float", example=100.50, description="Montant de la transaction"),
     *             @OA\Property(property="type_transaction", type="string", enum={"debit", "credit", "virement_emis", "virement_recu"}, example="debit", description="Type de transaction"),
     *             @OA\Property(property="libelle", type="string", example="Paiement facture EDF", description="Libellé de la transaction"),
     *             @OA\Property(property="description", type="string", example="Paiement de la facture d'électricité", description="Description détaillée"),
     *             @OA\Property(property="compte_bancaire_id", type="string", format="uuid", description="ID du compte bancaire source"),
     *             @OA\Property(property="compte_bancaire_destinataire_id", type="string", format="uuid", description="ID du compte bancaire destinataire (pour virements)"),
     *             @OA\Property(property="devise", type="string", example="EUR", description="Devise de la transaction")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction créée avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=403, description="Solde insuffisant ou accès non autorisé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
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
     * @OA\Get(
     *     path="/api/v1/transactions/{id}",
     *     summary="Récupérer une transaction spécifique",
     *     description="Permet de récupérer les informations détaillées d'une transaction spécifique.",
     *     operationId="getTransaction",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la transaction",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction récupérée avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Transaction non trouvée"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
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
     * @OA\Put(
     *     path="/api/v1/transactions/{id}",
     *     summary="Mettre à jour une transaction",
     *     description="Modifie les informations d'une transaction existante. Généralement limité à la description.",
     *     operationId="updateTransaction",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la transaction à modifier",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="description", type="string", example="Paiement facture EDF mise à jour", description="Nouvelle description de la transaction")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction mise à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction mise à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=404, description="Transaction non trouvée"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
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
     * @OA\Delete(
     *     path="/api/v1/transactions/{id}",
     *     summary="Supprimer une transaction",
     *     description="Supprime une transaction du système. Cette action est généralement réservée aux administrateurs.",
     *     operationId="deleteTransaction",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la transaction à supprimer",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction supprimée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction supprimée avec succès"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Transaction non trouvée"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function destroy(string $id)
    {
        // Logique pour supprimer la transaction
        return response()->json([
            'message' => 'Transaction supprimée avec succès'
        ]);
    }
}