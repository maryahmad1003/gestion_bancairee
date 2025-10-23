<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidNciAndTelephone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Nettoyer le numéro de téléphone
        $cleanedValue = preg_replace('/\s+/', '', $value);

        // Vérifier le format du numéro de téléphone (format international ou local)
        if (!preg_match('/^(\+221|221)?[76-8][0-9]{7}$/', $cleanedValue)) {
            $fail('Le numéro de téléphone doit être un numéro sénégalais valide (format: +221XXXXXXXXX ou 221XXXXXXXXX ou 7XXXXXXXX).');
            return;
        }

        // Pour les numéros sénégalais, vérifier que c'est un numéro valide
        // Les numéros sénégalais commencent par 70, 76, 77, 78 pour Orange, Free, Expresso
        $prefix = substr($cleanedValue, -9, 2); // Les 2 premiers chiffres du numéro local
        if (!in_array($prefix, ['70', '76', '77', '78', '75', '79', '81', '82', '83'])) {
            $fail('Le numéro de téléphone doit être un numéro sénégalais valide.');
            return;
        }

        // Vérifier la longueur totale (doit être 9 chiffres pour le Sénégal)
        $localNumber = substr($cleanedValue, -9);
        if (strlen($localNumber) !== 9) {
            $fail('Le numéro de téléphone doit contenir exactement 9 chiffres.');
            return;
        }

        // Vérifier que ce n'est que des chiffres
        if (!preg_match('/^[0-9]+$/', $localNumber)) {
            $fail('Le numéro de téléphone ne peut contenir que des chiffres.');
            return;
        }
    }
}
