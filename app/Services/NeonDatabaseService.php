<?php

namespace App\Services;

use App\Contracts\DatabaseServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NeonDatabaseService implements DatabaseServiceInterface
{
    /**
     * Retrieve data from Neon database
     *
     * @param string $table
     * @param array $conditions
     * @return array
     */
    public function retrieve(string $table, array $conditions): array
    {
        try {
            // Configuration de la connexion Neon
            $neonUrl = config('services.neon.url');
            $neonApiKey = config('services.neon.api_key');

            if (!$neonUrl || !$neonApiKey) {
                Log::error('Configuration Neon manquante');
                return [];
            }

            // Construction de la requête
            $queryParams = http_build_query($conditions);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $neonApiKey,
                'Content-Type' => 'application/json',
            ])->get("{$neonUrl}/{$table}?{$queryParams}");

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Données récupérées depuis Neon pour la table {$table}", [
                    'conditions' => $conditions,
                    'count' => count($data)
                ]);
                return $data;
            } else {
                Log::error("Erreur lors de la récupération depuis Neon pour la table {$table}", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'conditions' => $conditions
                ]);
                return [];
            }
        } catch (\Exception $e) {
            Log::error("Exception lors de la récupération depuis Neon pour la table {$table}", [
                'error' => $e->getMessage(),
                'conditions' => $conditions
            ]);
            return [];
        }
    }
}