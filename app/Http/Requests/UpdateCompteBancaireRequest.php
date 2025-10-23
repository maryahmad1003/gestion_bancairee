<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompteBancaireRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Pour l'API, on autorise par défaut (géré par les middlewares)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $compteId = $this->route('compte_bancaire')?->id ?? $this->route('id');

        return [
            'numero_compte' => 'sometimes|string|unique:comptes_bancaires,numero_compte,' . $compteId,
            'client_id' => 'sometimes|uuid|exists:clients,id',
            'type_compte' => 'sometimes|in:courant,epargne,joint',
            'devise' => 'sometimes|string|size:3|in:EUR,USD,GBP,XAF,XOF',
            'solde' => 'sometimes|numeric|min:-100000|max:1000000',
            'decouvert_autorise' => 'sometimes|numeric|min:0|max:50000',
            'date_ouverture' => 'sometimes|date|before_or_equal:today',
            'statut' => 'sometimes|in:actif,bloque,ferme',
            'commentaires' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numero_compte.unique' => 'Ce numéro de compte est déjà utilisé.',
            'client_id.exists' => 'Le client spécifié n\'existe pas.',
            'type_compte.in' => 'Le type de compte doit être courant, épargne ou joint.',
            'devise.size' => 'La devise doit contenir exactement 3 caractères.',
            'devise.in' => 'La devise doit être EUR, USD, GBP, XAF ou XOF.',
            'solde.min' => 'Le solde ne peut pas être inférieur à -100 000.',
            'solde.max' => 'Le solde ne peut pas dépasser 1 000 000.',
            'decouvert_autorise.min' => 'Le découvert autorisé ne peut pas être négatif.',
            'decouvert_autorise.max' => 'Le découvert autorisé ne peut pas dépasser 50 000.',
            'date_ouverture.before_or_equal' => 'La date d\'ouverture ne peut pas être dans le futur.',
            'statut.in' => 'Le statut doit être actif, bloqué ou fermé.',
            'commentaires.max' => 'Les commentaires ne peuvent pas dépasser 1000 caractères.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'numero_compte' => 'numéro de compte',
            'client_id' => 'identifiant client',
            'type_compte' => 'type de compte',
            'date_ouverture' => 'date d\'ouverture',
            'decouvert_autorise' => 'découvert autorisé',
        ];
    }
}