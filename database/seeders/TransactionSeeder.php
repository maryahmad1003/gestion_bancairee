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
        // Créer des transactions pour les comptes existants avec soldes spécifiques
        $comptes = \App\Models\CompteBancaire::all();
        $soldesDesires = [20000, 30000, 10000, 200000, 100000, 30000, 10000, 60000, 1000000, 290000];

        foreach ($comptes as $index => $compte) {
            // Utiliser le solde désiré pour ce compte (cyclique si plus de comptes que de soldes)
            $soldeDesire = $soldesDesires[$index % count($soldesDesires)];

            // Créer une transaction de crédit pour atteindre exactement le solde désiré
            \App\Models\Transaction::factory()->create([
                'compte_bancaire_id' => $compte->id,
                'type_transaction' => 'credit',
                'montant' => $soldeDesire,
                'libelle' => 'Solde initial',
                'statut' => 'validee',
                'date_transaction' => now()->subDays(30),
            ]);

            // Ajouter quelques transactions supplémentaires pour la variété
            $nombreTransactionsSupplementaires = rand(3, 8);
            for ($i = 0; $i < $nombreTransactionsSupplementaires; $i++) {
                \App\Models\Transaction::factory()->create([
                    'compte_bancaire_id' => $compte->id,
                ]);
            }
        }

        // S'assurer que les 10 premiers comptes ont exactement les soldes désirés
        $comptesLimites = $comptes->take(10);
        foreach ($comptesLimites as $index => $compte) {
            $soldeDesire = $soldesDesires[$index];

            // Supprimer toutes les transactions existantes pour ce compte
            $compte->transactions()->delete();

            // Créer uniquement la transaction de solde initial
            \App\Models\Transaction::factory()->create([
                'compte_bancaire_id' => $compte->id,
                'type_transaction' => 'credit',
                'montant' => $soldeDesire,
                'libelle' => 'Solde initial',
                'statut' => 'validee',
                'date_transaction' => now()->subDays(30),
            ]);
        }

        // Créer quelques transactions spécifiques pour les tests
        $compteTest1 = \App\Models\CompteBancaire::where('numero_compte', 'CB-TEST001')->first();
        if ($compteTest1) {
            // Ajouter des crédits importants pour le compte de test
            \App\Models\Transaction::factory()->create([
                'numero_transaction' => 'TXN-TEST001',
                'compte_bancaire_id' => $compteTest1->id,
                'type_transaction' => 'credit',
                'montant' => 50000.00, // Solde initial important
                'libelle' => 'Dépôt initial',
                'statut' => 'validee',
                'date_transaction' => now()->subDays(30),
            ]);

            \App\Models\Transaction::factory()->create([
                'numero_transaction' => 'TXN-TEST002',
                'compte_bancaire_id' => $compteTest1->id,
                'type_transaction' => 'credit',
                'montant' => 25000.00,
                'libelle' => 'Virement salaire',
                'statut' => 'validee',
                'date_transaction' => now()->subDays(15),
            ]);

            \App\Models\Transaction::factory()->create([
                'numero_transaction' => 'TXN-TEST003',
                'compte_bancaire_id' => $compteTest1->id,
                'type_transaction' => 'debit',
                'montant' => 150.00,
                'libelle' => 'Paiement carte bancaire',
                'statut' => 'validee',
                'date_transaction' => now()->subDays(2),
            ]);
        }

        $compteTest2 = \App\Models\CompteBancaire::where('numero_compte', 'CB-TEST002')->first();
        if ($compteTest2) {
            // Ajouter des crédits importants pour le compte épargne de test
            \App\Models\Transaction::factory()->create([
                'numero_transaction' => 'TXN-TEST004',
                'compte_bancaire_id' => $compteTest2->id,
                'type_transaction' => 'credit',
                'montant' => 75000.00, // Solde épargne important
                'libelle' => 'Dépôt épargne initial',
                'statut' => 'validee',
                'date_transaction' => now()->subDays(60),
            ]);

            \App\Models\Transaction::factory()->create([
                'numero_transaction' => 'TXN-TEST005',
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
