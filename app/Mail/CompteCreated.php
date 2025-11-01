<?php

namespace App\Mail;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompteCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Compte $compte
    ) {}

    public function build()
    {
        return $this->markdown('emails.compte.created')
                    ->subject('Votre compte bancaire a été créé');
    }
}