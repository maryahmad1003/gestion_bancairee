<?php

namespace App\Services;

use App\Contracts\SmsServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TwilioSmsService implements SmsServiceInterface
{
    protected string $accountSid;
    protected string $authToken;
    protected string $fromNumber;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.sid');
        $this->authToken = config('services.twilio.token');
        $this->fromNumber = config('services.twilio.from');
    }

    /**
     * Envoyer un SMS via Twilio
     */
    public function send(string $to, string $message): bool
    {
        try {
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                    'From' => $this->fromNumber,
                    'To' => $to,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                Log::info('SMS envoyé via Twilio', [
                    'to' => $to,
                    'sid' => $response->json()['sid'] ?? null,
                ]);
                return true;
            } else {
                Log::error('Échec envoi SMS Twilio', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'envoi SMS Twilio', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Obtenir le solde du compte Twilio
     */
    public function getBalance(): float
    {
        try {
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Balance.json");

            if ($response->successful()) {
                return (float) $response->json()['balance'] ?? 0;
            }
        } catch (\Exception $e) {
            Log::error('Erreur récupération solde Twilio', [
                'error' => $e->getMessage(),
            ]);
        }

        return 0;
    }
}