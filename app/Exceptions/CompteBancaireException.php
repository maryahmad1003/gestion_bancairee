<?php

namespace App\Exceptions;

use App\Exceptions\ApiException;

class CompteBancaireException extends ApiException
{
    public static function compteNonTrouve(string $numero = null): self
    {
        $message = $numero
            ? "Le compte bancaire avec le numéro {$numero} n'a pas été trouvé."
            : "Le compte bancaire demandé n'a pas été trouvé.";

        return new self($message, 404);
    }

    public static function accesNonAutorise(): self
    {
        return new self('Accès non autorisé à ce compte bancaire.', 403);
    }

    public static function compteBloque(): self
    {
        return new self('Ce compte bancaire est bloqué.', 423);
    }

    public static function soldeInsuffisant(): self
    {
        return new self('Solde insuffisant pour effectuer cette opération.', 400);
    }

    public static function typeCompteNonSupporte(): self
    {
        return new self('Type de compte non supporté.', 400);
    }
}