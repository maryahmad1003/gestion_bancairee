<?php

namespace App\Exceptions;
use App\Traits\ApiResponseTrait;

use Exception;

class CreateFailedException extends Exception
{
    use ApiResponseTrait;


    public function render($request)
    {
        return $this->errorResponse('Erreur lors de la création du compte', 500);
    }
}
