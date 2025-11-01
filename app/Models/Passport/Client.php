<?php


namespace App\Models\Passport;

use Laravel\Passport\Client as PassportClient;
use Illuminate\Support\Str;

class Client extends PassportClient
{
    public $incrementing = false;
    protected $keyType = 'string';

    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }
}