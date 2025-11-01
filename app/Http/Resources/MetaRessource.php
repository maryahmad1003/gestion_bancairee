<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Meta",
 *     title="Meta",
 *     description="Informations de pagination et liens",
 *     @OA\Property(property="pagination", type="object",
 *         @OA\Property(property="currentPage", type="integer", example=1),
 *         @OA\Property(property="totalPages", type="integer", example=5),
 *         @OA\Property(property="totalItems", type="integer", example=50),
 *         @OA\Property(property="itemsPerPage", type="integer", example=10),
 *         @OA\Property(property="hasNext", type="boolean", example=true),
 *         @OA\Property(property="hasPrevious", type="boolean", example=false)
 *     ),
 *     @OA\Property(property="links", type="object",
 *         @OA\Property(property="self", type="string", example="http://localhost:8000/api/v1/comptes?page=1"),
 *         @OA\Property(property="next", type="string", example="http://localhost:8000/api/v1/comptes?page=2"),
 *         @OA\Property(property="first", type="string", example="http://localhost:8000/api/v1/comptes?page=1"),
 *         @OA\Property(property="last", type="string", example="http://localhost:8000/api/v1/comptes?page=5")
 *     )
 * )
 */
class MetaRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'pagination' => [
                'currentPage' => $this->currentPage(),
                'totalPages' => $this->lastPage(),
                'totalItems' => $this->total(),
                'itemsPerPage' => $this->perPage(),
                'hasNext' => $this->hasMorePages(),
                'hasPrevious' => $this->onFirstPage() === false,

            ],
            'links' => [
                'self' => $this->url($this->currentPage()),
                'next' => $this->nextPageUrl(),
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
            ],
        ];
    }
}
