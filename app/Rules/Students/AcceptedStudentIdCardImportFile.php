<?php

declare(strict_types=1);

namespace App\Rules\Students;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class AcceptedStudentIdCardImportFile implements ValidationRule
{
    /** @var list<string> */
    public const EXTENSIONS = ['csv'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail(__('trans.student_id_card_import_invalid_file_type'));

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (! in_array($extension, self::EXTENSIONS, true)) {
            $fail(__('trans.student_id_card_import_invalid_file_type'));
        }
    }
}
