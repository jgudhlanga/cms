<?php

namespace App\Http\Requests\Institution;

use App\Services\Institution\ResolveCalendarTypeSlugPrefixFromCourseSyllabus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseSyllabusModuleRequest extends FormRequest
{
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
            'code' => ['required', 'string', 'max:255', Rule::unique('course_syllabus_modules', 'code')->ignore($moduleId)],
            'duration_in_hours' => ['nullable', 'integer', 'min:1'],
            'nql_level' => ['nullable', 'integer', 'min:1'],
            'prerequisite_module_ids' => ['nullable', 'array'],
            'prerequisite_module_ids.*' => ['integer', 'distinct', 'exists:course_syllabus_modules,id'],
            'shared' => ['nullable', 'boolean'],
        ];
    }
}
