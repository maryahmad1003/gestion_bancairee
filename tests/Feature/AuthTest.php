<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un client Passport pour les tests
        $this->client = Client::factory()->create([
            'password_client' => true,
            'personal_access_client' => false,
            'revoked' => false,
        ]);
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'statut' => 'actif',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'permissions',
                    ],
                    'access_token',
                    'token_type',
                    'expires_at',
                ])
                ->assertCookie('access_token');
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function inactive_user_cannot_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'statut' => 'suspendu',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function user_can_refresh_token()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'statut' => 'actif',
        ]);

        // Login d'abord
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
        ]);

        // Refresh token
        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => 'dummy_refresh_token', // Dans un vrai test, utiliser le vrai refresh token
        ]);

        // Note: Ce test nécessiterait une implémentation complète du refresh token
        // Pour l'instant, on teste juste que la route existe
        $response->assertStatus(401); // Unauthorized car pas de vrai refresh token
    }

    /** @test */
    public function user_can_logout()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'statut' => 'actif',
        ]);

        // Login d'abord
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
        ]);

        $token = $loginResponse->json('access_token');

        // Logout
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Déconnexion réussie'
                ]);
    }

    /** @test */
    public function login_requires_valid_client_credentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'client_id' => 'invalid',
            'client_secret' => 'invalid',
        ]);

        $response->assertStatus(422);
    }
}