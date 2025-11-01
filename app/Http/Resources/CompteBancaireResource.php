<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="CompteBancaire",
 *     title="Compte Bancaire",
 *     description="Objet représentant un compte bancaire",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="numero_compte", type="string", example="C00123456"),
 *     @OA\Property(property="type_compte", type="string", enum={"cheque", "epargne"}, example="cheque"),
 *     @OA\Property(property="devise", type="string", example="XOF"),
 *     @OA\Property(property="solde", type="number", format="float", example=500000),
 *     @OA\Property(property="solde_formate", type="string", example="500 000 XOF"),
 *     @OA\Property(property="decouvert_autorise", type="number", format="float", example=0),
 *     @OA\Property(property="date_ouverture", type="string", format="date", example="2023-10-23"),
 *     @OA\Property(property="statut", type="string", example="actif"),
 *     @OA\Property(property="peut_debiter", type="boolean", example=true),
 *     @OA\Property(property="client", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="numero_client", type="string", example="CL001"),
 *         @OA\Property(property="nom_complet", type="string", example="John Doe"),
 *         @OA\Property(property="telephone", type="string", example="+221771234567")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class CompteBancaireResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_compte' => $this->numero_compte,
            'type_compte' => $this->type_compte,
            'devise' => $this->devise,
            'solde' => (float) $this->solde,
            'solde_formate' => $this->solde_formate,
            'decouvert_autorise' => $this->decouvert_autorise,
            'date_ouverture' => $this->date_ouverture ? $this->date_ouverture->format('Y-m-d') : null,
            'statut' => $this->statut,
            'peut_debiter' => $this->peut_debiter,
            'client' => $this->client ? [
                'id' => $this->client->id,
                'numero_client' => $this->client->numero_client,
                'nom_complet' => $this->client->nom_complet,
                'telephone' => $this->client->telephone,
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}