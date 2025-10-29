<?php

namespace App\Events;

use App\Models\CompteBancaire;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteBancaireCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CompteBancaire $compteBancaire;
    public string $numeroCompte;

    /**
     * Create a new event instance.
     */
    public function __construct(CompteBancaire $compteBancaire, string $numeroCompte)
    {
        $this->compteBancaire = $compteBancaire;
        $this->numeroCompte = $numeroCompte;
    }
}