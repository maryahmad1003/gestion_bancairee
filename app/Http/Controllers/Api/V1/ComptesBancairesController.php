<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Casts\SoldeCast;
use App\Events\CompteBancaireCreated;
use App\Exceptions\CompteBancaireException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompteBancaireRequest;
use App\Http\Requests\UpdateCompteBancaireRequest;
use App\Http\Resources\CompteBancaireResource;
use App\Models\Client;
use App\Models\CompteBancaire;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Rules\ValidNciAndTelephone;

class ComptesBancairesController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/comptes",
     *     summary="Récupérer la liste des comptes bancaires",
     *     description="Permet à l'admin de récupérer tous les comptes ou au client de récupérer ses comptes. Liste uniquement les comptes non supprimés de type chèque ou épargne actifs (pas de comptes bloqués ou fermés).",
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
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de la page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page (max 100)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=10)
     *     ),
     *     @OA\Parameter(
     *         name="X-Rate-Limit",
     *         in="header",
     *         description="Limite de taux d'API (100 requêtes par heure)",
     *         required=false,
     *         @OA\Schema(type="string", example="100")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Champ de tri (dateCreation, solde, titulaire)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"dateCreation", "solde", "titulaire"}, example="dateCreation")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre de tri (asc, desc)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example="desc")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par titulaire ou numéro de compte",
     *         required=false,
     *         @OA\Schema(type="string", example="C00123456")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CompteBancaire")),
     *             @OA\Property(property="pagination", type="object",
     *                  @OA\Property(property="currentPage", type="integer", example=1),
     *                  @OA\Property(property="totalPages", type="integer", example=5),
     *                  @OA\Property(property="totalItems", type="integer", example=50),
     *                  @OA\Property(property="itemsPerPage", type="integer", example=10),
     *                  @OA\Property(property="hasNext", type="boolean", example=true),
     *                  @OA\Property(property="hasPrevious", type="boolean", example=false)
     *              ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         ),
     *         @OA\Header(
     *             header="X-Rate-Limit-Remaining",
     *             description="Nombre de requêtes restantes dans la fenêtre de taux",
     *             @OA\Schema(type="integer", example=99)
     *         ),
     *         @OA\Header(
     *             header="X-Rate-Limit-Reset",
     *             description="Timestamp de réinitialisation du taux limite",
     *             @OA\Schema(type="integer", example=1635782400)
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
              ->where('est_archive', false)
              ->where('est_bloque', false);

        // Filtres optionnels
        if ($request->has('type_compte') && in_array($request->type_compte, ['cheque', 'epargne'])) {
            $query->where('type_compte', $request->type_compte);
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
     *     description="Crée un compte bancaire pour un client existant ou nouveau. Génère automatiquement numéro de compte, mot de passe et code. Envoie un mail et un SMS de confirmation.",
     *     operationId="createCompte",
     *     tags={"Comptes Bancaires"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"type", "soldeInitial", "devise", "client"},
     *                 @OA\Property(property="type", type="string", enum={"cheque", "epargne"}, example="cheque"),
     *                 @OA\Property(property="soldeInitial", type="number", format="float", example=500000),
     *                 @OA\Property(property="devise", type="string", example="XOF"),
     *                 @OA\Property(property="client", type="object",
     *                     oneOf={
     *                         @OA\Schema(
     *                             title="Client existant",
     *                             @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID du client existant")
     *                         ),
     *                         @OA\Schema(
     *                             title="Nouveau client",
     *                             required={"titulaire", "email", "telephone", "adresse"},
     *                             @OA\Property(property="id", type="string", format="uuid", nullable=true, example=null, description="Laisser null pour créer un nouveau client"),
     *                             @OA\Property(property="titulaire", type="string", example="Cheikh Sy", description="Nom complet du titulaire"),
     *                             @OA\Property(property="email", type="string", format="email", example="cheikh.sy@example.com", description="Email du client"),
     *                             @OA\Property(property="telephone", type="string", example="+221771234567", description="Téléphone du client"),
     *                             @OA\Property(property="adresse", type="string", example="Dakar, Sénégal", description="Adresse du client"),
     *                             @OA\Property(property="nci", type="string", example="1234567890123", description="Numéro de carte d'identité (optionnel)")
     *                         )
     *                     }
     *                 )
     *             ),
     *             @OA\Examples(
*                 example="client_existant",
*                 summary="Créer compte pour client existant",
*                 value={
*                     "type": "cheque",
*                     "soldeInitial": 500000,
*                     "devise": "XOF",
*                     "client": {
*                         "id": "550e8400-e29b-41d4-a716-446655440000"
*                     }
*                 }
*             ),
*             @OA\Examples(
*                 example="nouveau_client",
*                 summary="Créer compte avec nouveau client",
*                 value={
*                     "type": "epargne",
*                     "soldeInitial": 100000,
*                     "devise": "XOF",
*                     "client": {
*                         "titulaire": "Amadou Diallo",
*                         "email": "amadou.diallo@example.com",
*                         "telephone": "+221771234568",
*                         "adresse": "Thiès, Sénégal",
*                         "nci": "1234567890123"
*                     }
*                 }
*             ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès - Mail et SMS de confirmation envoyés automatiquement",
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
     *         ),
     *         @OA\Header(
     *             header="X-Rate-Limit-Remaining",
     *             description="Nombre de requêtes restantes dans la fenêtre de taux",
     *             @OA\Schema(type="integer", example=99)
     *         ),
     *         @OA\Header(
     *             header="X-Rate-Limit-Reset",
     *             description="Timestamp de réinitialisation du taux limite",
     *             @OA\Schema(type="integer", example=1635782400)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    /**
     * @OA\Post(
     *     path="/comptes/{compte_bancaire}/send-verification-code",
     *     summary="Envoyer un code de vérification par email",
     *     description="Génère et envoie un code de vérification à 6 chiffres par email au propriétaire du compte. Le code expire après 15 minutes.",
     *     operationId="sendVerificationCode",
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
     *         description="Code de vérification envoyé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code de vérification envoyé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="expires_at", type="string", format="date-time", example="2025-11-01T10:49:50Z")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
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

            // Si un ID client est fourni, essayer de récupérer le client existant
            if (!empty($validated['client']['id'])) {
                $client = Client::find($validated['client']['id']);

                // Si le client n'existe pas, créer un nouveau client avec les informations fournies
                if (!$client) {
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
            } else {
                // Aucun ID fourni, créer un nouveau client
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
                'solde' => $validated['solde'] ?? $validated['soldeInitial'],
                'decouvert_autorise' => 0, // Par défaut pour les comptes chèque
                'date_ouverture' => now()->toDateString(),
                'statut' => 'actif',
            ]);

            DB::commit();

            // Générer numéro de compte unique
            $numeroCompte = $this->generateNumeroCompte($compte);
            $compte->update(['numero_compte' => $numeroCompte]);

            // Déclencher l'événement de création de compte
            event(new CompteBancaireCreated($compte, $numeroCompte));

            // Envoyer mail et SMS de confirmation
            try {
                // Envoi du mail
                Mail::raw("Votre compte bancaire {$numeroCompte} a été créé avec succès. Solde initial: {$validated['soldeInitial']} {$validated['devise']}.", function ($message) use ($client) {
                    $message->to($client->email)
                            ->subject('Création de votre compte bancaire');
                });

                // Envoi du SMS
                $smsService = app(\App\Contracts\SmsServiceInterface::class);
                $smsService->send($client->telephone, "Votre compte bancaire {$numeroCompte} a été créé avec succès. Solde initial: {$validated['soldeInitial']} {$validated['devise']}.");

                Log::info('Mail et SMS envoyés pour création de compte', [
                    'numero_compte' => $numeroCompte,
                    'client_email' => $client->email,
                    'client_telephone' => $client->telephone,
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur lors de l\'envoi du mail ou SMS de confirmation', [
                    'numero_compte' => $numeroCompte,
                    'error' => $e->getMessage(),
                ]);
                // Ne pas échouer la création du compte pour autant
            }

            // Retourner la réponse dans le format demandé
            return response()->json([
                'success' => true,
                'message' => 'Compte créé avec succès',
                'data' => [
                    'id' => $compte->id,
                    'numeroCompte' => $numeroCompte,
                    'titulaire' => $client->nom_complet,
                    'type' => $compte->type_compte,
                    'solde' => (float) ($validated['solde'] ?? $validated['soldeInitial']),
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
      
public function sendVerificationCode($id)
{
    $compte = CompteBancaire::findOrFail($id);

    $code = str_pad((string) rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    $compte->verification_code = $code;
    $compte->verification_expires_at = Carbon::now()->addMinutes(15);
    $compte->verification_used = false;
    $compte->save();

    // Envoi du mail via Mailjet
    $this->sendMailjetCode($compte->client->email, $code);

    return response()->json([
        'success' => true,
        'message' => 'Code de vérification envoyé avec succès',
        'data' => [
            'expires_at' => $compte->verification_expires_at->toISOString()
        ],
        'timestamp' => now()->toISOString()
    ]);
}
    /**
     * Générer un numéro de compte unique
     */
    private function generateNumeroCompte(CompteBancaire $compte): string
    {
        do {
            $prefix = $compte->type_compte === 'cheque' ? 'C' : 'E';
            $numero = $prefix . str_pad((string) rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        } while (CompteBancaire::where('numero_compte', $numero)->exists());

        return $numero;
    }

    /**
     * Envoyer un code de vérification par email via Mailjet
     */
    private function sendMailjetCode(string $email, string $code): void
    {
        try {
            $body = [
                'Messages' => [
                    [
                        'From' => [
                            'Email' => env('MAIL_FROM_ADDRESS', 'vonnemary19@gmail.com'),
                            'Name' => env('MAIL_FROM_NAME', 'Banque Vonne')
                        ],
                        'To' => [
                            ['Email' => $email]
                        ],
                        'Subject' => "Code de vérification - Banque Vonne",
                        'TextPart' => "Votre code de vérification est : $code",
                        'HTMLPart' => "<h3>Votre code de vérification est : <strong>$code</strong></h3><p>Ce code expire dans 15 minutes.</p>"
                    ]
                ]
            ];

            \Mailjet\LaravelMailjet\Facades\Mailjet::send($body);

            Log::info('Code de vérification envoyé par email', [
                'email' => $email,
                'code_length' => strlen($code)
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du code par email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            throw $e;
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
     * @OA\Get(
     *     path="/comptes/{compte_bancaire}",
     *     summary="Récupérer un compte bancaire spécifique",
     *     description="Permet de récupérer les informations détaillées d'un compte bancaire spécifique. Pour les comptes épargne, affiche les dates de début et fin de blocage. Si le compte épargne est archivé, récupère les données depuis la base Neon.",
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
     *     @OA\Parameter(
     *         name="X-Rate-Limit",
     *         in="header",
     *         description="Limite de taux d'API (100 requêtes par heure)",
     *         required=false,
     *         @OA\Schema(type="string", example="100")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bancaire récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bancaire récupéré avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Cheikh Sy"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *             @OA\Property(property="solde", type="number", format="float", example=10000),
     *                 @OA\Property(property="devise", type="string", example="XOF"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="dateDebutBlocage", type="string", format="date-time", nullable=true, example="2025-11-01T10:00:00Z", description="Date de début de blocage (comptes épargne uniquement)"),
     *                 @OA\Property(property="dateFinBlocage", type="string", format="date-time", nullable=true, example="2025-12-01T10:00:00Z", description="Date de fin de blocage (comptes épargne uniquement)"),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2025-10-19T11:00:00Z"),
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 )
     *             ),
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
        // Utiliser le model binding pour récupérer le compte avec ses relations
        $compte_bancaire->load('client:id,nom,prenom,numero_client,telephone,email');

        // Vérifier si le compte existe
        if (!$compte_bancaire) {
            return $this->errorResponse('Compte bancaire non trouvé.', 404);
        }

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
            // Récupération depuis Neon
            $neonService = app(\App\Services\NeonDatabaseService::class);
            $neonData = $neonService->retrieve('comptes_epargne_archives', ['id' => $compte_bancaire->id]);
            if (!empty($neonData)) {
                $data = $data->additional($neonData[0]);
            }
        }

        // Construire la réponse personnalisée avec headers de rate limit
        $response = response()->json([
            'success' => true,
            'message' => 'Compte bancaire récupéré avec succès',
            'data' => $data,
            'timestamp' => now()->toISOString()
        ]);

        // Ajouter les headers de rate limit
        $response->header('X-Rate-Limit-Remaining', 99); // Exemple
        $response->header('X-Rate-Limit-Reset', time() + 3600); // Exemple

        return $response;
    }

    /**
     * @OA\Get(
     *     path="/comptes/numero/{numeroCompte}",
     *     summary="Récupérer un compte bancaire par numéro",
     *     description="Permet de récupérer les informations détaillées d'un compte bancaire spécifique en utilisant son numéro de compte. Pour les comptes épargne, affiche les dates de début et fin de blocage. Si le compte épargne est archivé, récupère les données depuis la base Neon.",
     *     operationId="getCompteBancaireByNumero",
     *     tags={"Comptes Bancaires"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         required=true,
     *         description="Numéro du compte bancaire",
     *         @OA\Schema(type="string", example="C00123456")
     *     ),
     *     @OA\Parameter(
     *         name="X-Rate-Limit",
     *         in="header",
     *         description="Limite de taux d'API (100 requêtes par heure)",
     *         required=false,
     *         @OA\Schema(type="string", example="100")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bancaire récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bancaire récupéré avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Cheikh Sy"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", format="float", example=500000),
     *                 @OA\Property(property="devise", type="string", example="XOF"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="dateDebutBlocage", type="string", format="date-time", nullable=true, example="2025-11-01T10:00:00Z", description="Date de début de blocage (comptes épargne uniquement)"),
     *                 @OA\Property(property="dateFinBlocage", type="string", format="date-time", nullable=true, example="2025-12-01T10:00:00Z", description="Date de fin de blocage (comptes épargne uniquement)"),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2025-10-19T11:00:00Z"),
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 )
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
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
            // Récupération depuis Neon
            $neonService = app(\App\Services\NeonDatabaseService::class);
            $neonData = $neonService->retrieve('comptes_epargne_archives', ['id' => $compte_bancaire->id]);
            if (!empty($neonData)) {
                $data = $data->additional($neonData[0]);
            }
        }

        return $this->successResponse($data, 'Compte bancaire récupéré avec succès');
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
     public function update(Request $request, CompteBancaire $compte_bancaire)
     {
         // Utiliser le model binding pour récupérer le compte

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
     * @OA\Post(
     *     path="/comptes/{compte_bancaire}/bloquer",
     *     summary="Bloquer un compte épargne",
     *     description="Bloque un compte épargne actif pour une durée déterminée avec un motif spécifique. Après blocage, affiche les informations de blocage incluant la date de début. Les comptes bloqués peuvent être archivés automatiquement par un job quand la date de début de blocage est échue.",
     *     operationId="bloquerCompteEpargne",
     *     tags={"Comptes Bancaires"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte_bancaire",
     *         in="path",
     *         required=true,
     *         description="ID du compte bancaire à bloquer",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motif", "duree", "unite"},
     *             @OA\Property(property="motif", type="string", example="Inactivité prolongée", description="Motif du blocage"),
     *             @OA\Property(property="duree", type="integer", example=30, description="Durée du blocage"),
     *             @OA\Property(property="unite", type="string", enum={"jours", "mois"}, example="jours", description="Unité de la durée")
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
     *                 @OA\Property(property="motifBlocage", type="string", example="Inactivité prolongée"),
     *                 @OA\Property(property="dateDebutBlocage", type="string", format="date-time", example="2025-11-01T10:22:36Z"),
     *                 @OA\Property(property="dateFinBlocage", type="string", format="date-time", example="2025-12-01T10:22:36Z")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides, compte déjà bloqué ou compte non éligible (seuls les comptes épargne actifs peuvent être bloqués)"),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
     public function bloquer(Request $request, CompteBancaire $compte_bancaire)
     {
         // Utiliser le model binding pour récupérer le compte

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
     * @OA\Delete(
     *     path="/comptes/{compte_bancaire}",
     *     summary="Supprimer un compte bancaire (Soft Delete)",
     *     description="Supprime un compte bancaire actif avec solde nul. Seul les comptes actifs peuvent être supprimés. Utilise le soft delete pour conserver l'historique. Limite de taux : 10 suppressions par heure.",
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
     *     @OA\Parameter(
     *         name="X-Rate-Limit",
     *         in="header",
     *         description="Limite de taux d'API (10 suppressions par heure)",
     *         required=false,
     *         @OA\Schema(type="string", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bancaire supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bancaire supprimé avec succès"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         ),
     *         @OA\Header(
     *             header="X-Rate-Limit-Remaining",
     *             description="Nombre de suppressions restantes dans la fenêtre de taux",
     *             @OA\Schema(type="integer", example=9)
     *         ),
     *         @OA\Header(
     *             header="X-Rate-Limit-Reset",
     *             description="Timestamp de réinitialisation du taux limite",
     *             @OA\Schema(type="integer", example=1635782400)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Le compte doit avoir un solde nul, être actif et non bloqué"),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=429, description="Trop de requêtes - Limite de taux dépassée"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
     public function destroy(CompteBancaire $compte_bancaire)
     {
         // Utiliser le model binding pour récupérer le compte

         // Selon US: Supprimer compte - Seul les comptes actifs peuvent être supprimés
         if ($compte_bancaire->statut !== 'actif') {
             return $this->errorResponse('Seul un compte actif peut être supprimé.', 400);
         }

         // Vérifier que le solde est nul avant suppression
         if ($compte_bancaire->solde != 0) {
             return $this->errorResponse('Le compte doit avoir un solde nul pour être supprimé.', 400);
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

             // Retourner la réponse dans le format demandé avec headers de rate limit
             $response = response()->json([
                 'success' => true,
                 'message' => 'Compte bancaire supprimé avec succès',
                 'timestamp' => now()->toISOString()
             ]);

             // Ajouter les headers de rate limit
             $response->header('X-Rate-Limit-Remaining', 9); // Exemple
             $response->header('X-Rate-Limit-Reset', time() + 3600); // Exemple

             return $response;
         } catch (\Exception $e) {
             Log::error('Erreur lors de la suppression du compte bancaire via API', [
                 'numero_compte' => $compte_bancaire->numero_compte,
                 'error' => $e->getMessage(),
             ]);

             return $this->errorResponse('Erreur lors de la suppression du compte.', 500);
         }
     }
}