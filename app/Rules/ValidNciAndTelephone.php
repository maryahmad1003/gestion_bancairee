<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidNciAndTelephone implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Validation pour le NCI (Numéro d'Identification Nationale)
        if ($attribute === 'nci') {
            // Le NCI doit être une chaîne de 13 caractères numériques
            if (!preg_match('/^[0-9]{13}$/', $value)) {
                $fail('Le NCI doit contenir exactement 13 chiffres.');
                return;
            }
        }

        // Validation pour le téléphone
        if ($attribute === 'telephone') {
            // Format sénégalais: +221 suivi de 9 chiffres
            if (!preg_match('/^\+221[0-9]{9}$/', $value)) {
                $fail('Le numéro de téléphone doit être au format +221XXXXXXXXX.');
                return;
            }
        }
    }
}
