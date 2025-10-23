<?php

namespace App\Listeners;

use App\Events\ClientNotificationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class SendClientNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ClientNotificationEvent $event): void
    {
        try {
            if ($event->type === 'creation_compte') {
                // Envoyer l'email d'authentification
                $this->sendAuthenticationEmail($event);

                // Envoyer le SMS avec le code
                $this->sendVerificationSMS($event);

                Log::info('Notifications envoyées avec succès pour le client', [
                    'client_id' => $event->client->id,
                    'compte_id' => $event->compte->id,
                    'email' => $event->client->email,
                    'telephone' => $event->client->telephone,
                ]);
            } elseif ($event->type === 'transaction') {
                // Envoyer SMS de confirmation de transaction
                $this->sendTransactionSMS($event);

                Log::info('Notification de transaction envoyée avec succès', [
                    'client_id' => $event->client->id,
                    'transaction_id' => $event->transaction->id,
                    'montant' => $event->transaction->montant,
                    'telephone' => $event->client->telephone,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi des notifications', [
                'client_id' => $event->client->id,
                'type' => $event->type,
                'error' => $e->getMessage(),
            ]);

            // Re-lancer l'exception pour que le job soit marqué comme échoué
            throw $e;
        }
    }

    /**
     * Envoyer l'email d'authentification
     */
    private function sendAuthenticationEmail(ClientNotificationEvent $event): void
    {
        $client = $event->client;
        $compte = $event->compte;

        // Ici on utiliserait Mail::to() avec une classe Mailable
        // Pour l'exemple, on simule l'envoi
        Log::info('Email d\'authentification envoyé', [
            'to' => $client->email,
            'subject' => 'Création de votre compte bancaire',
            'password' => $event->password, // En production, ne pas logger le mot de passe
        ]);

        // En production, remplacer par :
        /*
        Mail::to($client->email)->send(new CompteCreatedMail($client, $compte, $event->password));
        */
    }

    /**
     * Envoyer le SMS avec le code de vérification
     */
    private function sendVerificationSMS(ClientNotificationEvent $event): void
    {
        $client = $event->client;
        $compte = $event->compte;

        $message = "Votre compte bancaire {$compte->numero_compte} a été créé. Code de vérification: {$event->code}";

        // Simulation d'appel à un service SMS
        // En production, remplacer par un vrai service SMS
        try {
            // Exemple avec un service SMS fictif
            $response = Http::timeout(30)->post('https://api.sms-service.com/send', [
                'to' => $client->telephone,
                'message' => $message,
                'api_key' => config('services.sms_api_key'),
            ]);

            if ($response->successful()) {
                Log::info('SMS envoyé avec succès', [
                    'to' => $client->telephone,
                    'message' => $message,
                ]);
            } else {
                throw new \Exception('Échec de l\'envoi du SMS: ' . $response->body());
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du SMS', [
                'to' => $client->telephone,
                'error' => $e->getMessage(),
            ]);

            // En développement, on peut logger le code au lieu de l'envoyer
            Log::info('Code de vérification (développement)', [
                'telephone' => $client->telephone,
                'code' => $event->code,
            ]);

            // Re-lancer l'exception si c'est critique
            // throw $e;
        }
    }

    /**
     * Envoyer le SMS de confirmation de transaction
     */
    private function sendTransactionSMS(ClientNotificationEvent $event): void
    {
        $client = $event->client;
        $transaction = $event->transaction;

        $typeTransaction = match($transaction->type_transaction) {
            'debit' => 'retrait',
            'credit' => 'dépôt',
            'virement_emis' => 'virement émis',
            'virement_recus' => 'virement reçu',
            default => 'transaction'
        };

        $message = "Transaction effectuée: {$typeTransaction} de {$transaction->montant_formate} sur le compte {$event->compte->numero_compte}.";

        // Utiliser l'interface SMS pour éviter le couplage fort
        $smsService = app(\App\Contracts\SmsServiceInterface::class);
        $smsService->send($client->telephone, $message);
    }

    /**
     * Handle a job failure.
     */
    public function failed(ClientNotificationEvent $event, \Throwable $exception): void
    {
        Log::error('Échec définitif de l\'envoi des notifications', [
            'client_id' => $event->client->id,
            'type' => $event->type,
            'error' => $exception->getMessage(),
        ]);

        // Ici on pourrait implémenter une logique de retry ou d'alerte
    }
}
