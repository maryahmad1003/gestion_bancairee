<?php

namespace App\Console\Commands;

use App\Models\CompteBancaire;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BloquerCompteEpargne extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'banque:bloquer-compte-epargne
                            {numero_compte : Le numéro du compte épargne à bloquer}
                            {duree : Nombre de jours de blocage}
                            {--motif= : Motif du blocage (optionnel)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bloquer un compte épargne actif pour une durée déterminée';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $numeroCompte = $this->argument('numero_compte');
        $duree = (int) $this->argument('duree');
        $motif = $this->option('motif');

        // Validation de la durée
        if ($duree <= 0) {
            $this->error('La durée de blocage doit être supérieure à 0 jour.');
            return Command::FAILURE;
        }

        // Recherche du compte
        $compte = CompteBancaire::numero($numeroCompte)->first();

        if (!$compte) {
            $this->error("Aucun compte trouvé avec le numéro : {$numeroCompte}");
            return Command::FAILURE;
        }

        // Vérification que c'est un compte épargne
        if ($compte->type_compte !== 'epargne') {
            $this->error("Le compte {$numeroCompte} n'est pas un compte épargne.");
            return Command::FAILURE;
        }

        // Vérification que le compte est actif
        if ($compte->statut !== 'actif') {
            $this->error("Le compte {$numeroCompte} n'est pas actif (statut: {$compte->statut}).");
            return Command::FAILURE;
        }

        // Vérification que le compte n'est pas déjà bloqué
        if ($compte->est_bloque) {
            $this->error("Le compte {$numeroCompte} est déjà bloqué jusqu'au {$compte->date_fin_blocage->format('d/m/Y H:i')}.");
            return Command::FAILURE;
        }

        // Blocage du compte
        try {
            $result = $compte->bloquer($duree, $motif);

            if ($result) {
                $this->info("✅ Compte épargne bloqué avec succès !");
                $this->line("📋 Numéro de compte : {$numeroCompte}");
                $this->line("👤 Client : {$compte->client->nom_complet}");
                $this->line("📅 Date de début : {$compte->date_debut_blocage->format('d/m/Y H:i')}");
                $this->line("⏰ Durée : {$duree} jour(s)");
                $this->line("🏁 Date de fin : {$compte->date_fin_blocage->format('d/m/Y H:i')}");

                if ($motif) {
                    $this->line("📝 Motif : {$motif}");
                }

                Log::info("Compte épargne bloqué via commande Artisan", [
                    'numero_compte' => $numeroCompte,
                    'duree' => $duree,
                    'motif' => $motif,
                    'date_fin' => $compte->date_fin_blocage,
                ]);

                return Command::SUCCESS;
            } else {
                $this->error("❌ Échec du blocage du compte {$numeroCompte}.");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors du blocage : {$e->getMessage()}");
            Log::error("Erreur lors du blocage du compte épargne via commande", [
                'numero_compte' => $numeroCompte,
                'error' => $e->getMessage(),
            ]);
            return Command::FAILURE;
        }
    }
}
