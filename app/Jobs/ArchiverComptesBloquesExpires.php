<?php

namespace App\Jobs;

use App\Models\CompteBancaire;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ArchiverComptesBloquesExpires implements ShouldQueue
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
        Log::info('Début du job d\'archivage automatique des comptes bloqués expirés');

        // Récupérer tous les comptes épargne bloqués dont la date de début de blocage est dépassée
        // et qui ne sont pas encore archivés
        $comptesBloquesExpires = CompteBancaire::epargne()
            ->bloques()
            ->nonArchives()
            ->where('date_debut_blocage', '<=', now()->subDays(30)) // Plus de 30 jours bloqués
            ->get();

        $comptesArchives = 0;

        foreach ($comptesBloquesExpires as $compte) {
            try {
                $compte->archiver();
                $comptesArchives++;

                Log::info("Compte épargne bloqué expiré archivé automatiquement", [
                    'numero_compte' => $compte->numero_compte,
                    'client_id' => $compte->client_id,
                    'date_debut_blocage' => $compte->date_debut_blocage,
                    'duree_blocage' => $compte->duree_blocage_jours,
                    'date_archivage' => $compte->date_archivage,
                ]);
            } catch (\Exception $e) {
                Log::error("Erreur lors de l'archivage automatique du compte épargne bloqué expiré", [
                    'numero_compte' => $compte->numero_compte,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Job d'archivage terminé", [
            'comptes_archives' => $comptesArchives,
            'total_comptes_traites' => $comptesBloquesExpires->count(),
        ]);
    }
}
