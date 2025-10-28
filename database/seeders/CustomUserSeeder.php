<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un administrateur par défaut
        \App\Models\User::factory()->create([
            'numero_user' => 'USR-ADMIN001',
            'name' => 'Administrateur Système',
            'email' => 'admin@banque.com',
            'role' => 'admin',
            'statut' => 'actif',
            'password' => Hash::make('password'),
        ]);

        // Créer des gestionnaires
        \App\Models\User::factory()->create([
            'numero_user' => 'USR-MGR001',
            'name' => 'Marie Dupont',
            'email' => 'marie.dupont@banque.com',
            'role' => 'manager',
            'statut' => 'actif',
            'password' => Hash::make('password'),
        ]);

        \App\Models\User::factory()->create([
            'numero_user' => 'USR-MGR002',
            'name' => 'Pierre Martin',
            'email' => 'pierre.martin@banque.com',
            'role' => 'manager',
            'statut' => 'actif',
            'password' => Hash::make('password'),
        ]);

        // Créer des utilisateurs standards sénégalais
        \App\Models\User::factory()->create([
            'numero_user' => 'USR-USER001',
            'name' => 'Mary Vonne',
            'email' => 'mary.vonne@banque.com',
            'role' => 'user',
            'statut' => 'actif',
            'password' => Hash::make('password'),
        ]);

        \App\Models\User::factory()->create([
            'numero_user' => 'USR-USER002',
            'name' => 'Ahmad Diallo',
            'email' => 'ahmad.diallo@banque.com',
            'role' => 'user',
            'statut' => 'actif',
            'password' => Hash::make('password'),
        ]);

        \App\Models\User::factory()->create([
            'numero_user' => 'USR-USER003',
            'name' => 'Roi Pere',
            'email' => 'roi.pere@banque.com',
            'role' => 'user',
            'statut' => 'actif',
            'password' => Hash::make('password'),
        ]);

        \App\Models\User::factory()->create([
            'numero_user' => 'USR-USER004',
            'name' => 'Reine Mere',
            'email' => 'reine.mere@banque.com',
            'role' => 'user',
            'statut' => 'actif',
            'password' => Hash::make('password'),
        ]);

        \App\Models\User::factory()->create([
            'numero_user' => 'USR-USER005',
            'name' => 'Fatou',
            'email' => 'fatou@banque.com',
            'role' => 'user',
            'statut' => 'actif',
            'password' => Hash::make('password'),
        ]);

        \App\Models\User::factory()->create([
            'numero_user' => 'USR-USER006',
            'name' => 'Dieynaba',
            'email' => 'dieynaba@banque.com',
            'role' => 'user',
            'statut' => 'actif',
            'password' => Hash::make('password'),
        ]);

        \App\Models\User::factory()->create([
            'numero_user' => 'USR-USER007',
            'name' => 'Bobo Tijani',
            'email' => 'bobo.tijani@banque.com',
            'role' => 'user',
            'statut' => 'actif',
            'password' => Hash::make('password'),
        ]);

        \App\Models\User::factory()->create([
            'numero_user' => 'USR-USER008',
            'name' => 'Baye Ala Mine',
            'email' => 'baye.ala.mine@banque.com',
            'role' => 'user',
            'statut' => 'actif',
            'password' => Hash::make('password'),
        ]);

        \App\Models\User::factory()->create([
            'numero_user' => 'USR-USER009',
            'name' => 'Baye Amadou',
            'email' => 'baye.amadou@banque.com',
            'role' => 'user',
            'statut' => 'actif',
            'password' => Hash::make('password'),
        ]);

        \App\Models\User::factory()->create([
            'numero_user' => 'USR-USER010',
            'name' => 'Daba Ngom',
            'email' => 'daba.ngom@banque.com',
            'role' => 'user',
            'statut' => 'actif',
            'password' => Hash::make('password'),
        ]);
    }
}
