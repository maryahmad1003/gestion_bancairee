<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des transactions pour les comptes existants
        $comptes = \App\Models\CompteBancaire::all();

        foreach ($comptes as $compte) {
            // Chaque compte a entre 5 et 15 transactions
            $nombreTransactions = rand(5, 15);

            for ($i = 0; $i < $nombreTransactions; $i++) {
                \App\Models\Transaction::factory()->create([
                    'compte_bancaire_id' => $compte->id,
                ]);
            }
        }

        // Créer quelques transactions spécifiques pour les tests
        $compteTest1 = \App\Models\CompteBancaire::where('numero_compte', 'CB-TEST001')->first();
        if ($compteTest1) {
            \App\Models\Transaction::factory()->create([
                'numero_transaction' => 'TXN-TEST001',
                'compte_bancaire_id' => $compteTest1->id,
                'type_transaction' => 'debit',
                'montant' => 150.00,
                'libelle' => 'Paiement carte bancaire',
                'statut' => 'validee',
                'date_transaction' => now()->subDays(2),
            ]);

            \App\Models\Transaction::factory()->create([
                'numero_transaction' => 'TXN-TEST002',
                'compte_bancaire_id' => $compteTest1->id,
                'type_transaction' => 'credit',
                'montant' => 2500.00,
                'libelle' => 'Virement salaire',
                'statut' => 'validee',
                'date_transaction' => now()->subDays(1),
            ]);
        }

        $compteTest2 = \App\Models\CompteBancaire::where('numero_compte', 'CB-TEST002')->first();
        if ($compteTest2) {
            \App\Models\Transaction::factory()->create([
                'numero_transaction' => 'TXN-TEST003',
                'compte_bancaire_id' => $compteTest2->id,
                'type_transaction' => 'credit',
                'montant' => 500.00,
                'libelle' => 'Dépôt espèces',
                'statut' => 'validee',
                'date_transaction' => now()->subDays(3),
            ]);
        }

        // Créer un virement entre deux comptes
        $compteSource = \App\Models\CompteBancaire::where('numero_compte', 'CB-TEST001')->first();
        $compteDest = \App\Models\CompteBancaire::where('numero_compte', 'CB-TEST002')->first();

        if ($compteSource && $compteDest) {
            \App\Models\Transaction::factory()->create([
                'numero_transaction' => 'TXN-VIR001',
                'compte_bancaire_id' => $compteSource->id,
                'compte_bancaire_destinataire_id' => $compteDest->id,
                'type_transaction' => 'virement_emis',
                'montant' => 300.00,
                'libelle' => 'Virement vers épargne',
                'statut' => 'validee',
                'date_transaction' => now()->subDays(5),
            ]);

            \App\Models\Transaction::factory()->create([
                'numero_transaction' => 'TXN-VIR002',
                'compte_bancaire_id' => $compteDest->id,
                'compte_bancaire_destinataire_id' => $compteSource->id,
                'type_transaction' => 'virement_recus',
                'montant' => 300.00,
                'libelle' => 'Virement depuis compte courant',
                'statut' => 'validee',
                'date_transaction' => now()->subDays(5),
            ]);
        }
    }
}
