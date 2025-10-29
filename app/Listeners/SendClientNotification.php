<?php

namespace App\Listeners;

use App\Events\CompteBancaireCreated;
use App\Services\LogSmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendClientNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected LogSmsService $smsService;

    /**
     * Create the event listener.
     */
    public function __construct(LogSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Handle the event.
     */
    public function handle(CompteBancaireCreated $event): void
    {
        $compte = $event->compteBancaire;
        $numeroCompte = $event->numeroCompte;
        $client = $compte->client;

        // Envoyer l'email
        $this->sendEmailNotification($client, $compte, $numeroCompte);

        // Envoyer le SMS
        $this->sendSmsNotification($client, $numeroCompte);
    }

    /**
     * Envoyer la notification par email
     */
    private function sendEmailNotification($client, $compte, $numeroCompte): void
    {
        try {
            Mail::raw(
                $this->getEmailContent($client, $compte, $numeroCompte),
                function ($message) use ($client) {
                    $message->to($client->email)
                            ->subject('Création de votre compte bancaire - ' . config('app.name'));
                }
            );
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
        }
    }

    /**
     * Envoyer la notification par SMS
     */
    private function sendSmsNotification($client, $numeroCompte): void
    {
        try {
            $message = "Votre compte bancaire a été créé avec succès. Numéro de compte: {$numeroCompte}. Conservez ce numéro précieusement.";

            $this->smsService->send($client->telephone, $message);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du SMS: ' . $e->getMessage());
        }
    }

    /**
     * Générer le contenu de l'email
     */
    private function getEmailContent($client, $compte, $numeroCompte): string
    {
        return "
Cher(e) {$client->titulaire},

Votre compte bancaire a été créé avec succès !

Détails du compte :
- Numéro de compte : {$numeroCompte}
- Type : {$compte->type}
- Solde initial : " . number_format($compte->solde_initial, 0, ',', ' ') . " {$compte->devise}
- Solde actuel : " . number_format($compte->solde, 0, ',', ' ') . " {$compte->devise}
- Date de création : " . $compte->created_at->format('d/m/Y H:i') . "

Veuillez conserver ce numéro de compte précieusement. Il vous sera demandé pour toutes vos opérations bancaires.

Cordialement,
L'équipe " . config('app.name') . "
        ";
    }
}
