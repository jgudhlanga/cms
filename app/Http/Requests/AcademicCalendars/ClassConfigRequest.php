<?php

namespace App\Http\Requests\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

        $rawSyllabusIds = $this->input('course_syllabus_ids');
        if ($rawSyllabusIds === null || $rawSyllabusIds === '') {
            $merge['course_syllabus_ids'] = [];
        } elseif (is_array($rawSyllabusIds)) {
            $merge['course_syllabus_ids'] = array_values(array_unique(array_filter(array_map(
                static fn ($id): int => (int) $id,
                $rawSyllabusIds
            ), static fn (int $id): bool => $id > 0)));
        } else {
            $merge['course_syllabus_ids'] = [];
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $institutionDepartment = $this->route('institution_department');
        $institutionDepartmentId = $institutionDepartment instanceof InstitutionDepartment
            ? (int) $institutionDepartment->id
            : 0;

        $departmentLevelCourseId = $this->resolveDepartmentLevelCourseId();

        return [
            'students_per_class' => ['required', 'integer', 'min:1'],
            'department_level_id' => [
                'required',
                Rule::exists('department_levels', 'id')->where(
                    static fn ($query) => $query->where('institution_department_id', $institutionDepartmentId),
                ),
            ],
            'department_course_id' => [
                'required',
                Rule::exists('department_courses', 'id')->where(
                    static fn ($query) => $query->where('institution_department_id', $institutionDepartmentId),
                ),
            ],
            'mode_of_study_id' => ['required', 'exists:mode_of_studies,id'],
            'academic_year_option_id' => [
                'required',
                'integer',
                Rule::exists('academic_year_options', 'id')->where(function ($query): void {
                    $prefix = $this->calendarTypeSlugPrefix();

                    $query->where('slug', 'like', $prefix.'-%');
                }),
            ],
            'course_syllabus_ids' => ['nullable', 'array'],
            'course_syllabus_ids.*' => [
                'integer',
                Rule::exists('course_syllabuses', 'id')->where(function ($query) use ($departmentLevelCourseId, $institutionDepartmentId): void {
                    $query->where('institution_department_id', $institutionDepartmentId);
                    if ($departmentLevelCourseId !== null) {
                        $query->where('department_level_course_id', $departmentLevelCourseId);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ids = $this->input('course_syllabus_ids') ?? [];
            if ($ids === [] || ! is_array($ids)) {
                return;
            }

            $seen = [];
            foreach ($ids as $id) {
                $intId = (int) $id;
                if (isset($seen[$intId])) {
                    $validator->errors()->add('course_syllabus_ids', __('validation.distinct', ['attribute' => 'course_syllabus_ids']));

                    return;
                }
                $seen[$intId] = true;
            }

            if ($this->resolveDepartmentLevelCourseId() === null) {
                $validator->errors()->add(
                    'department_course_id',
                    __('validation.exists', ['attribute' => 'department course']),
                );
            }
        });
    }

    private function resolveDepartmentLevelCourseId(): ?int
    {
        $institutionDepartment = $this->route('institution_department');
        if (! $institutionDepartment instanceof InstitutionDepartment) {
            return null;
        }

        $departmentCourseId = (int) ($this->input('department_course_id') ?? 0);
        $departmentLevelId = (int) ($this->input('department_level_id') ?? 0);
        if ($departmentCourseId < 1 || $departmentLevelId < 1) {
            return null;
        }

        $id = DepartmentLevelCourse::query()
            ->where('department_course_id', $departmentCourseId)
            ->where('department_level_id', $departmentLevelId)
            ->whereHas('departmentCourse', static function ($query) use ($institutionDepartment): void {
                $query->where('institution_department_id', $institutionDepartment->id);
            })
            ->value('id');

        return $id !== null ? (int) $id : null;
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
