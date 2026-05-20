<?php

namespace App\Http\Requests\Institution;

use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Services\Institution\ResolveCalendarTypeSlugPrefixFromCourseSyllabus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MoveCourseSyllabusModulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courseSyllabus = $this->route('course_syllabus');
        $courseSyllabusId = $courseSyllabus instanceof CourseSyllabus ? (int) $courseSyllabus->id : 0;
        $slugPrefix = app(ResolveCalendarTypeSlugPrefixFromCourseSyllabus::class)->resolve($courseSyllabusId);

        return [
            'course_syllabus_module_ids' => ['required', 'array', 'min:1'],
            'course_syllabus_module_ids.*' => ['integer', 'distinct', 'exists:course_syllabus_modules,id'],
            'target_academic_year_option_id' => [
                'required',
                'integer',
                Rule::exists('academic_year_options', 'id')->where(function ($query) use ($slugPrefix): void {
                    $query->where('slug', 'like', $slugPrefix.'-%');
                }),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $courseSyllabus = $this->route('course_syllabus');

            if (! $courseSyllabus instanceof CourseSyllabus) {
                return;
            }

            /** @var array<int, int> $moduleIds */
            $moduleIds = array_map('intval', $this->input('course_syllabus_module_ids', []));
            $targetOptionId = (int) $this->input('target_academic_year_option_id');

            $countOnSyllabus = CourseSyllabusModule::query()
                ->where('course_syllabus_id', $courseSyllabus->id)
                ->whereIn('id', $moduleIds)
                ->count();

            if ($countOnSyllabus !== count($moduleIds)) {
                $validator->errors()->add('course_syllabus_module_ids', __('syllabus.move_modules_not_all_on_syllabus'));

                return;
            }

            $allAlreadyOnTarget = CourseSyllabusModule::query()
                ->whereIn('id', $moduleIds)
                ->where('academic_year_option_id', $targetOptionId)
                ->count() === count($moduleIds);

            if ($allAlreadyOnTarget) {
                $validator->errors()->add('target_academic_year_option_id', __('syllabus.move_modules_same_period'));
            }
        });
    }
}
