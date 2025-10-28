<?php

namespace App\Jobs;

use App\Models\CompteBancaire;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncDailyAccountsToCloud implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Définir le fuseau horaire du Sénégal
        $senegalTimezone = 'Africa/Dakar';

        // Obtenir la date d'aujourd'hui en heure du Sénégal
        $today = Carbon::now($senegalTimezone)->startOfDay();
        $tomorrow = Carbon::now($senegalTimezone)->endOfDay();

        // Convertir en UTC pour la requête de base de données
        $todayUtc = $today->utc();
        $tomorrowUtc = $tomorrow->utc();

        // Log de démarrage
        Log::info('🚀 Démarrage de la synchronisation PostgreSQL local → Railway PostgreSQL', [
            'date_senegal' => $today->toDateString(),
            'timezone' => $senegalTimezone,
            'source' => 'postgresql_local',
            'destination' => 'railway_postgresql',
            'today_utc' => $todayUtc->toISOString(),
            'tomorrow_utc' => $tomorrowUtc->toISOString(),
        ]);

        try {
            // Récupérer tous les comptes créés aujourd'hui (heure du Sénégal)
            $dailyAccounts = CompteBancaire::with('client')
                ->whereBetween('created_at', [$todayUtc, $tomorrowUtc])
                ->whereNull('deleted_at') // Exclure les comptes supprimés
                ->get();

            if ($dailyAccounts->isEmpty()) {
                Log::info('📭 Aucun compte créé aujourd\'hui à synchroniser PostgreSQL → Railway');
                return;
            }

            // Transformer les données pour l'API cloud
            $accountsData = $dailyAccounts->map(function ($compte) {
                return [
                    'id' => $compte->id,
                    'numero_compte' => $compte->numero_compte,
                    'type_compte' => $compte->type_compte,
                    'devise' => $compte->devise,
                    'solde_initial' => $compte->solde_initial,
                    'solde' => $compte->solde,
                    'decouvert_autorise' => $compte->decouvert_autorise,
                    'date_ouverture' => $compte->date_ouverture,
                    'statut' => $compte->statut,
                    'client' => [
                        'id' => $compte->client->id,
                        'numero_client' => $compte->client->numero_client,
                        'nom' => $compte->client->nom,
                        'prenom' => $compte->client->prenom,
                        'email' => $compte->client->email,
                        'telephone' => $compte->client->telephone,
                        'date_naissance' => $compte->client->date_naissance,
                        'adresse' => $compte->client->adresse,
                        'ville' => $compte->client->ville,
                        'code_postal' => $compte->client->code_postal,
                        'pays' => $compte->client->pays,
                    ],
                    'created_at' => $compte->created_at->toISOString(),
                    'updated_at' => $compte->updated_at->toISOString(),
                ];
            });

            // Envoyer les données vers Railway (PostgreSQL cloud)
            $response = Http::timeout(60)
                ->retry(3, 100) // Retry 3 fois avec 100ms de délai
                ->post(config('services.railway_api.sync_accounts_url'), [
                    'accounts' => $accountsData->toArray(),
                    'sync_date' => $today->toDateString(),
                    'timezone' => $senegalTimezone,
                    'source' => 'postgresql_local',
                    'destination' => 'railway_postgresql',
                    'api_key' => config('services.railway_api.key'),
                ]);

            if ($response->successful()) {
                Log::info('✅ Synchronisation PostgreSQL → Railway réussie', [
                    'source' => 'postgresql_local',
                    'destination' => 'railway_postgresql',
                    'nombre_comptes' => $dailyAccounts->count(),
                    'date_sync' => $today->toDateString(),
                    'response_status' => $response->status(),
                ]);

                // Marquer les comptes comme synchronisés (optionnel)
                // Vous pouvez ajouter une colonne 'synced_at' si nécessaire
                // $dailyAccounts->each(function ($compte) {
                //     $compte->update(['synced_at' => now()]);
                // });

            } else {
                Log::error('❌ Échec de la synchronisation PostgreSQL → Railway', [
                    'source' => 'postgresql_local',
                    'destination' => 'railway_postgresql',
                    'nombre_comptes' => $dailyAccounts->count(),
                    'date_sync' => $today->toDateString(),
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                // Relancer le job en cas d'échec
                throw new \Exception('Échec de synchronisation vers le cloud: ' . $response->body());
            }

        } catch (\Exception $e) {
            Log::error('💥 Erreur lors de la synchronisation PostgreSQL → Railway', [
                'source' => 'postgresql_local',
                'destination' => 'railway_postgresql',
                'error' => $e->getMessage(),
                'date_sync' => $today->toDateString(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Relancer le job avec un délai
            $this->release(300); // 5 minutes
            throw $e;
        }
    }

    /**
     * Définir les tags pour le job
     */
    public function tags(): array
    {
        return ['sync', 'cloud', 'daily-accounts'];
    }
}
