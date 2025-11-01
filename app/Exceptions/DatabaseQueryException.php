<?php

namespace App\Exceptions;
use App\Traits\ApiResponseTrait;

use Exception;

class DatabaseQueryException extends Exception
{
    use ApiResponseTrait;

    public function render($request)
    {
        return $this->errorResponse('Erreur lors de la récupération des comptes',    500);
    }
}
