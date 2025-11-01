<?php

namespace App\Models\Passport;

use Laravel\Passport\PersonalAccessClient as PassportPersonalAccessClient;
use Illuminate\Support\Str;

class PersonalAccessClient extends PassportPersonalAccessClient
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'oauth_personal_access_clients';

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }
}