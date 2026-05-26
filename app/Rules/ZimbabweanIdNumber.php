<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ZimbabweanIdNumber implements ValidationRule
{
    
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid Zimbabwean ID number.');
            return;
        }

        $value = strtoupper(trim($value));

        $isValid = preg_match(
            '/^\d{2}-\d{5,7}[A-Z]\d{2}$/',
            $value
        );

        if (! $isValid) {
            $fail('The :attribute format is invalid. Example: 63-1234567N63');
        }
    }
}
