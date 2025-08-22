<?php

namespace App\Rules;

use App\Helpers\RutHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidChileanRut implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!RutHelper::validate($value)) {
            $fail('El :attribute no es un RUT chileno válido.');
        }
    }
}
