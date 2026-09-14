<?php

namespace App\Http\Requests\AcademicCalendars;

use App\Models\AcademicCalendars\ClassConfig;
use App\Services\AcademicCalendars\LecturerInChargeService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Only the programme's Lecturer in Charge (holding the submit permission) sends progress reports, and only for
 * the current calendar year; earlier years are kept for reference.
 */
class CourseWorkProgressReportStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $classConfig = $this->route('class_config');
        $user = $this->user();

        return $classConfig instanceof ClassConfig
            && $user !== null
            && $user->can('submit:course-work-progress-reports')
            && (int) $classConfig->calendar_year >= now()->year
            && app(LecturerInChargeService::class)->isLecturerInCharge($user, (int) $classConfig->id);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
