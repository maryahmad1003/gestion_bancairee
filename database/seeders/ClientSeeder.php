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

        // Les utilisateurs sont maintenant créés dans CustomUserSeeder
    }

}
