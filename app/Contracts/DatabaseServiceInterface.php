<?php

namespace App\Contracts;

interface DatabaseServiceInterface
{
    /**
     * Retrieve data from the database
     *
     * @param string $table
     * @param array $conditions
     * @return array
     */
    public function retrieve(string $table, array $conditions): array;
}