<?php

namespace App\Http\Requests;

use App\Rules\ValidNciAndTelephone;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompteBancaireRequest extends FormRequest
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
        return [
            // Informations client
            'nom' => 'required|string|max:255|regex:/^[a-zA-ZÀ-ÿ\s\-]+$/',
            'prenom' => 'required|string|max:255|regex:/^[a-zA-ZÀ-ÿ\s\-]+$/',
            'email' => 'required|email:rfc,dns|unique:clients,email',
            'telephone' => ['required', 'string', 'max:20', new ValidNciAndTelephone()],
            'date_naissance' => 'required|date|before:today|after:1900-01-01',
            'adresse' => 'nullable|string|max:500',
            'ville' => 'nullable|string|max:255|regex:/^[a-zA-ZÀ-ÿ\s\-]+$/',
            'code_postal' => 'nullable|string|regex:/^[0-9]{5}$/',
            'pays' => 'nullable|string|max:100',

            // Informations compte bancaire
            'type_compte' => 'sometimes|in:courant,epargne',
            'devise' => 'sometimes|string|size:3|in:EUR,USD,GBP',
            'decouvert_autorise' => 'sometimes|numeric|min:0|max:10000',
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
            'nom.required' => 'Le nom du client est obligatoire.',
            'nom.regex' => 'Le nom ne peut contenir que des lettres, espaces et tirets.',
            'prenom.required' => 'Le prénom du client est obligatoire.',
            'prenom.regex' => 'Le prénom ne peut contenir que des lettres, espaces et tirets.',
            'email.required' => 'L\'email du client est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé par un autre client.',
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'date_naissance.after' => 'La date de naissance semble incorrecte.',
            'ville.regex' => 'Le nom de la ville ne peut contenir que des lettres, espaces et tirets.',
            'code_postal.regex' => 'Le code postal doit contenir exactement 5 chiffres.',
            'type_compte.in' => 'Le type de compte doit être courant ou épargne.',
            'devise.size' => 'La devise doit être composée de 3 caractères.',
            'devise.in' => 'La devise doit être EUR, USD ou GBP.',
            'decouvert_autorise.numeric' => 'Le découvert autorisé doit être un nombre.',
            'decouvert_autorise.min' => 'Le découvert autorisé ne peut pas être négatif.',
            'decouvert_autorise.max' => 'Le découvert autorisé ne peut pas dépasser 10 000 €.',
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
            'nom' => 'nom du client',
            'prenom' => 'prénom du client',
            'email' => 'email du client',
            'telephone' => 'numéro de téléphone',
            'date_naissance' => 'date de naissance',
            'code_postal' => 'code postal',
            'type_compte' => 'type de compte',
            'decouvert_autorise' => 'découvert autorisé',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Nettoyer le numéro de téléphone
        if ($this->telephone) {
            $this->merge([
                'telephone' => preg_replace('/\s+/', '', $this->telephone),
            ]);
        }
    }
}