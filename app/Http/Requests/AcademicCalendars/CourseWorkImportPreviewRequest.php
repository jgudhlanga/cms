<?php

namespace App\Http\Requests\AcademicCalendars;

use App\Rules\AcademicCalendars\AcceptedCourseWorkImportFile;
use Illuminate\Foundation\Http\FormRequest;

class CourseWorkImportPreviewRequest extends FormRequest
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
            'module' => ['required', 'integer', 'min:1'],
            'file' => ['required', 'file', 'max:10240', new AcceptedCourseWorkImportFile],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('academic_calendar.course_work_import_file_required'),
            'file.max' => __('academic_calendar.course_work_import_file_too_large'),
        ];
    }
}
