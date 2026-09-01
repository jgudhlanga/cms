<?php

declare(strict_types=1);

namespace App\Http\Requests\AcademicCalendars;

use Illuminate\Foundation\Http\FormRequest;

class CorrectProgrammeSemesterInclusionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update:academic-calendar-student-enrolments') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_enrolment_ids' => ['required', 'array', 'min:1'],
            'student_enrolment_ids.*' => ['integer', 'distinct', 'exists:student_enrolments,id'],
            'programme_semester_id' => ['required', 'integer', 'exists:programme_semesters,id'],
        ];
    }
}
