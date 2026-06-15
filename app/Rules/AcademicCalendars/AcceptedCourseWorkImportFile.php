<?php

namespace App\Rules\AcademicCalendars;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class AcceptedCourseWorkImportFile implements ValidationRule
{
    /** @var list<string> */
    public const EXTENSIONS = ['xlsx', 'xls', 'csv'];

    /** @var list<string> */
    public const MIME_TYPES = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'text/csv',
        'text/plain',
        'application/csv',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail(__('academic_calendar.course_work_import_invalid_file_type'));

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (! in_array($extension, self::EXTENSIONS, true)) {
            $fail(__('academic_calendar.course_work_import_invalid_file_type'));

            return;
        }
    }
}
