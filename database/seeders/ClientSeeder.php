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
        // Créer des clients sénégalais spécifiques
        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-SEN001',
            'nom' => 'Vonne',
            'prenom' => 'Mary',
            'email' => 'mary.vonne@client.com',
            'telephone' => '+221771234567',
            'date_naissance' => '1985-03-10',
            'ville' => 'Dakar',
            'statut' => 'actif',
        ]);

        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-SEN002',
            'nom' => 'Diallo',
            'prenom' => 'Ahmad',
            'email' => 'ahmad.diallo@client.com',
            'telephone' => '+221772345678',
            'date_naissance' => '1990-07-15',
            'ville' => 'Saint-Louis',
            'statut' => 'actif',
        ]);

        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-SEN003',
            'nom' => 'Pere',
            'prenom' => 'Roi',
            'email' => 'roi.pere@client.com',
            'telephone' => '+221773456789',
            'date_naissance' => '1982-11-20',
            'ville' => 'Thiès',
            'statut' => 'actif',
        ]);

        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-SEN004',
            'nom' => 'Mere',
            'prenom' => 'Reine',
            'email' => 'reine.mere@client.com',
            'telephone' => '+221774567890',
            'date_naissance' => '1988-05-25',
            'ville' => 'Ziguinchor',
            'statut' => 'actif',
        ]);

        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-SEN005',
            'nom' => 'Fatou',
            'prenom' => 'diallo',
            'email' => 'fatou@client.com',
            'telephone' => '+221775678901',
            'date_naissance' => '1995-09-30',
            'ville' => 'Kaolack',
            'statut' => 'actif',
        ]);

        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-SEN006',
            'nom' => 'Dieynaba',
            'prenom' => 'diallo',
            'email' => 'dieynaba@client.com',
            'telephone' => '+221776789012',
            'date_naissance' => '1987-01-12',
            'ville' => 'Tambacounda',
            'statut' => 'actif',
        ]);

        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-SEN007',
            'nom' => 'Tijani',
            'prenom' => 'Bobo',
            'email' => 'bobo.tijani@client.com',
            'telephone' => '+221777890123',
            'date_naissance' => '1992-04-18',
            'ville' => 'Louga',
            'statut' => 'actif',
        ]);

        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-SEN008',
            'nom' => 'Ala Mine',
            'prenom' => 'Baye',
            'email' => 'baye.ala.mine@client.com',
            'telephone' => '+221778901234',
            'date_naissance' => '1984-08-05',
            'ville' => 'Matam',
            'statut' => 'actif',
        ]);

        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-SEN009',
            'nom' => 'Amadou',
            'prenom' => 'Baye',
            'email' => 'baye.amadou@client.com',
            'telephone' => '+221779012345',
            'date_naissance' => '1991-12-08',
            'ville' => 'Kolda',
            'statut' => 'actif',
        ]);

        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-SEN010',
            'nom' => 'Ngom',
            'prenom' => 'Daba',
            'email' => 'daba.ngom@client.com',
            'telephone' => '+221780123456',
            'date_naissance' => '1989-06-22',
            'ville' => 'Sédhiou',
            'statut' => 'actif',
        ]);

        // Créer des clients de test supplémentaires
        \App\Models\Client::factory(40)->create();

        // Créer quelques clients spécifiques pour les tests
        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-TEST001',
            'nom' => 'diallo',
            'prenom' => 'mary von',
            'email' => 'mari.von.diallo@test.com',
            'telephone' => '+33123456789',
            'date_naissance' => '1980-05-15',
            'ville' => 'Paris',
            'statut' => 'actif',
        ]);

        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-TEST002',
            'nom' => 'diallo',
            'prenom' => 'Marie',
            'email' => 'marie.diallo@test.com',
            'telephone' => '+33987654321',
            'date_naissance' => '1992-08-22',
            'ville' => 'Lyon',
            'statut' => 'actif',
        ]);

        \App\Models\Client::factory()->create([
            'numero_client' => 'CLI-TEST003',
            'nom' => 'Diallo',
            'prenom' => 'papa',
            'email' => 'papadiallo@test.com',
            'telephone' => '+33555666777',
            'date_naissance' => '1975-12-03',
            'ville' => 'Marseille',
            'statut' => 'suspendu',
        ]);

        // Les utilisateurs sont maintenant créés dans CustomUserSeeder
    }

}
