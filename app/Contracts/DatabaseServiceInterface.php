<?php

namespace App\Contracts;

interface DatabaseServiceInterface
{
    /**
     * Archiver des données vers la base externe
     *
     * @param array $data Données à archiver
     * @param string $table Nom de la table
     * @return bool Succès de l'opération
     */
    public function archive(array $data, string $table): bool;

    /**
     * Récupérer des données depuis la base externe
     *
     * @param string $table Nom de la table
     * @param array $conditions Conditions de recherche
     * @return array Données récupérées
     */
    public function retrieve(string $table, array $conditions = []): array;

    /**
     * Tester la connexion à la base externe
     *
     * @return bool Connexion réussie
     */
    public function testConnection(): bool;
}