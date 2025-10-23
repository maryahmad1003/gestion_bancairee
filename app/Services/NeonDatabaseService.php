<?php

namespace App\Services;

use App\Contracts\DatabaseServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NeonDatabaseService implements DatabaseServiceInterface
{
    protected array $config;

    public function __construct()
    {
        $this->config = [
            'driver' => 'pgsql',
            'host' => env('NEON_HOST'),
            'port' => env('NEON_PORT', 5432),
            'database' => env('NEON_DATABASE'),
            'username' => env('NEON_USERNAME'),
            'password' => env('NEON_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'search_path' => 'public',
            'sslmode' => 'require',
        ];
    }

    /**
     * Archiver des données vers Neon
     */
    public function archive(array $data, string $table): bool
    {
        try {
            $connection = $this->getConnection();
            $connection->table($table)->insert($data);

            Log::info('Données archivées dans Neon', [
                'table' => $table,
                'count' => count($data)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'archivage dans Neon', [
                'table' => $table,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Récupérer des données depuis Neon
     */
    public function retrieve(string $table, array $conditions = []): array
    {
        try {
            $connection = $this->getConnection();
            $query = $connection->table($table);

            foreach ($conditions as $column => $value) {
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
            }

            $results = $query->get()->toArray();

            Log::info('Données récupérées depuis Neon', [
                'table' => $table,
                'conditions' => $conditions,
                'count' => count($results)
            ]);

            return $results;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération depuis Neon', [
                'table' => $table,
                'conditions' => $conditions,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Tester la connexion à Neon
     */
    public function testConnection(): bool
    {
        try {
            $connection = $this->getConnection();
            $connection->getPdo();

            Log::info('Connexion à Neon réussie');
            return true;
        } catch (\Exception $e) {
            Log::error('Échec de connexion à Neon', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Obtenir la connexion Neon
     */
    private function getConnection()
    {
        $connectionName = 'neon_' . md5(serialize($this->config));

        if (!config()->has("database.connections.{$connectionName}")) {
            config(["database.connections.{$connectionName}" => $this->config]);
        }

        return DB::connection($connectionName);
    }
}