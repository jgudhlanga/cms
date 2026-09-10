<?php

declare(strict_types=1);

namespace App\Http\Requests\AcademicCalendars;

use App\Rules\AcademicCalendars\AcceptedCourseWorkImportFile;
use Illuminate\Foundation\Http\FormRequest;

class EnrolmentProgressionImportPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', new AcceptedCourseWorkImportFile],
            'academic_calendar_class_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('academic_calendar.progression_import_file_required'),
            'file.max' => __('academic_calendar.course_work_import_file_too_large'),
        ];
    }
}
