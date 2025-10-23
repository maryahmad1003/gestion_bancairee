<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\ClientNotificationEvent;
use App\Exceptions\CompteBancaireException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompteBancaireRequest;
use App\Http\Requests\UpdateCompteBancaireRequest;
use App\Http\Resources\CompteBancaireResource;
use App\Models\Client;
use App\Models\CompteBancaire;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ComptesBancairesController extends Controller
{
    use ApiResponseTrait;

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
    public function index(Request $request)
    {
        $user = Auth::user();

        // Vérifier si l'utilisateur est authentifié
        if (!$user) {
            throw CompteBancaireException::accesNonAutorise();
        }

        $query = CompteBancaire::with('client:id,nom,prenom,numero_client,telephone');

        // Logique d'accès basée sur le rôle
        // Pour simplifier, on suppose que l'admin a un champ 'is_admin' ou on utilise un middleware
        // Ici on utilise une vérification simple basée sur l'existence d'un champ ou une logique métier
        if ($user->email === 'admin@banque.example.com' || isset($user->is_admin) && $user->is_admin) {
            // Admin peut voir tous les comptes
            // Le scope global filtre déjà les comptes non supprimés
        } else {
            // Client ne peut voir que ses propres comptes
            // Supposons que l'utilisateur a une relation avec le client ou un champ client_id
            $clientId = $user->client_id ?? $user->id; // Adapter selon votre modèle User
            $query->where('client_id', $clientId);
        }

        // Filtres spécifiques selon les exigences
        $query->whereIn('type_compte', ['cheque', 'epargne'])
              ->where('statut', 'actif');

        // Filtres optionnels
        if ($request->has('type_compte') && in_array($request->type_compte, ['cheque', 'epargne'])) {
            $query->where('type_compte', $request->type_compte);
        }

        // Gestion des comptes épargne archivés depuis le cloud
        if ($request->type_compte === 'epargne' && $request->has('archive')) {
            return $this->getArchivedSavingsAccounts($request);
        }

        $comptes = $query->paginate(15);

        return $this->paginatedResponse(
            CompteBancaireResource::collection($comptes),
            'Liste des comptes bancaires récupérée avec succès'
        );
    }

    /**
     * Récupérer les comptes épargne archivés depuis le cloud
     */
    private function getArchivedSavingsAccounts(Request $request)
    {
        $telephone = $request->input('telephone');

        try {
            // Simulation d'appel à un service cloud
            // En production, remplacer par un vrai appel API
            $response = Http::timeout(30)->get('https://cloud-storage.banque.example.com/api/archived-savings-accounts', [
                'client_telephone' => $telephone,
                'api_key' => config('services.cloud_api_key'),
            ]);

            if ($response->successful()) {
                $archivedAccounts = $response->json()['data'] ?? [];

                // Transformer les données du cloud en format compatible
                $formattedAccounts = collect($archivedAccounts)->map(function ($account) use ($telephone) {
                    return [
                        'id' => $account['id'] ?? null,
                        'numero_compte' => $account['numero_compte'] ?? '',
                        'type_compte' => 'epargne',
                        'devise' => $account['devise'] ?? 'EUR',
                        'solde' => $account['solde'] ?? 0,
                        'solde_formate' => number_format($account['solde'] ?? 0, 2, ',', ' ') . ' ' . ($account['devise'] ?? 'EUR'),
                        'decouvert_autorise' => 0,
                        'date_ouverture' => $account['date_ouverture'] ?? null,
                        'statut' => 'archive',
                        'peut_debiter' => false,
                        'client' => [
                            'id' => $account['client_id'] ?? null,
                            'numero_client' => $account['numero_client'] ?? '',
                            'nom_complet' => $account['client_nom'] ?? '',
                            'telephone' => $telephone,
                        ],
                        'created_at' => $account['created_at'] ?? null,
                        'updated_at' => $account['updated_at'] ?? null,
                    ];
                });

                return $this->successResponse(
                    $formattedAccounts,
                    'Comptes épargne archivés récupérés depuis le cloud avec succès'
                );
            } else {
                throw CompteBancaireException::compteNonTrouve();
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des comptes épargne archivés', [
                'error' => $e->getMessage(),
                'telephone' => $telephone,
            ]);

            throw new CompteBancaireException('Erreur lors de la récupération des comptes épargne archivés.', 500);
        }
    }

    /**
     * Créer un nouveau compte bancaire avec client si nécessaire
     *
     * @OA\Post(
     *     path="/api/v1/comptes",
     *     summary="Créer un compte bancaire",
     *     description="Crée un compte bancaire pour un client existant ou nouveau. Génère automatiquement numéro de compte, mot de passe et code.",
     *     operationId="createCompte",
     *     tags={"Comptes Bancaires"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom", "prenom", "email", "telephone", "date_naissance"},
     *             @OA\Property(property="nom", type="string", example="Dupont"),
     *             @OA\Property(property="prenom", type="string", example="Jean"),
     *             @OA\Property(property="email", type="string", format="email", example="jean.dupont@email.com"),
     *             @OA\Property(property="telephone", type="string", example="+33123456789"),
     *             @OA\Property(property="date_naissance", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="adresse", type="string", example="123 Rue de la Paix"),
     *             @OA\Property(property="ville", type="string", example="Paris"),
     *             @OA\Property(property="code_postal", type="string", example="75001"),
     *             @OA\Property(property="pays", type="string", example="France"),
     *             @OA\Property(property="type_compte", type="string", enum={"courant", "epargne"}, example="courant"),
     *             @OA\Property(property="devise", type="string", example="EUR"),
     *             @OA\Property(property="decouvert_autorise", type="number", format="float", example=500.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte bancaire créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/CompteBancaire"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function store(StoreCompteBancaireRequest $request)
    {
        DB::beginTransaction();

        try {
            // Vérifier si le client existe déjà
            $client = Client::where('email', $request->email)
                          ->orWhere('telephone', $request->telephone)
                          ->first();

            if (!$client) {
                // Créer le client avec génération automatique du numéro client
                $client = Client::create([
                    'nom' => $request->nom,
                    'prenom' => $request->prenom,
                    'email' => $request->email,
                    'telephone' => $request->telephone,
                    'date_naissance' => $request->date_naissance,
                    'adresse' => $request->adresse,
                    'ville' => $request->ville,
                    'code_postal' => $request->code_postal,
                    'pays' => $request->pays ?? 'France',
                ]);

                // Générer mot de passe et code pour le nouveau client
                $password = $this->generatePassword();
                $code = $this->generateCode();

                // Ici on pourrait stocker le mot de passe hashé et le code quelque part
                // Pour cet exemple, on les utilise directement pour les notifications
            } else {
                // Client existant - générer nouveau mot de passe et code
                $password = $this->generatePassword();
                $code = $this->generateCode();
            }

            // Créer le compte bancaire
            $compte = CompteBancaire::create([
                'client_id' => $client->id,
                'type_compte' => $request->type_compte ?? 'courant',
                'devise' => $request->devise ?? 'EUR',
                'decouvert_autorise' => $request->decouvert_autorise ?? 0,
                'date_ouverture' => now()->toDateString(),
            ]);

            // Déclencher l'événement de notification
            event(new ClientNotificationEvent($client, $compte, $password, $code));

            DB::commit();

            return $this->successResponse(
                new CompteBancaireResource($compte->load('client')),
                'Compte bancaire créé avec succès. Notifications envoyées.',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du compte bancaire', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            throw new CompteBancaireException('Erreur lors de la création du compte bancaire.', 500);
        }
    }

    /**
     * Générer un mot de passe sécurisé
     */
    private function generatePassword(): string
    {
        return Str::random(12) . rand(100, 999);
    }

    /**
     * Générer un code à 6 chiffres
     */
    private function generateCode(): string
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/{compte_bancaire}",
     *     summary="Récupérer un compte bancaire spécifique",
     *     description="Permet de récupérer les informations détaillées d'un compte bancaire spécifique.",
     *     operationId="getCompteBancaire",
     *     tags={"Comptes Bancaires"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte_bancaire",
     *         in="path",
     *         required=true,
     *         description="ID du compte bancaire",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bancaire récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bancaire récupéré avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/CompteBancaire"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function show(CompteBancaire $compte_bancaire)
    {
        return response()->json([
            'data' => $compte_bancaire->load('client:id,nom,prenom,numero_client'),
            'message' => 'Compte bancaire récupéré avec succès'
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/comptes/{compte_bancaire}",
     *     summary="Mettre à jour un compte bancaire",
     *     description="Modifie les informations d'un compte bancaire existant. Tous les champs sont optionnels.",
     *     operationId="updateCompteBancaire",
     *     tags={"Comptes Bancaires"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte_bancaire",
     *         in="path",
     *         required=true,
     *         description="ID du compte bancaire à modifier",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type_compte", type="string", enum={"cheque", "epargne"}, example="cheque", description="Type de compte"),
     *             @OA\Property(property="devise", type="string", example="EUR", description="Devise du compte"),
     *             @OA\Property(property="decouvert_autorise", type="number", format="float", example=500.00, description="Découvert autorisé"),
     *             @OA\Property(property="statut", type="string", enum={"actif", "inactif", "bloque"}, example="actif", description="Statut du compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bancaire mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bancaire mis à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/CompteBancaire"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function update(UpdateCompteBancaireRequest $request, CompteBancaire $compte_bancaire)
    {
        $compte_bancaire->update($request->validated());

        return response()->json([
            'data' => $compte_bancaire->load('client:id,nom,prenom,numero_client'),
            'message' => 'Compte bancaire mis à jour avec succès'
        ]);
    }

    /**
     * Bloquer un compte épargne
     *
     * @OA\Post(
     *     path="/api/v1/comptes/{compte_bancaire}/bloquer",
     *     summary="Bloquer un compte épargne",
     *     description="Bloque un compte épargne actif pour une durée déterminée. Seuls les comptes épargne actifs peuvent être bloqués.",
     *     operationId="bloquerCompteEpargne",
     *     tags={"Comptes Bancaires"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte_bancaire",
     *         in="path",
     *         required=true,
     *         description="ID du compte bancaire",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"duree_jours"},
     *             @OA\Property(property="duree_jours", type="integer", example=30, description="Nombre de jours de blocage", minimum=1),
     *             @OA\Property(property="motif", type="string", example="Blocage pour vérification", description="Motif du blocage (optionnel)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/CompteBancaire"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides ou compte ne peut pas être bloqué"),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function bloquer(Request $request, CompteBancaire $compte_bancaire)
    {
        // Validation des données
        $validated = $request->validate([
            'duree_jours' => 'required|integer|min:1',
            'motif' => 'nullable|string|max:255',
        ]);

        // Vérifier que c'est un compte épargne actif
        if ($compte_bancaire->type_compte !== 'epargne') {
            return $this->errorResponse('Seuls les comptes épargne peuvent être bloqués.', 400);
        }

        if ($compte_bancaire->statut !== 'actif') {
            return $this->errorResponse('Le compte doit être actif pour être bloqué.', 400);
        }

        if ($compte_bancaire->est_bloque) {
            return $this->errorResponse('Le compte est déjà bloqué.', 400);
        }

        try {
            $result = $compte_bancaire->bloquer($validated['duree_jours'], $validated['motif']);

            if ($result) {
                Log::info('Compte épargne bloqué via API', [
                    'numero_compte' => $compte_bancaire->numero_compte,
                    'duree' => $validated['duree_jours'],
                    'motif' => $validated['motif'],
                    'date_fin' => $compte_bancaire->date_fin_blocage,
                ]);

                return $this->successResponse(
                    new CompteBancaireResource($compte_bancaire->load('client')),
                    'Compte épargne bloqué avec succès'
                );
            } else {
                return $this->errorResponse('Échec du blocage du compte.', 500);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors du blocage du compte épargne via API', [
                'numero_compte' => $compte_bancaire->numero_compte,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Erreur lors du blocage du compte.', 500);
        }
    }

    /**
     * Débloquer un compte épargne
     *
     * @OA\Post(
     *     path="/api/v1/comptes/{compte_bancaire}/debloquer",
     *     summary="Débloquer un compte épargne",
     *     description="Débloque un compte épargne qui était bloqué.",
     *     operationId="debloquerCompteEpargne",
     *     tags={"Comptes Bancaires"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte_bancaire",
     *         in="path",
     *         required=true,
     *         description="ID du compte bancaire",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte débloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/CompteBancaire"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Le compte n'est pas bloqué"),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function debloquer(CompteBancaire $compte_bancaire)
    {
        if (!$compte_bancaire->est_bloque) {
            return $this->errorResponse('Le compte n\'est pas bloqué.', 400);
        }

        try {
            $result = $compte_bancaire->debloquer();

            if ($result) {
                Log::info('Compte épargne débloqué via API', [
                    'numero_compte' => $compte_bancaire->numero_compte,
                ]);

                return $this->successResponse(
                    new CompteBancaireResource($compte_bancaire->load('client')),
                    'Compte épargne débloqué avec succès'
                );
            } else {
                return $this->errorResponse('Échec du déblocage du compte.', 500);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors du déblocage du compte épargne via API', [
                'numero_compte' => $compte_bancaire->numero_compte,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Erreur lors du déblocage du compte.', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/comptes/{compte_bancaire}",
     *     summary="Supprimer un compte bancaire",
     *     description="Supprime un compte bancaire du système. Cette action est généralement effectuée en soft delete.",
     *     operationId="deleteCompteBancaire",
     *     tags={"Comptes Bancaires"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte_bancaire",
     *         in="path",
     *         required=true,
     *         description="ID du compte bancaire à supprimer",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bancaire supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bancaire supprimé avec succès"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=400, description="Impossible de supprimer le compte (solde non nul)"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function destroy(CompteBancaire $compte_bancaire)
    {
        $compte_bancaire->delete();

        return response()->json([
            'message' => 'Compte bancaire supprimé avec succès'
        ]);
    }
}