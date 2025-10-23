<?php

namespace App\Events;

use App\Models\Client;
use App\Models\CompteBancaire;
use App\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientNotificationEvent
{
    use Dispatchable, SerializesModels;

    public Client $client;
    public CompteBancaire $compte;
    public Transaction $transaction;
    public string $type; // 'creation_compte' ou 'transaction'

    // Pour création de compte
    public ?string $password = null;
    public ?string $code = null;

    /**
     * Create a new event instance pour création de compte.
     */
    public function __construct(Client $client, CompteBancaire $compte, string $password, string $code)
    {
        $this->client = $client;
        $this->compte = $compte;
        $this->password = $password;
        $this->code = $code;
        $this->type = 'creation_compte';
    }

    /**
     * Create a new event instance pour transaction.
     */
    public static function forTransaction(Transaction $transaction): self
    {
        $event = new self(
            $transaction->compteBancaire->client,
            $transaction->compteBancaire,
            '', // pas utilisé pour transaction
            ''  // pas utilisé pour transaction
        );
        $event->transaction = $transaction;
        $event->type = 'transaction';
        $event->password = null;
        $event->code = null;

        return $event;
    }
}
