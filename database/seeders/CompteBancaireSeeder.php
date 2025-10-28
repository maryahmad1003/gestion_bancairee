<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompteBancaireSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des comptes bancaires pour les clients existants
        $clients = \App\Models\Client::all();

        foreach ($clients as $client) {
            // Chaque client a entre 1 et 3 comptes
            $nombreComptes = rand(1, 3);

            for ($i = 0; $i < $nombreComptes; $i++) {
                \App\Models\CompteBancaire::factory()->create([
                    'client_id' => $client->id,
                ]);
            }
        }

        // Créer quelques comptes spécifiques pour les tests
        $clientTest1 = \App\Models\Client::where('numero_client', 'CLI-TEST001')->first();
        if ($clientTest1) {
            \App\Models\CompteBancaire::factory()->create([
                'numero_compte' => 'CB-TEST001',
                'client_id' => $clientTest1->id,
                'type_compte' => 'cheque',
                'statut' => 'actif',
                'solde_initial' => 30000.00,
            ]);

            \App\Models\CompteBancaire::factory()->create([
                'numero_compte' => 'CB-TEST002',
                'client_id' => $clientTest1->id,
                'type_compte' => 'epargne',
                'statut' => 'actif',
                'solde_initial' => 50000.00,
            ]);
        }

        $clientTest2 = \App\Models\Client::where('numero_client', 'CLI-TEST002')->first();
        if ($clientTest2) {
            \App\Models\CompteBancaire::factory()->create([
                'numero_compte' => 'CB-TEST003',
                'client_id' => $clientTest2->id,
                'type_compte' => 'cheque',
                'statut' => 'actif',
                'solde_initial' => 25000.00,
            ]);
        }
    }
}
