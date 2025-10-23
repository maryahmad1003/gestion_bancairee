<?php

namespace App\Jobs;

use App\Models\CompteBancaire;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DebloquerComptesEpargne implements ShouldQueue
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
        Log::info('Début du job de déblocage automatique des comptes épargne');

        // Récupérer tous les comptes épargne bloqués dont la date de fin de blocage est dépassée
        $comptesBloques = CompteBancaire::epargne()
            ->bloques()
            ->where('date_fin_blocage', '<=', now())
            ->get();

        $comptesDebloques = 0;

        foreach ($comptesBloques as $compte) {
            try {
                $compte->debloquer();
                $comptesDebloques++;

                Log::info("Compte épargne débloqué automatiquement", [
                    'numero_compte' => $compte->numero_compte,
                    'client_id' => $compte->client_id,
                    'date_fin_blocage' => $compte->date_fin_blocage,
                ]);
            } catch (\Exception $e) {
                Log::error("Erreur lors du déblocage automatique du compte épargne", [
                    'numero_compte' => $compte->numero_compte,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Job de déblocage terminé", [
            'comptes_debloques' => $comptesDebloques,
            'total_comptes_traites' => $comptesBloques->count(),
        ]);
    }
}
