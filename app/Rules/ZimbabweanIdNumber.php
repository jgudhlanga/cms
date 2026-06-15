<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ZimbabweanIdNumber implements ValidationRule
{
    public static function isValid(?string $idNumber): bool
    {
        if (! is_string($idNumber)) {
            return false;
        }

        $value = strtoupper(trim($idNumber));

        return preg_match(
            '/^\d{2}-\d{5,7}[A-Z]\d{2}$/',
            $value
        ) === 1;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
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
            $fail(__('trans.enrollment_invalid_national_id'));
        }
    }
}
