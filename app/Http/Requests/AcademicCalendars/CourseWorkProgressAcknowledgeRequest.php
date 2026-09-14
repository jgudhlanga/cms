<?php

namespace App\Http\Requests\AcademicCalendars;

use App\Models\AcademicCalendars\CourseWorkProgressReport;
use Illuminate\Foundation\Http\FormRequest;

class CourseWorkProgressAcknowledgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('course_work_progress_report');

        return $report instanceof CourseWorkProgressReport
            && ($this->user()?->can('acknowledge', $report) ?? false);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
