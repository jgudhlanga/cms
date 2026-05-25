<?php

namespace App\Rules;

use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidExchangeRateDate implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof DateTimeInterface) {
            return;
        }

        if (! is_string($value)) {
            $fail('The date field must be a valid date.');

            return;
        }

        $raw = trim($value);

        if ($raw === '') {
            $fail('The date field must be a valid date.');

            return;
        }

        try {
            Carbon::parse($raw);
        } catch (\Throwable) {
            $fail('The date field must be a valid date.');
        }
    }
}
