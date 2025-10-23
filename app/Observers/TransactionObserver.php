<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Events\ClientNotificationEvent;
use Illuminate\Support\Facades\Log;

class TransactionObserver
{
    /**
     * Vérifier les règles métier avant la création d'une transaction
     */
    public function creating(Transaction $transaction): bool
    {
        // Vérifier la disponibilité du solde pour les débits et virements émis
        if (in_array($transaction->type_transaction, ['debit', 'virement_emis'])) {
            $compte = $transaction->compteBancaire;

            if (!$compte) {
                Log::error('Transaction rejetée : compte bancaire non trouvé', [
                    'transaction_id' => $transaction->id,
                    'compte_id' => $transaction->compte_bancaire_id
                ]);
                return false;
            }

            $soldeDisponible = $compte->solde + $compte->decouvert_autorise;

            if ($soldeDisponible < $transaction->montant) {
                Log::warning('Transaction rejetée : solde insuffisant', [
                    'compte_id' => $compte->id,
                    'solde' => $compte->solde,
                    'decouvert' => $compte->decouvert_autorise,
                    'montant' => $transaction->montant
                ]);
                return false;
            }
        }

        // Vérifier que le compte n'est pas bloqué
        if ($transaction->compteBancaire && $transaction->compteBancaire->est_bloque) {
            Log::warning('Transaction rejetée : compte bloqué', [
                'compte_id' => $transaction->compte_bancaire_id
            ]);
            return false;
        }

        // Pour les virements, vérifier que le compte destinataire existe et n'est pas bloqué
        if ($transaction->type_transaction === 'virement_emis' && $transaction->compteDestinataire) {
            if ($transaction->compteDestinataire->est_bloque) {
                Log::warning('Transaction rejetée : compte destinataire bloqué', [
                    'compte_destinataire_id' => $transaction->compte_bancaire_destinataire_id
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Traiter après la création d'une transaction validée
     */
    public function created(Transaction $transaction): void
    {
        if ($transaction->statut === 'validee') {
            // Déclencher l'événement de notification SMS
            event(ClientNotificationEvent::forTransaction($transaction));
        }
    }

    /**
     * Traiter après la mise à jour d'une transaction
     */
    public function updated(Transaction $transaction): void
    {
        // Si la transaction passe à validée, envoyer la notification
        if ($transaction->wasChanged('statut') && $transaction->statut === 'validee') {
            event(ClientNotificationEvent::forTransaction($transaction));
        }
    }
}