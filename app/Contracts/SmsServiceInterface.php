<?php

namespace App\Contracts;

interface SmsServiceInterface
{
    /**
     * Envoyer un SMS
     *
     * @param string $to Numéro de téléphone destinataire
     * @param string $message Contenu du message
     * @return bool Succès de l'envoi
     */
    public function send(string $to, string $message): bool;

    /**
     * Vérifier le solde du compte SMS
     *
     * @return float Solde restant
     */
    public function getBalance(): float;
}