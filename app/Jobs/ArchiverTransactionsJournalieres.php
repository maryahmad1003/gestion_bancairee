<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArchiverTransactionsJournalieres implements ShouldQueue
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
        try {
            Log::info('Début de l\'archivage des transactions journalières');

            // Récupérer les transactions du jour non archivées
            $transactionsDuJour = Transaction::whereDate('created_at', today())
                ->where('est_archive', false)
                ->get();

            if ($transactionsDuJour->isEmpty()) {
                Log::info('Aucune transaction à archiver pour aujourd\'hui');
                return;
            }

            // Utiliser une connexion différente pour Neon (sans couplage fort)
            $neonConnection = $this->getNeonConnection();

            $databaseService = $this->getDatabaseService();

            DB::transaction(function () use ($transactionsDuJour, $databaseService) {
                foreach ($transactionsDuJour->chunk(100) as $chunk) {
                    // Préparer les données pour l'archivage
                    $dataToArchive = $chunk->map(function ($transaction) {
                        return [
                            'id' => $transaction->id,
                            'numero_transaction' => $transaction->numero_transaction,
                            'compte_bancaire_id' => $transaction->compte_bancaire_id,
                            'compte_bancaire_destinataire_id' => $transaction->compte_bancaire_destinataire_id,
                            'type_transaction' => $transaction->type_transaction,
                            'montant' => $transaction->montant,
                            'devise' => $transaction->devise,
                            'libelle' => $transaction->libelle,
                            'description' => $transaction->description,
                            'date_transaction' => $transaction->date_transaction,
                            'statut' => $transaction->statut,
                            'reference_externe' => $transaction->reference_externe,
                            'metadata' => json_encode($transaction->metadata),
                            'created_at' => $transaction->created_at,
                            'updated_at' => $transaction->updated_at,
                            'date_archivage' => now(),
                        ];
                    })->toArray();

                    // Archiver via le service (sans couplage fort)
                    $databaseService->archive($dataToArchive, 'transactions_archivees');

                    // Marquer comme archivé dans la base locale
                    $chunk->each(function ($transaction) {
                        $transaction->update([
                            'est_archive' => true,
                            'date_archivage' => now(),
                        ]);
                    });
                }
            });

            Log::info('Archivage terminé avec succès', [
                'nombre_transactions' => $transactionsDuJour->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'archivage des transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Obtenir le service de base de données externe (sans couplage fort)
     */
    private function getDatabaseService(): \App\Contracts\DatabaseServiceInterface
    {
        return app(\App\Contracts\DatabaseServiceInterface::class);
    }
}