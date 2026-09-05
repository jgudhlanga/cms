<?php

declare(strict_types=1);

namespace App\Http\Requests\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\Institution\DepartmentLevelCourse;
use App\Support\Institution\ProgrammeDurationCalculator;
use App\Support\Institution\ProgrammeSemesterNameFormatter;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProgrammeStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage:programme-structures') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'duration_years' => ['required', 'numeric', 'min:0.5', 'max:10'],
            'taught_semester_count' => ['required', 'integer', 'min:1', 'max:24'],
            'includes_industrial_attachment' => ['required', 'boolean'],
            'attachment_semester_count' => ['required', 'integer', 'min:0', 'max:12'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $includesAttachment = filter_var(
            $this->input('includes_industrial_attachment'),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE,
        );

        $periodsPerYear = $this->resolvePeriodsPerYear();

        if ($includesAttachment !== true) {
            if ($includesAttachment === false) {
                $this->merge(['attachment_semester_count' => 0]);
            }

            return;
        }

        if ((int) $this->input('attachment_semester_count', 0) < 1) {
            $this->merge(['attachment_semester_count' => $periodsPerYear]);
        }

        $this->merge([
            'duration_years' => ProgrammeDurationCalculator::years(
                max(1, (int) $this->input('taught_semester_count', 1)),
                max(1, (int) $this->input('attachment_semester_count', $periodsPerYear)),
                $periodsPerYear,
                true,
            ),
        ]);
    }

    private function resolvePeriodsPerYear(): int
    {
        $departmentLevelCourse = $this->route('department_level_course');

        if (! $departmentLevelCourse instanceof DepartmentLevelCourse) {
            return ProgrammeSemesterNameFormatter::periodsPerYear(AcademicCalendarTypeEnum::SEMESTER);
        }

        $departmentLevelCourse->loadMissing('departmentLevel.level');
        $calendarType = $departmentLevelCourse->departmentLevel?->level?->calendar_type;

        if (! $calendarType instanceof AcademicCalendarTypeEnum) {
            $calendarType = AcademicCalendarTypeEnum::tryFrom((string) $calendarType)
                ?? AcademicCalendarTypeEnum::SEMESTER;
        }

        return ProgrammeSemesterNameFormatter::periodsPerYear($calendarType);
    }
}
