<?php

namespace App\Http\Requests\AcademicCalendars;

use App\Rules\AcademicCalendars\AcceptedCourseWorkImportFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseWorkImportProcessRequest extends FormRequest
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
            'preview_token' => ['required', 'string', 'size:40'],
            'file' => [
                Rule::prohibitedIf(fn (): bool => $this->filled('preview_token')),
                'file',
                'max:10240',
                new AcceptedCourseWorkImportFile,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preview_token.required' => __('academic_calendar.course_work_import_preview_required'),
            'file.max' => __('academic_calendar.course_work_import_file_too_large'),
        ];
    }
}
