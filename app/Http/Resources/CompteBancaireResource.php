<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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