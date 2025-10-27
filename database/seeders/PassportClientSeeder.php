<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Laravel\Passport\Client;

class PassportClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un client pour l'application mobile
        Client::create([
            'name' => 'Banque Mobile App',
            'secret' => 'secret_mobile_app',
            'redirect' => 'http://localhost',
            'personal_access_client' => false,
            'password_client' => true,
            'revoked' => false,
        ]);

        // Créer un client pour l'application web
        Client::create([
            'name' => 'Banque Web App',
            'secret' => 'secret_web_app',
            'redirect' => 'http://localhost',
            'personal_access_client' => false,
            'password_client' => true,
            'revoked' => false,
        ]);

        // Créer un client pour les accès personnels
        Client::create([
            'name' => 'Personal Access Client',
            'secret' => 'personal_access_secret',
            'redirect' => 'http://localhost',
            'personal_access_client' => true,
            'password_client' => false,
            'revoked' => false,
        ]);
    }
}