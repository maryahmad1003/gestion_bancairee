<?php

namespace App\Services;

use App\Contracts\SmsServiceInterface;
use Illuminate\Support\Facades\Log;

class LogSmsService implements SmsServiceInterface
{
    /**
     * Simuler l'envoi d'un SMS en le loggant seulement
     * Utile pour le développement et les tests
     */
    public function send(string $to, string $message): bool
    {
        Log::info('SMS simulé (LogSmsService)', [
            'to' => $to,
            'message' => $message,
        ]);

        return true;
    }

    /**
     * Retourner un solde fictif
     */
    public function getBalance(): float
    {
        return 100.0; // Solde fictif pour les tests
    }
}