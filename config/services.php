<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Railway PostgreSQL API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour la synchronisation des comptes vers Railway PostgreSQL
    | Les comptes créés localement (PostgreSQL) sont synchronisés vers Railway
    |
    */
    'railway_api' => [
        'sync_accounts_url' => env('RAILWAY_API_SYNC_ACCOUNTS_URL', 'https://your-railway-app.railway.app/api/sync/accounts'),
        'key' => env('RAILWAY_API_KEY'),
        'timeout' => env('RAILWAY_API_TIMEOUT', 60),
        'retry_attempts' => env('RAILWAY_API_RETRY_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Neon Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour la base de données Neon utilisée pour les comptes épargne archivés
    |
    */
    'neon' => [
        'url' => env('NEON_DATABASE_URL'),
        'api_key' => env('NEON_API_KEY'),
    ],

];
