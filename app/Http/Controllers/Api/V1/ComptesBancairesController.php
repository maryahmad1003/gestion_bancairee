<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Events\ClientNotificationEvent;
use App\Exceptions\CompteBancaireException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompteBancaireRequest;
use App\Http\Requests\UpdateCompteBancaireRequest;
use App\Http\Resources\CompteBancaireResource;
use App\Models\Client;
use App\Models\CompteBancaire;
use App\Models\User;
use App\Rules\ValidNciAndTelephone;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ComptesBancairesController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/comptes",
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
        // Vérifier l'authentification et les permissions
        $user = Auth::user();

        // Admin peut voir tous les comptes, client seulement les siens
        $query = CompteBancaire::with('client:id,nom,prenom,numero_client,telephone,email')
            ->where('type_compte', '!=', 'joint') // Exclure les comptes joints
            ->whereNull('deleted_at'); // Comptes non supprimés

        if ($user && $user->role !== 'admin') {
            // Client ne voit que ses propres comptes
            $query->where('client_id', $user->client_id ?? null);
        }

        // Filtres par défaut : seulement comptes chèque et épargne actifs (pas de comptes bloqués ou fermés)
        $query->whereIn('type_compte', ['cheque', 'epargne'])
              ->where('statut', 'actif')
              ->where('est_archive', false);

        // Filtres optionnels
        if ($request->has('type') && in_array($request->type, ['cheque', 'epargne'])) {
            $query->where('type_compte', $request->type);
        }

        // Note: Selon US 2.0, on n'affiche pas les comptes bloqués ou fermés
        // Donc on ne permet pas le filtre par statut

        // Recherche par titulaire ou numéro
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_compte', 'like', '%' . $search . '%')
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('nom', 'like', '%' . $search . '%')
                                  ->orWhere('prenom', 'like', '%' . $search . '%')
                                  ->orWhere('numero_client', 'like', '%' . $search . '%');
                  });
            });
        }

        // Tri
        $sort = $request->get('sort', 'dateCreation');
        $order = $request->get('order', 'desc');

        switch ($sort) {
            case 'dateCreation':
                $query->orderBy('date_ouverture', $order);
                break;
            case 'solde':
                $query->orderBy('solde', $order);
                break;
            case 'titulaire':
                $query->join('clients', 'comptes_bancaires.client_id', '=', 'clients.id')
                      ->orderBy('clients.nom', $order)
                      ->orderBy('clients.prenom', $order)
                      ->select('comptes_bancaires.*');
                break;
            default:
                $query->orderBy('date_ouverture', $order);
        }

        // Pagination
        $limit = min($request->get('limit', 10), 100);
        $page = $request->get('page', 1);

        $comptes = $query->paginate($limit, ['*'], 'page', $page);

        // Transformer les données selon le format demandé
        $transformedData = $comptes->getCollection()->map(function($compte) {
            return [
                'id' => $compte->id,
                'numeroCompte' => $compte->numero_compte,
                'titulaire' => $compte->client->nom_complet,
                'type' => $compte->type_compte,
                'solde' => (int) $compte->solde,
                'devise' => $compte->devise,
                'dateCreation' => $compte->date_ouverture->toISOString(),
                'statut' => $compte->statut,
                'motifBlocage' => $compte->getEstBloqueAttribute() ? 'Inactivité de 30+ jours' : null,
                'metadata' => [
                    'derniereModification' => $compte->updated_at->toISOString(),
                    'version' => 1
                ]
            ];
        });

        // Créer une nouvelle collection avec les données transformées
        $comptes->setCollection(collect($transformedData));

        // Construire la réponse personnalisée
        $response = [
            'success' => true,
            'data' => $transformedData,
            'pagination' => [
                'currentPage' => $comptes->currentPage(),
                'totalPages' => $comptes->lastPage(),
                'totalItems' => $comptes->total(),
                'itemsPerPage' => $comptes->perPage(),
                'hasNext' => $comptes->hasMorePages(),
                'hasPrevious' => $comptes->currentPage() > 1,
            ],
            'links' => [
                'self' => $request->url() . '?' . http_build_query($request->query()),
                'next' => $comptes->nextPageUrl(),
                'first' => $comptes->url(1),
                'last' => $comptes->url($comptes->lastPage()),
            ]
        ];

        return response()->json($response);
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
     *     path="/comptes",
     *     summary="Créer un compte bancaire",
     *     description="Crée un compte bancaire pour un client existant ou nouveau. Génère automatiquement numéro de compte, mot de passe et code.",
     *     operationId="createCompte",
     *     tags={"Comptes Bancaires"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "soldeInitial", "devise", "client"},
     *             @OA\Property(property="type", type="string", enum={"cheque", "epargne"}, example="cheque"),
     *             @OA\Property(property="soldeInitial", type="number", format="float", example=500000),
     *             @OA\Property(property="devise", type="string", example="XOF"),
     *             @OA\Property(property="solde", type="number", format="float", example=10000),
     *             @OA\Property(property="client", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="titulaire", type="string", example="Cheikh Sy"),
     *                 @OA\Property(property="email", type="string", format="email", example="cheikh.sy@example.com"),
     *                 @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="660f9511-f30c-52e5-b827-557766551111"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123460"),
     *                 @OA\Property(property="titulaire", type="string", example="Cheikh Sy"),
     *                 @OA\Property(property="type", type="string", example="cheque"),
     *                 @OA\Property(property="solde", type="number", format="float", example=500000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-10-19T10:30:00Z"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2025-10-19T10:30:00Z"),
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function store(Request $request)
    {
        // Validation des données d'entrée selon les spécifications
        $validated = $request->validate([
            'type' => 'required|in:cheque,epargne',
            'soldeInitial' => 'required|numeric|min:10000',
            'devise' => 'required|string|in:FCFA,XOF,EUR,USD',
            'client' => 'required|array',
            'client.id' => 'sometimes|string|uuid|nullable',
            'client.titulaire' => 'required_without:client.id|string|max:255',
            'client.email' => 'required_without:client.id|email|max:255|unique:clients,email',
            'client.telephone' => ['required_without:client.id', 'string', 'max:20', 'unique:clients,telephone', new ValidNciAndTelephone()],
            'client.adresse' => 'required|string|max:500',
            'client.nci' => 'sometimes|string|max:20',
        ]);

        DB::beginTransaction();

        try {
            $client = null;

            // Si un ID client est fourni, récupérer le client existant
            if (!empty($validated['client']['id'])) {
                $client = Client::findOrFail($validated['client']['id']);
            } else {
                // Extraire nom et prénom du titulaire
                $titulaireParts = explode(' ', $validated['client']['titulaire'], 2);
                $nom = $titulaireParts[1] ?? $titulaireParts[0];
                $prenom = $titulaireParts[0];

                // Générer mot de passe et code pour le nouveau client
                $password = $this->generatePassword();
                $code = $this->generateCode();

                // Créer le client avec mot de passe et code
                $client = Client::create([
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $validated['client']['email'],
                    'telephone' => $validated['client']['telephone'],
                    'adresse' => $validated['client']['adresse'],
                    'date_naissance' => now()->subYears(18)->toDateString(), // Valeur par défaut
                    'password' => $password,
                    'code' => $code,
                ]);

                // Créer un utilisateur lié au client
                $user = User::create([
                    'name' => $client->nom_complet,
                    'email' => $client->email,
                    'password' => $password,
                    'role' => 'user',
                    'statut' => 'actif',
                    'authenticatable_type' => Client::class,
                    'authenticatable_id' => $client->id,
                ]);
            }

            // Créer le compte bancaire
            $compte = CompteBancaire::create([
                'client_id' => $client->id,
                'type_compte' => $validated['type'],
                'devise' => $validated['devise'],
                'solde_initial' => $validated['soldeInitial'],
                'decouvert_autorise' => 0, // Par défaut pour les comptes chèque
                'date_ouverture' => now()->toDateString(),
                'statut' => 'actif',
            ]);

            DB::commit();

            // Envoyer les notifications (email avec mot de passe, SMS avec code)
            $this->sendAccountCreationNotifications($compte, $client, $password ?? null, $code ?? null);

            // Retourner la réponse dans le format demandé
            return response()->json([
                'success' => true,
                'message' => 'Compte créé avec succès',
                'data' => [
                    'id' => $compte->id,
                    'numeroCompte' => $compte->numero_compte,
                    'titulaire' => $client->nom_complet,
                    'type' => $compte->type_compte,
                    'solde' => (float) $validated['soldeInitial'],
                    'devise' => $compte->devise,
                    'dateCreation' => $compte->date_ouverture->toISOString(),
                    'statut' => $compte->statut,
                    'metadata' => [
                        'derniereModification' => $compte->updated_at->toISOString(),
                        'version' => 1
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du compte bancaire', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return $this->errorResponse('Erreur lors de la création du compte bancaire.', 500);
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
        return str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Envoyer les notifications de création de compte (mail et SMS)
     */
    private function sendAccountCreationNotifications(CompteBancaire $compte, Client $client, ?string $password = null, ?string $code = null)
    {
        try {
            // Envoi de mail avec mot de passe
            $this->sendAccountCreationEmail($compte, $client, $password);

            // Envoi de SMS avec code
            $this->sendAccountCreationSMS($compte, $client, $code);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi des notifications de création de compte', [
                'compte_id' => $compte->id,
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
            // Ne pas échouer la création du compte pour autant
        }
    }

    /**
     * Envoyer l'email de création de compte avec mot de passe
     */
    private function sendAccountCreationEmail(CompteBancaire $compte, Client $client, ?string $password = null)
    {
        // Simulation d'envoi d'email avec mot de passe
        Log::info('Email de création de compte envoyé', [
            'to' => $client->email,
            'compte' => $compte->numero_compte,
            'titulaire' => $client->nom_complet,
            'password' => $password ? 'Envoyé' : 'Non généré',
        ]);

        // En production, utiliser un service de mail comme Mailgun, SendGrid, etc.
        // Mail::to($client->email)->send(new AccountCreated($compte, $client, $password));
    }

    /**
     * Envoyer le SMS de création de compte avec code
     */
    private function sendAccountCreationSMS(CompteBancaire $compte, Client $client, ?string $code = null)
    {
        // Simulation d'envoi de SMS avec code
        Log::info('SMS de création de compte envoyé', [
            'to' => $client->telephone,
            'compte' => $compte->numero_compte,
            'titulaire' => $client->nom_complet,
            'code' => $code ? 'Envoyé' : 'Non généré',
        ]);

        // En production, utiliser un service SMS comme Twilio, etc.
        // $smsService = app(SmsServiceInterface::class);
        // $message = $code
        //     ? "Votre compte {$compte->numero_compte} a été créé. Code: {$code}"
        //     : "Votre compte {$compte->numero_compte} a été créé avec succès.";
        // $smsService->send($client->telephone, $message);
    }

    /**
     * @OA\Get(
     *     path="/comptes/{compte_bancaire}",
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
    public function show($id)
    {
        // Pour les tests, on utilise l'ID directement au lieu du model binding
        $compte_bancaire = CompteBancaire::with('client:id,nom,prenom,numero_client,telephone,email')->findOrFail($id);

        // Selon US: Détail compte par ID - Pour les comptes épargne, afficher dates de blocage
        $data = new CompteBancaireResource($compte_bancaire);

        // Ajouter les dates de blocage pour les comptes épargne
        if ($compte_bancaire->type_compte === 'epargne') {
            $data->additional([
                'dateDebutBlocage' => $compte_bancaire->date_debut_blocage?->toISOString(),
                'dateFinBlocage' => $compte_bancaire->date_fin_blocage?->toISOString(),
            ]);
        }

        // Selon US: Si le compte épargne est archivé, récupérer depuis Neon
        if ($compte_bancaire->type_compte === 'epargne' && $compte_bancaire->est_archive) {
            // Simulation de récupération depuis Neon
            $neonData = $this->getArchivedSavingsAccountFromNeon($compte_bancaire->id);
            if ($neonData) {
                $data = $data->additional($neonData);
            }
        }

        return $this->successResponse($data, 'Compte bancaire récupéré avec succès');
    }

    /**
     * Récupérer un compte bancaire par numéro
     */
    public function showByNumero($numeroCompte)
    {
        $compte_bancaire = CompteBancaire::with('client:id,nom,prenom,numero_client,telephone,email')
            ->where('numero_compte', $numeroCompte)
            ->firstOrFail();

        // Selon US: Détail compte par numéro - Même logique que show()
        $data = new CompteBancaireResource($compte_bancaire);

        // Ajouter les dates de blocage pour les comptes épargne
        if ($compte_bancaire->type_compte === 'epargne') {
            $data->additional([
                'dateDebutBlocage' => $compte_bancaire->date_debut_blocage?->toISOString(),
                'dateFinBlocage' => $compte_bancaire->date_fin_blocage?->toISOString(),
            ]);
        }

        // Selon US: Si le compte épargne est archivé, récupérer depuis Neon
        if ($compte_bancaire->type_compte === 'epargne' && $compte_bancaire->est_archive) {
            $neonData = $this->getArchivedSavingsAccountFromNeon($compte_bancaire->id);
            if ($neonData) {
                $data = $data->additional($neonData);
            }
        }

        return $this->successResponse($data, 'Compte bancaire récupéré avec succès');
    }

    /**
     * Récupérer un compte épargne archivé depuis Neon
     */
    private function getArchivedSavingsAccountFromNeon($compteId)
    {
        try {
            // Simulation d'appel à Neon
            // En production, remplacer par un vrai appel API
            $response = Http::timeout(30)->get(config('services.neon_api_url') . '/archived-accounts/' . $compteId, [
                'api_key' => config('services.neon_api_key'),
            ]);

            if ($response->successful()) {
                return $response->json()['data'] ?? null;
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du compte archivé depuis Neon', [
                'compte_id' => $compteId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Récupérer un client par numéro de téléphone
     */
    public function getClientByTelephone($telephone)
    {
        $client = Client::where('telephone', $telephone)->firstOrFail();

        return $this->successResponse($client, 'Client récupéré avec succès');
    }

    /**
     * @OA\Put(
     *     path="/comptes/{compte_bancaire}",
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
     *             @OA\Property(property="titulaire", type="string", example="Amadou Diallo Junior", description="Nom complet du titulaire"),
     *             @OA\Property(property="informationsClient", type="object",
     *                 @OA\Property(property="telephone", type="string", example="+221771234568", description="Téléphone du client"),
     *                 @OA\Property(property="email", type="string", format="email", example="amadou.diallo@example.com", description="Email du client"),
     *                 @OA\Property(property="nci", type="string", example="1234567890123", description="Numéro de carte d'identité")
     *             ),
     *             @OA\Property(property="type_compte", type="string", enum={"cheque", "epargne"}, example="epargne", description="Type de compte"),
     *             @OA\Property(property="devise", type="string", example="XOF", description="Devise du compte"),
     *             @OA\Property(property="decouvert_autorise", type="number", format="float", example=600, description="Découvert autorisé"),
     *             @OA\Property(property="statut", type="string", enum={"actif", "inactif", "bloque"}, example="bloque", description="Statut du compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bancaire mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo Junior"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", format="float", example=1250000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
     *                 @OA\Property(property="statut", type="string", example="bloque"),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2025-10-19T11:00:00Z"),
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 )
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
     public function update(Request $request, $id)
     {
         // Pour les tests, on utilise l'ID directement
         $compte_bancaire = CompteBancaire::findOrFail($id);

         // Validation des données
         $validated = $request->validate([
             'titulaire' => 'sometimes|string|max:255',
             'informationsClient' => 'sometimes|array',
             'informationsClient.telephone' => 'sometimes|string|max:20',
             'informationsClient.email' => 'sometimes|email|max:255',
             'informationsClient.nci' => 'sometimes|string|max:20',
             'type_compte' => 'sometimes|in:cheque,epargne',
             'devise' => 'sometimes|string|min:3|max:3',
             'decouvert_autorise' => 'sometimes|numeric|min:0',
             'statut' => 'sometimes|in:actif,inactif,bloque,ferme',
         ]);

         // Filtrer les valeurs vides
         $validated = array_filter($validated, function($value) {
             return $value !== null && $value !== '';
         });

         // Gestion de la mise à jour du client si informationsClient est fourni
         if (isset($validated['informationsClient']) || isset($validated['titulaire'])) {
             $client = $compte_bancaire->client;

             if ($client) {
                 $clientData = [];

                 // Mise à jour du titulaire (nom complet)
                 if (isset($validated['titulaire'])) {
                     $parts = explode(' ', $validated['titulaire'], 2);
                     $clientData['prenom'] = $parts[0] ?? '';
                     $clientData['nom'] = $parts[1] ?? $parts[0] ?? '';
                 }

                 // Mise à jour des informations client
                 if (isset($validated['informationsClient'])) {
                     if (isset($validated['informationsClient']['telephone'])) {
                         $clientData['telephone'] = $validated['informationsClient']['telephone'];
                     }
                     if (isset($validated['informationsClient']['email'])) {
                         $clientData['email'] = $validated['informationsClient']['email'];
                     }
                     // Note: Le NCI pourrait nécessiter un champ supplémentaire dans la table clients
                 }

                 if (!empty($clientData)) {
                     try {
                         $client->update($clientData);
                     } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                         // Gérer l'erreur de contrainte unique sur l'email
                         return $this->errorResponse('L\'email fourni est déjà utilisé par un autre client.', 422);
                     }
                 }
             }

             // Supprimer les champs client des données de validation du compte
             unset($validated['titulaire'], $validated['informationsClient']);
         }

         // Mise à jour du compte bancaire
         if (!empty($validated)) {
             $compte_bancaire->update($validated);
         }

         // Recharger le compte avec les relations
         $compte_bancaire->load('client:id,nom,prenom,numero_client,telephone,email');

         // Retourner la réponse dans le format demandé
         return response()->json([
             'success' => true,
             'message' => 'Compte mis à jour avec succès',
             'data' => [
                 'id' => $compte_bancaire->id,
                 'numeroCompte' => $compte_bancaire->numero_compte,
                 'titulaire' => $compte_bancaire->client->nom_complet,
                 'type' => $compte_bancaire->type_compte,
                 'solde' => (int) $compte_bancaire->solde,
                 'devise' => $compte_bancaire->devise,
                 'dateCreation' => $compte_bancaire->date_ouverture->toISOString(),
                 'statut' => $compte_bancaire->statut,
                 'metadata' => [
                     'derniereModification' => $compte_bancaire->updated_at->toISOString(),
                     'version' => 1
                 ]
             ],
             'timestamp' => now()->toISOString(),
         ]);
     }

    /**
     * Bloquer un compte épargne
     *
     * @OA\Post(
     *     path="/comptes/{compte_bancaire}/bloquer",
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
     *             required={"motif", "duree", "unite"},
     *             @OA\Property(property="motif", type="string", example="Activité suspecte détectée", description="Motif du blocage"),
     *             @OA\Property(property="duree", type="integer", example=30, description="Durée du blocage", minimum=1),
     *             @OA\Property(property="unite", type="string", enum={"jours", "mois"}, example="mois", description="Unité de la durée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bloqué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="statut", type="string", example="bloque"),
     *                 @OA\Property(property="motifBlocage", type="string", example="Activité suspecte détectée"),
     *                 @OA\Property(property="dateBlocage", type="string", format="date-time", example="2025-10-19T11:20:00Z"),
     *                 @OA\Property(property="dateDeblocagePrevue", type="string", format="date-time", example="2025-11-18T11:20:00Z")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides ou compte ne peut pas être bloqué"),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
     public function bloquer(Request $request, $id)
     {
         // Pour les tests, on utilise l'ID directement
         $compte_bancaire = CompteBancaire::findOrFail($id);

         // Validation des données
         $validated = $request->validate([
             'motif' => 'required|string|max:255',
             'duree' => 'required|integer|min:1',
             'unite' => 'required|string',
         ]);

         // Vérification manuelle de l'unité pour plus de fiabilité
         if (!in_array(trim($validated['unite']), ['jours', 'mois'])) {
             return $this->errorResponse('L\'unité doit être "jours" ou "mois".', 422);
         }

         // Convertir la durée en jours si nécessaire
         $dureeJours = $validated['unite'] === 'mois' ? $validated['duree'] * 30 : $validated['duree'];

         // Vérifier que c'est un compte épargne actif (selon US: Blocage)
         if ($compte_bancaire->type_compte !== 'epargne') {
             return $this->errorResponse('Seuls les comptes épargne peuvent être bloqués.', 400);
         }
 
         if ($compte_bancaire->statut !== 'actif') {
             return $this->errorResponse('Le compte doit être actif pour être bloqué.', 400);
         }

         if ($compte_bancaire->getEstBloqueAttribute()) {
             return $this->errorResponse('Le compte est déjà bloqué.', 400);
         }

         try {
             $result = $compte_bancaire->bloquer($dureeJours, $validated['motif']);

             if ($result) {
                 Log::info('Compte épargne bloqué via API', [
                     'numero_compte' => $compte_bancaire->numero_compte,
                     'duree' => $dureeJours,
                     'unite' => $validated['unite'],
                     'motif' => $validated['motif'],
                     'date_fin' => $compte_bancaire->date_fin_blocage,
                 ]);

                 // Retourner la réponse dans le format demandé
                 return response()->json([
                     'success' => true,
                     'message' => 'Compte bloqué avec succès',
                     'data' => [
                         'id' => $compte_bancaire->id,
                         'statut' => 'bloque',
                         'motifBlocage' => $validated['motif'],
                         'dateDebutBlocage' => $compte_bancaire->date_debut_blocage->toISOString(),
                         'dateFinBlocage' => $compte_bancaire->date_fin_blocage->toISOString(),
                     ],
                     'timestamp' => now()->toISOString(),
                 ]);
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
     *     path="/comptes/{compte_bancaire}/debloquer",
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
     *             @OA\Property(property="message", type="string", example="Compte débloqué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="motifBlocage", type="string", nullable=true, example=null),
     *                 @OA\Property(property="dateBlocage", type="string", format="date-time", nullable=true, example=null),
     *                 @OA\Property(property="dateDeblocagePrevue", type="string", format="date-time", nullable=true, example=null)
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Le compte n'est pas bloqué"),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
     public function debloquer($id)
     {
         // Pour les tests, on utilise l'ID directement
         $compte_bancaire = CompteBancaire::findOrFail($id);

         if (!$compte_bancaire->getEstBloqueAttribute()) {
             return $this->errorResponse('Le compte n\'est pas bloqué.', 400);
         }

         try {
             $result = $compte_bancaire->debloquer();

             if ($result) {
                 Log::info('Compte épargne débloqué via API', [
                     'numero_compte' => $compte_bancaire->numero_compte,
                 ]);

                 // Retourner la réponse dans le format demandé (comme pour le blocage)
                 return response()->json([
                     'success' => true,
                     'message' => 'Compte débloqué avec succès',
                     'data' => [
                         'id' => $compte_bancaire->id,
                         'statut' => 'actif',
                         'motifBlocage' => null,
                         'dateBlocage' => null,
                         'dateDeblocagePrevue' => null,
                     ],
                     'timestamp' => now()->toISOString(),
                 ]);
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
     * Archiver un compte bancaire
     *
     * @OA\Post(
     *     path="/comptes/{compte_bancaire}/archiver",
     *     summary="Archiver un compte bancaire",
     *     description="Archive un compte bancaire. Seuls les comptes actifs peuvent être archivés.",
     *     operationId="archiverCompteBancaire",
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
     *             required={"motif"},
     *             @OA\Property(property="motif", type="string", example="Archivage demandé par le client", description="Motif de l'archivage")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte archivé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/CompteBancaire"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides ou compte ne peut pas être archivé"),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function archiver(Request $request, $id)
    {
        // Pour les tests, on utilise l'ID directement
        $compte_bancaire = CompteBancaire::findOrFail($id);

        // Validation des données
        $validated = $request->validate([
            'motif' => 'required|string|max:255',
        ]);

        // Selon US: Archivage - Seul les comptes épargne bloqués dont la date de début de blocage est échue peuvent être archivés
        if ($compte_bancaire->type_compte !== 'epargne') {
            return $this->errorResponse('Seuls les comptes épargne peuvent être archivés.', 400);
        }

        if (!$compte_bancaire->getEstBloqueAttribute()) {
            return $this->errorResponse('Le compte doit être bloqué pour être archivé.', 400);
        }

        if ($compte_bancaire->est_archive) {
            return $this->errorResponse('Le compte est déjà archivé.', 400);
        }

        // Vérifier que la date de début de blocage est échue
        if ($compte_bancaire->date_debut_blocage && $compte_bancaire->date_debut_blocage->isFuture()) {
            return $this->errorResponse('La date de début de blocage n\'est pas encore échue.', 400);
        }

        try {
            $result = $compte_bancaire->archiver($validated['motif']);

            if ($result) {
                Log::info('Compte bancaire archivé via API', [
                    'numero_compte' => $compte_bancaire->numero_compte,
                    'type_compte' => $compte_bancaire->type_compte,
                    'motif' => $validated['motif'],
                    'date_archivage' => $compte_bancaire->date_archivage,
                ]);

                // Retourner la réponse dans le format demandé (comme pour le blocage/déblocage)
                return response()->json([
                    'success' => true,
                    'message' => 'Compte archivé avec succès',
                    'data' => [
                        'id' => $compte_bancaire->id,
                        'statut' => $compte_bancaire->statut,
                        'motifBlocage' => $compte_bancaire->motif_blocage,
                        'dateBlocage' => $compte_bancaire->date_debut_blocage?->toISOString(),
                        'dateDeblocagePrevue' => $compte_bancaire->date_fin_blocage?->toISOString(),
                    ],
                    'timestamp' => now()->toISOString(),
                ]);
            } else {
                return $this->errorResponse('Échec de l\'archivage du compte.', 500);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'archivage du compte bancaire via API', [
                'numero_compte' => $compte_bancaire->numero_compte,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Erreur lors de l\'archivage du compte.', 500);
        }
    }

    /**
     * Désarchiver un compte bancaire
     *
     * @OA\Post(
     *     path="/comptes/{compte_bancaire}/desarchiver",
     *     summary="Désarchiver un compte bancaire",
     *     description="Désarchive un compte bancaire archivé.",
     *     operationId="desarchiverCompteBancaire",
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
     *         description="Compte désarchivé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/CompteBancaire"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Le compte n'est pas archivé"),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function desarchiver($id)
    {
        // Pour les tests, on utilise l'ID directement
        $compte_bancaire = CompteBancaire::findOrFail($id);

        // Selon US: Désarchivage - Seul les comptes épargne bloqués dont la date de fin de blocage est échue peuvent être désarchivés
        if ($compte_bancaire->type_compte !== 'epargne') {
            return $this->errorResponse('Seuls les comptes épargne peuvent être désarchivés.', 400);
        }

        if (!$compte_bancaire->getEstBloqueAttribute()) {
            return $this->errorResponse('Le compte doit être bloqué pour être désarchivé.', 400);
        }

        if (!$compte_bancaire->est_archive) {
            return $this->errorResponse('Le compte n\'est pas archivé.', 400);
        }

        // Vérifier que la date de fin de blocage est échue
        if ($compte_bancaire->date_fin_blocage && $compte_bancaire->date_fin_blocage->isFuture()) {
            return $this->errorResponse('La date de fin de blocage n\'est pas encore échue.', 400);
        }

        try {
            $result = $compte_bancaire->desarchiver();

            if ($result) {
                Log::info('Compte bancaire désarchivé via API', [
                    'numero_compte' => $compte_bancaire->numero_compte,
                ]);

                // Retourner la réponse dans le format demandé (comme pour le blocage/déblocage)
                return response()->json([
                    'success' => true,
                    'message' => 'Compte désarchivé avec succès',
                    'data' => [
                        'id' => $compte_bancaire->id,
                        'statut' => $compte_bancaire->statut,
                        'motifBlocage' => $compte_bancaire->motif_blocage,
                        'dateBlocage' => $compte_bancaire->date_debut_blocage?->toISOString(),
                        'dateDeblocagePrevue' => $compte_bancaire->date_fin_blocage?->toISOString(),
                    ],
                    'timestamp' => now()->toISOString(),
                ]);
            } else {
                return $this->errorResponse('Échec du désarchivage du compte.', 500);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors du désarchivage du compte bancaire via API', [
                'numero_compte' => $compte_bancaire->numero_compte,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Erreur lors du désarchivage du compte.', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/comptes/{compte_bancaire}",
     *     summary="Supprimer un compte bancaire",
     *     description="Supprime un compte bancaire du système. Seuls les comptes non bloqués peuvent être supprimés. Les comptes bloqués doivent être débloqués avant suppression. Si le compte est déjà supprimé (soft delete), il sera supprimé définitivement.",
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
     *     @OA\Response(response=400, description="Impossible de supprimer le compte (compte bloqué ou solde non nul)"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
     public function destroy($id)
     {
         // Pour les tests, on utilise l'ID directement, en incluant les comptes soft deleted
         $compte_bancaire = CompteBancaire::withTrashed()->findOrFail($id);

         // Si le compte est déjà soft deleted, le supprimer définitivement
         if ($compte_bancaire->trashed()) {
             try {
                 $compte_bancaire->forceDelete();

                 Log::info('Compte bancaire supprimé définitivement via API', [
                     'numero_compte' => $compte_bancaire->numero_compte,
                     'type_compte' => $compte_bancaire->type_compte,
                 ]);

                 return $this->successResponse(
                     null,
                     'Compte bancaire supprimé définitivement avec succès'
                 );
             } catch (\Exception $e) {
                 Log::error('Erreur lors de la suppression définitive du compte bancaire via API', [
                     'numero_compte' => $compte_bancaire->numero_compte,
                     'error' => $e->getMessage(),
                 ]);

                 return $this->errorResponse('Erreur lors de la suppression définitive du compte.', 500);
             }
         }

         // Selon US: Supprimer compte - Seul les comptes actifs peuvent être supprimés
         if ($compte_bancaire->statut !== 'actif') {
             return $this->errorResponse('Seul un compte actif peut être supprimé.', 400);
         }
 
         // Vérifier si le compte est bloqué
         if ($compte_bancaire->getEstBloqueAttribute()) {
             return $this->errorResponse('Le compte est bloqué. Veuillez le débloquer avant de le supprimer.', 400);
         }

         try {
             $compte_bancaire->delete();

             Log::info('Compte bancaire supprimé via API', [
                 'numero_compte' => $compte_bancaire->numero_compte,
                 'type_compte' => $compte_bancaire->type_compte,
             ]);

             return $this->successResponse(
                 null,
                 'Compte bancaire supprimé avec succès'
             );
         } catch (\Exception $e) {
             Log::error('Erreur lors de la suppression du compte bancaire via API', [
                 'numero_compte' => $compte_bancaire->numero_compte,
                 'error' => $e->getMessage(),
             ]);

             return $this->errorResponse('Erreur lors de la suppression du compte.', 500);
         }
     }
}