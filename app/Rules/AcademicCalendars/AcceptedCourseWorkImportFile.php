<?php

namespace App\Rules\AcademicCalendars;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Only the system-generated .xlsx template is accepted: CSV and legacy .xls files cannot carry the
 * locked cells and signed metadata that protect everything except the mark columns.
 */
class AcceptedCourseWorkImportFile implements ValidationRule
{
    /** @var list<string> */
    public const EXTENSIONS = ['xlsx'];

    /** @var list<string> */
    public const MIME_TYPES = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'application/octet-stream',
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
        }
    }
}
