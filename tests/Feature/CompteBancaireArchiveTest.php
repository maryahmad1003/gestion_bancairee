<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CompteBancaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client as PassportClient;
use Tests\TestCase;

class CompteBancaireArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $client;
    protected $passportClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un client Passport pour les tests
        $this->passportClient = PassportClient::factory()->create([
            'password_client' => true,
            'personal_access_client' => false,
            'revoked' => false,
        ]);

        // Créer un utilisateur admin
        $this->user = User::factory()->create([
            'role' => 'admin',
            'statut' => 'actif',
        ]);

        // Créer un client
        $this->client = Client::factory()->create();
    }

    /** @test */
    public function admin_can_archive_active_compte_bancaire()
    {
        $compte = CompteBancaire::factory()->create([
            'client_id' => $this->client->id,
            'statut' => 'actif',
            'type_compte' => 'cheque',
            'solde' => 0, // Solde nul pour les comptes chèque
        ]);

        // Login pour obtenir le token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
            'client_id' => $this->passportClient->id,
            'client_secret' => $this->passportClient->secret,
        ]);

        $token = $loginResponse->json('access_token');

        // Archiver le compte
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/comptes/{$compte->id}/archiver", [
            'motif' => 'Archivage demandé par le client',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Compte bancaire archivé avec succès'
                ]);

        // Vérifier que le compte est archivé
        $compte->refresh();
        $this->assertTrue($compte->est_archive);
        $this->assertNotNull($compte->date_archivage);
        $this->assertEquals('Archivage demandé par le client', $compte->motif_archivage);
    }

    /** @test */
    public function cannot_archive_already_archived_compte()
    {
        $compte = CompteBancaire::factory()->create([
            'client_id' => $this->client->id,
            'statut' => 'actif',
            'type_compte' => 'cheque',
            'solde' => 0,
            'est_archive' => true,
        ]);

        // Login pour obtenir le token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
            'client_id' => $this->passportClient->id,
            'client_secret' => $this->passportClient->secret,
        ]);

        $token = $loginResponse->json('access_token');

        // Tenter d'archiver un compte déjà archivé
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/comptes/{$compte->id}/archiver", [
            'motif' => 'Test archivage',
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Le compte est déjà archivé.'
                ]);
    }

    /** @test */
    public function cannot_archive_inactive_compte()
    {
        $compte = CompteBancaire::factory()->create([
            'client_id' => $this->client->id,
            'statut' => 'inactif',
            'type_compte' => 'cheque',
            'solde' => 0,
        ]);

        // Login pour obtenir le token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
            'client_id' => $this->passportClient->id,
            'client_secret' => $this->passportClient->secret,
        ]);

        $token = $loginResponse->json('access_token');

        // Tenter d'archiver un compte inactif
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/comptes/{$compte->id}/archiver", [
            'motif' => 'Test archivage',
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Seul un compte actif peut être archivé.'
                ]);
    }

    /** @test */
    public function cannot_archive_cheque_compte_with_non_zero_balance()
    {
        $compte = CompteBancaire::factory()->create([
            'client_id' => $this->client->id,
            'statut' => 'actif',
            'type_compte' => 'cheque',
            'solde' => 100.00, // Solde non nul
        ]);

        // Login pour obtenir le token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
            'client_id' => $this->passportClient->id,
            'client_secret' => $this->passportClient->secret,
        ]);

        $token = $loginResponse->json('access_token');

        // Tenter d'archiver un compte chèque avec solde non nul
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/comptes/{$compte->id}/archiver", [
            'motif' => 'Test archivage',
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Le compte chèque doit avoir un solde nul pour être archivé.'
                ]);
    }

    /** @test */
    public function admin_can_desarchive_compte_bancaire()
    {
        $compte = CompteBancaire::factory()->create([
            'client_id' => $this->client->id,
            'statut' => 'actif',
            'type_compte' => 'cheque',
            'solde' => 0,
            'est_archive' => true,
            'date_archivage' => now(),
            'motif_archivage' => 'Test archivage',
        ]);

        // Login pour obtenir le token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
            'client_id' => $this->passportClient->id,
            'client_secret' => $this->passportClient->secret,
        ]);

        $token = $loginResponse->json('access_token');

        // Désarchiver le compte
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/comptes/{$compte->id}/desarchiver");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Compte bancaire désarchivé avec succès'
                ]);

        // Vérifier que le compte n'est plus archivé
        $compte->refresh();
        $this->assertFalse($compte->est_archive);
        $this->assertNull($compte->date_archivage);
        $this->assertNull($compte->motif_archivage);
    }

    /** @test */
    public function cannot_desarchive_non_archived_compte()
    {
        $compte = CompteBancaire::factory()->create([
            'client_id' => $this->client->id,
            'statut' => 'actif',
            'type_compte' => 'cheque',
            'solde' => 0,
            'est_archive' => false,
        ]);

        // Login pour obtenir le token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
            'client_id' => $this->passportClient->id,
            'client_secret' => $this->passportClient->secret,
        ]);

        $token = $loginResponse->json('access_token');

        // Tenter de désarchiver un compte non archivé
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/comptes/{$compte->id}/desarchiver");

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Le compte n\'est pas archivé.'
                ]);
    }

    /** @test */
    public function archiving_requires_motif()
    {
        $compte = CompteBancaire::factory()->create([
            'client_id' => $this->client->id,
            'statut' => 'actif',
            'type_compte' => 'cheque',
            'solde' => 0,
        ]);

        // Login pour obtenir le token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
            'client_id' => $this->passportClient->id,
            'client_secret' => $this->passportClient->secret,
        ]);

        $token = $loginResponse->json('access_token');

        // Tenter d'archiver sans motif
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/comptes/{$compte->id}/archiver", []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['motif']);
    }
}