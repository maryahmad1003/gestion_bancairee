<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des clients de test
        \App\Models\Client::factory(50)->create();

        // Créer quelques clients spécifiques pour les tests
        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-TEST001',
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean.dupont@test.com',
            'telephone' => '+33123456789',
            'date_naissance' => '1980-05-15',
            'ville' => 'Paris',
            'statut' => 'actif',
        ]);

        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-TEST002',
            'nom' => 'Martin',
            'prenom' => 'Marie',
            'email' => 'marie.martin@test.com',
            'telephone' => '+33987654321',
            'date_naissance' => '1992-08-22',
            'ville' => 'Lyon',
            'statut' => 'actif',
        ]);

        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-TEST003',
            'nom' => 'Dubois',
            'prenom' => 'Pierre',
            'email' => 'pierre.dubois@test.com',
            'telephone' => '+33555666777',
            'date_naissance' => '1975-12-03',
            'ville' => 'Marseille',
            'statut' => 'suspendu',
        ]);

        // Créer des utilisateurs pour l'authentification
        $this->createUsers();
    }

    /**
     * Créer des utilisateurs de test avec différents rôles
     */
    private function createUsers(): void
    {
        // Administrateur
        \App\Models\User::create([
            'name' => 'Admin Banque',
            'email' => 'admin@banque.example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'admin',
            'permissions' => json_encode([
                'manage_clients',
                'manage_accounts',
                'manage_transactions',
                'block_accounts',
                'view_reports',
                'manage_users'
            ]),
        ]);

        // Client 1
        \App\Models\User::create([
            'name' => 'Jean Dupont',
            'email' => 'jean.dupont@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'client',
            'client_id' => 'CLI-TEST001',
            'permissions' => json_encode([
                'view_own_accounts',
                'view_own_transactions',
                'create_transactions'
            ]),
        ]);

        // Client 2
        \App\Models\User::create([
            'name' => 'Marie Martin',
            'email' => 'marie.martin@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'client',
            'client_id' => 'CLI-TEST002',
            'permissions' => json_encode([
                'view_own_accounts',
                'view_own_transactions',
                'create_transactions'
            ]),
        ]);

        // Gestionnaire
        \App\Models\User::create([
            'name' => 'Gestionnaire Compte',
            'email' => 'gestionnaire@banque.example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'gestionnaire',
            'permissions' => json_encode([
                'manage_clients',
                'manage_accounts',
                'view_reports',
                'block_accounts'
            ]),
        ]);
    }
}
