<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
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
            'permissions' => json_encode([
                'manage_clients',
                'manage_accounts',
                'manage_transactions',
                'block_accounts',
                'view_reports',
                'manage_users'
            ]),
        ]);

        // Créer des gestionnaires
        \App\Models\User::factory()->create([
            'numero_user' => 'USR-MGR001',
            'name' => 'Marie Dupont',
            'email' => 'marie.dupont@banque.com',
            'role' => 'manager',
            'statut' => 'actif',
            'password' => Hash::make('password'),
            'permissions' => json_encode([
                'manage_clients',
                'manage_accounts',
                'view_reports',
                'block_accounts'
            ]),
        ]);

        \App\Models\User::factory()->create([
            'numero_user' => 'USR-MGR002',
            'name' => 'Pierre Martin',
            'email' => 'pierre.martin@banque.com',
            'role' => 'manager',
            'statut' => 'actif',
            'password' => Hash::make('password'),
            'permissions' => json_encode([
                'manage_clients',
                'manage_accounts',
                'view_reports',
                'block_accounts'
            ]),
        ]);

        // Créer des utilisateurs standards
        \App\Models\User::factory()->create([
            'numero_user' => 'USR-USER001',
            'name' => 'Jean Dupont',
            'email' => 'jean.dupont@banque.com',
            'role' => 'user',
            'statut' => 'actif',
            'password' => Hash::make('password'),
            'permissions' => json_encode([
                'view_own_accounts',
                'view_own_transactions',
                'create_transactions'
            ]),
        ]);

        \App\Models\User::factory()->create([
            'numero_user' => 'USR-USER002',
            'name' => 'Sophie Bernard',
            'email' => 'sophie.bernard@banque.com',
            'role' => 'user',
            'statut' => 'actif',
            'password' => Hash::make('password'),
            'permissions' => json_encode([
                'view_own_accounts',
                'view_own_transactions',
                'create_transactions'
            ]),
        ]);

        // Créer quelques utilisateurs inactifs pour les tests
        \App\Models\User::factory()->create([
            'numero_user' => 'USR-INACT001',
            'name' => 'Paul Durand',
            'email' => 'paul.durand@banque.com',
            'role' => 'user',
            'statut' => 'inactif',
            'password' => Hash::make('password'),
            'permissions' => json_encode([]),
        ]);

        // Créer des utilisateurs supplémentaires aléatoires
        \App\Models\User::factory(10)->create([
            'password' => Hash::make('password'),
        ]);
    }
}
