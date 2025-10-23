<?php

namespace App\Traits;

trait ApiResponseTrait
{
    /**
     * Format de réponse standard pour les succès
     */
    protected function successResponse($data = null, string $message = 'Opération réussie', int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ], $status);
    }

    /**
     * Format de réponse standard pour les erreurs
     */
    protected function errorResponse(string $message = 'Une erreur est survenue', int $status = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Format de réponse pour les listes paginées
     */
    protected function paginatedResponse($paginatedData, string $message = 'Données récupérées avec succès')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginatedData->items(),
            'pagination' => [
                'current_page' => $paginatedData->currentPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total(),
                'last_page' => $paginatedData->lastPage(),
                'from' => $paginatedData->firstItem(),
                'to' => $paginatedData->lastItem(),
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }
}