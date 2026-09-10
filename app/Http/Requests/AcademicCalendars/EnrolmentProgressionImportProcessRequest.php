<?php

declare(strict_types=1);

namespace App\Http\Requests\AcademicCalendars;

use Illuminate\Foundation\Http\FormRequest;

class EnrolmentProgressionImportProcessRequest extends FormRequest
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
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.studentEnrolmentId' => ['required', 'integer', 'min:1'],
            'rows.*.academicCalendarClassId' => ['nullable', 'integer', 'min:1'],
            'academic_calendar_class_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
