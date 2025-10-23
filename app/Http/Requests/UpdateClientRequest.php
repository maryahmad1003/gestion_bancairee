<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
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
        $clientId = $this->route('client')?->id ?? $this->route('id');

        return [
            'numero_client' => 'sometimes|string|unique:clients,numero_client,' . $clientId,
            'nom' => 'sometimes|string|max:255|regex:/^[a-zA-ZÀ-ÿ\s\-]+$/',
            'prenom' => 'sometimes|string|max:255|regex:/^[a-zA-ZÀ-ÿ\s\-]+$/',
            'email' => 'sometimes|email:rfc,dns|unique:clients,email,' . $clientId,
            'telephone' => 'sometimes|string|max:20',
            'date_naissance' => 'sometimes|date|before:today|after:1900-01-01',
            'adresse' => 'nullable|string|max:500',
            'ville' => 'nullable|string|max:255|regex:/^[a-zA-ZÀ-ÿ\s\-]+$/',
            'code_postal' => 'nullable|string|regex:/^[0-9]{5}$/',
            'pays' => 'nullable|string|max:100',
            'statut' => 'sometimes|in:actif,inactif,suspendu',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Vérifier qu'au moins un champ est fourni pour la modification
            $fillableFields = [
                'numero_client', 'nom', 'prenom', 'email', 'telephone',
                'date_naissance', 'adresse', 'ville', 'code_postal', 'pays', 'statut'
            ];

            $hasAtLeastOneField = false;
            foreach ($fillableFields as $field) {
                if ($this->has($field)) {
                    $hasAtLeastOneField = true;
                    break;
                }
            }

            if (!$hasAtLeastOneField) {
                $validator->errors()->add('general', 'Au moins un champ doit être fourni pour la modification.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom.regex' => 'Le nom ne peut contenir que des lettres, espaces et tirets.',
            'prenom.regex' => 'Le prénom ne peut contenir que des lettres, espaces et tirets.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'telephone.regex' => 'Le format du numéro de téléphone n\'est pas valide.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'date_naissance.after' => 'La date de naissance semble incorrecte.',
            'ville.regex' => 'Le nom de la ville ne peut contenir que des lettres, espaces et tirets.',
            'code_postal.regex' => 'Le code postal doit contenir exactement 5 chiffres.',
            'statut.in' => 'Le statut doit être actif, inactif ou suspendu.',
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
            'numero_client' => 'numéro client',
            'date_naissance' => 'date de naissance',
            'code_postal' => 'code postal',
        ];
    }
}
