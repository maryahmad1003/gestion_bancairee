<?php


namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidateCni implements Rule
{
    public function passes($attribute, $value): bool
    {
        //  les espaces éventuels
        $nci = str_replace(' ', '', $value);
        
        if (strlen($nci) !== 13) {
            return false;
        }

        for ($i = 0; $i < 13; $i++) {
            if (!is_numeric($nci[$i])) {
                return false;
            }
        }

        if (!in_array($nci[0], ['1', '2'])) {
            return false;
        }

        $year = intval(substr($nci, 1, 2));
        $month = intval(substr($nci, 3, 2));
        $day = intval(substr($nci, 5, 2));

        if ($month < 1 || $month > 12) {
            return false;
        }

        if ($day < 1 || $day > 31) {
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return 'Le numéro CNI doit être valide (13 chiffres, commence par 1 ou 2, et contient une date valide).';
    }
}