<?php

declare(strict_types=1);

namespace App\Rules\HMS;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class AcceptedHostelOccupantImportFile implements ValidationRule
{
    /** @var list<string> */
    public const EXTENSIONS = ['xlsx', 'xls', 'csv'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail(__('hms.import_occupants_invalid_file_type'));

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (! in_array($extension, self::EXTENSIONS, true)) {
            $fail(__('hms.import_occupants_invalid_file_type'));
        }
    }
}
