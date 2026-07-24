<?php

namespace App\Http\Requests\Institution;

use App\Enums\Rbac\RoleEnum;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\Staff;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Services\Institution\ResolveCalendarTypeSlugPrefixFromCourseSyllabus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CourseSyllabusModuleRequest extends FormRequest
{
    /** @return array<int, string> */
    private static function academicStaffRoleSlugs(): array
    {
        return [
            RoleEnum::LECTURER->value,
            RoleEnum::SENIOR_LECTURER->value,
            RoleEnum::LECTURER_IN_CHARGE->value,
            RoleEnum::HEAD_OF_DEPARTMENT->value,
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $moduleId = (int) ($this->route('course_syllabus_module')?->id ?? 0);
        $courseSyllabusId = (int) $this->input('course_syllabus_id', 0);
        $slugPrefix = app(ResolveCalendarTypeSlugPrefixFromCourseSyllabus::class)->resolve($courseSyllabusId);

        return [
            'course_syllabus_id' => ['required', 'exists:course_syllabuses,id'],
            'academic_year_option_id' => [
                'required',
                'integer',
                Rule::exists('academic_year_options', 'id')->where(function ($query) use ($slugPrefix): void {
                    $query->where('slug', 'like', $slugPrefix.'-%');
                }),
            ],
            'title' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('course_syllabus_modules', 'code')
                    ->where(fn ($query) => $query->where('course_syllabus_id', $courseSyllabusId))
                    ->ignore($moduleId),
            ],
            'duration_in_hours' => ['nullable', 'integer', 'min:1'],
            'nql_level' => ['nullable', 'integer', 'min:1'],
            'prerequisite_module_ids' => ['nullable', 'array'],
            'prerequisite_module_ids.*' => ['integer', 'distinct', 'exists:course_syllabus_modules,id'],
            'shared' => ['nullable', 'boolean'],
            'all_semesters' => ['nullable', 'boolean'],
            'capture_mark_only' => ['nullable', 'boolean'],
            'staff_ids' => ['nullable', 'array'],
            'staff_ids.*' => ['integer', 'distinct', Rule::exists('staff', 'id')->whereNull('deleted_at')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $module = $this->route('course_syllabus_module');

            if ($module !== null && $this->has('capture_mark_only')) {
                $requested = $this->boolean('capture_mark_only');
                $current = (bool) $module->capture_mark_only;

                if ($requested !== $current && CourseWorkMark::query()
                    ->where('course_syllabus_module_id', $module->id)
                    ->exists()) {
                    $validator->errors()->add(
                        'capture_mark_only',
                        __('syllabus.capture_mark_only_locked'),
                    );
                }
            }

            $staffIds = array_map('intval', $this->input('staff_ids', []));

            if ($staffIds === []) {
                return;
            }

            $courseSyllabusId = (int) $this->input('course_syllabus_id', 0);
            $institutionDepartmentId = CourseSyllabus::query()
                ->whereKey($courseSyllabusId)
                ->value('institution_department_id');

            $validStaff = Staff::query()
                ->whereIn('id', $staffIds)
                ->whereNull('deleted_at')
                ->when(
                    $institutionDepartmentId,
                    fn ($query) => $query->whereHas(
                        'institutionDepartments',
                        fn ($departmentQuery) => $departmentQuery->where(
                            'institution_departments.id',
                            (int) $institutionDepartmentId,
                        ),
                    ),
                )
                ->whereHas('user.roles', function ($query): void {
                    $query->whereIn('slug', self::academicStaffRoleSlugs());
                })
                ->pluck('id')
                ->all();

            foreach ($staffIds as $index => $staffId) {
                if (! in_array($staffId, $validStaff, true)) {
                    $validator->errors()->add(
                        "staff_ids.{$index}",
                        __('validation.exists', ['attribute' => 'staff_ids.'.$index]),
                    );
                }
            }
        });
    }
}
