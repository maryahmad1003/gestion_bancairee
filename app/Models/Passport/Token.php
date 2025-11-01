<?php

namespace App\Models\Passport;

use Laravel\Passport\Token as PassportToken;

class Token extends PassportToken
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'oauth_access_tokens';
}