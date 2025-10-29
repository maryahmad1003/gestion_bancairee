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
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:cheque,epargne',
            'soldeInitial' => 'required|numeric|min:0',
            'devise' => 'required|string|in:XOF,FCFA,EUR,USD',
            'solde' => 'required|numeric|min:0',
            'client' => 'required|array',
            'client.titulaire' => 'required|string|min:2|max:255',
            'client.nci' => ['nullable', 'string', new ValidNciAndTelephone()],
            'client.email' => 'required|email|unique:clients,email',
            'client.telephone' => ['required', 'string', new ValidNciAndTelephone()],
            'client.adresse' => 'required|string|min:5|max:500',
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
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type de compte doit être soit "cheque" soit "epargne".',
            'soldeInitial.required' => 'Le solde initial est obligatoire.',
            'soldeInitial.numeric' => 'Le solde initial doit être un nombre.',
            'soldeInitial.min' => 'Le solde initial ne peut pas être négatif.',
            'devise.required' => 'La devise est obligatoire.',
            'devise.in' => 'La devise doit être XOF, FCFA, EUR ou USD.',
            'solde.required' => 'Le solde est obligatoire.',
            'solde.numeric' => 'Le solde doit être un nombre.',
            'solde.min' => 'Le solde ne peut pas être négatif.',
            'client.required' => 'Les informations du client sont obligatoires.',
            'client.titulaire.required' => 'Le nom du titulaire est obligatoire.',
            'client.titulaire.min' => 'Le nom du titulaire doit contenir au moins 2 caractères.',
            'client.email.required' => 'L\'email est obligatoire.',
            'client.email.email' => 'L\'email doit être valide.',
            'client.email.unique' => 'Cet email est déjà utilisé.',
            'client.telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'client.adresse.required' => 'L\'adresse est obligatoire.',
            'client.adresse.min' => 'L\'adresse doit contenir au moins 5 caractères.',
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
            'type' => 'type de compte',
            'soldeInitial' => 'solde initial',
            'devise' => 'devise',
            'solde' => 'solde',
            'client.titulaire' => 'nom du titulaire',
            'client.nci' => 'numéro NCI',
            'client.email' => 'email',
            'client.telephone' => 'téléphone',
            'client.adresse' => 'adresse',
        ];
    }
}