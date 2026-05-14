<?php

namespace App\Http\Requests\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\Institution\DepartmentLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('students_per_class') && $this->input('students_per_class') !== '' && $this->input('students_per_class') !== null) {
            $merge['students_per_class'] = (int) $this->input('students_per_class');
        }

        foreach (['department_level_id', 'department_course_id', 'mode_of_study_id', 'academic_year_option_id'] as $key) {
            if ($this->has($key) && $this->input($key) !== '' && $this->input($key) !== null) {
                $merge[$key] = (int) $this->input($key);
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'students_per_class' => ['required', 'integer', 'min:1'],
            'department_level_id' => ['required', 'exists:department_levels,id'],
            'department_course_id' => ['required', 'exists:department_courses,id'],
            'mode_of_study_id' => ['required', 'exists:mode_of_studies,id'],
            'academic_year_option_id' => [
                'required',
                'integer',
                Rule::exists('academic_year_options', 'id')->where(function ($query): void {
                    $prefix = $this->calendarTypeSlugPrefix();

                    $query->where('slug', 'like', $prefix.'-%');
                }),
            ],
        ];
    }

    /**
     * When a level has no calendar type, semester options are allowed (matches UI default).
     */
    private function calendarTypeSlugPrefix(): string
    {
        $departmentLevelId = $this->input('department_level_id');
        if ($departmentLevelId === null || $departmentLevelId === '') {
            return AcademicCalendarTypeEnum::SEMESTER->value;
        }

        $departmentLevel = DepartmentLevel::query()->with('level')->find((int) $departmentLevelId);
        $calendarType = $departmentLevel?->level?->calendar_type;

        if ($calendarType instanceof AcademicCalendarTypeEnum) {
            return $calendarType->value;
        }

        return AcademicCalendarTypeEnum::tryFrom((string) $calendarType)?->value
            ?? AcademicCalendarTypeEnum::SEMESTER->value;
    }
}
