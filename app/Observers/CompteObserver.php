<?php


namespace App\Observers;

use App\Models\Compte;
use App\Mail\CompteCreated;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class CompteObserver
{
    public function created(Compte $compte): void
    {
        try {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Log pour debug
            Log::info('Compte créé, envoi des notifications', [
                'compte_id' => $compte->id,
                'email' => $compte->user->email,
                'telephone' => $compte->user->telephone
            ]);

            // Envoi SMS via Twilio
            try {
                $twilioClient = new TwilioClient(
                    config('services.twilio.sid'),
                    config('services.twilio.token')
                );

                $twilioClient->messages->create(
                    '+221' . ltrim($compte->user->telephone, '0'),
                    [
                        'from' => config('services.twilio.phone'),
                        'body' => "Votre code de vérification est : {$code}"
                    ]
                );

                Log::info('SMS envoyé avec succès');
            } catch (\Exception $e) {
                Log::error('Erreur envoi SMS:', ['error' => $e->getMessage()]);
            }

            // Envoi Email
            try {
                Mail::to($compte->user->email)
                    ->send(new CompteCreated($compte));

                Log::info('Email envoyé avec succès');
            } catch (\Exception $e) {
                Log::error('Erreur envoi email:', ['error' => $e->getMessage()]);
            }

            // Sauvegarde du code
            $compte->update([
                'code_verification' => $code,
                'code_expire_at' => now()->addHours(24)
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur générale dans l\'Observer:', [
                'error' => $e->getMessage(),
                'compte_id' => $compte->id
            ]);
        }
    }
}