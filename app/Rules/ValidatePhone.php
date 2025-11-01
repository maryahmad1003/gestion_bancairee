<?php


namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidatePhone implements Rule
{
    public function passes($attribute, $value): bool
    {
        $phone = str_replace('+221', '', $value);
        
        if (strlen($phone) !== 9) {
            return false;
        }

        if ($phone[0] !== '7') {
            return false;
        }

        if (!in_array($phone[1], ['0','5','6','7','8'])) {
            return false;
        }

        for ($i = 2; $i < 9; $i++) {
            if (!is_numeric($phone[$i])) {
                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return 'Le numéro de téléphone doit être un numéro sénégalais valide (format: 7X XXXXXXX où X est un chiffre et le deuxième chiffre doit être 0,5,6,7 ou 8).';
    }
}