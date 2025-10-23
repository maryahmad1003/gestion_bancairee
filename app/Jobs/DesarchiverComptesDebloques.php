<?php

namespace App\Jobs;

use App\Models\CompteBancaire;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DesarchiverComptesDebloques implements ShouldQueue
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
        Log::info('Début du job de désarchivage automatique des comptes débloqués');

        // Récupérer tous les comptes épargne archivés qui ne sont plus bloqués
        $comptesArchivesDebloques = CompteBancaire::epargne()
            ->archives()
            ->where(function ($query) {
                $query->where('est_bloque', false)
                      ->orWhereNull('date_fin_blocage')
                      ->orWhere('date_fin_blocage', '<=', now());
            })
            ->get();

        $comptesDesarchives = 0;

        foreach ($comptesArchivesDebloques as $compte) {
            try {
                $compte->desarchiver();
                $comptesDesarchives++;

                Log::info("Compte épargne archivé désarchivé automatiquement", [
                    'numero_compte' => $compte->numero_compte,
                    'client_id' => $compte->client_id,
                    'date_archivage' => $compte->date_archivage,
                    'est_bloque' => $compte->est_bloque,
                    'date_fin_blocage' => $compte->date_fin_blocage,
                ]);
            } catch (\Exception $e) {
                Log::error("Erreur lors du désarchivage automatique du compte épargne", [
                    'numero_compte' => $compte->numero_compte,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Job de désarchivage terminé", [
            'comptes_desarchives' => $comptesDesarchives,
            'total_comptes_traites' => $comptesArchivesDebloques->count(),
        ]);
    }
}
