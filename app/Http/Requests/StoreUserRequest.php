<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'numero_user' => 'sometimes|string|unique:users,numero_user',
            'name' => 'required|string|max:255|regex:/^[a-zA-ZÀ-ÿ\s\-]+$/',
            'email' => 'required|email:rfc,dns|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|in:admin,manager,user',
            'statut' => 'sometimes|in:actif,inactif,suspendu',
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
            'numero_user.unique' => 'Ce numéro d\'utilisateur est déjà utilisé.',
            'name.required' => 'Le nom est obligatoire.',
            'name.regex' => 'Le nom ne peut contenir que des lettres, espaces et tirets.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.in' => 'Le rôle doit être admin, manager ou user.',
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
            'numero_user' => 'numéro utilisateur',
            'password_confirmation' => 'confirmation du mot de passe',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->password) {
            $this->merge([
                'password' => bcrypt($this->password),
            ]);
        }
    }
}