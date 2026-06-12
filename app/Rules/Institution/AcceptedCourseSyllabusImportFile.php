<?php

declare(strict_types=1);

namespace App\Rules\Institution;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class AcceptedCourseSyllabusImportFile implements ValidationRule
{
    /** @var list<string> */
    public const EXTENSIONS = ['xlsx', 'xls', 'csv'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail(__('syllabus.import_invalid_file_type'));

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (! in_array($extension, self::EXTENSIONS, true)) {
            $fail(__('syllabus.import_invalid_file_type'));
        }
    }
}
