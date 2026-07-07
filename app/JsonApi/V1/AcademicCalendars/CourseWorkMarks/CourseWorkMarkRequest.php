<?php

namespace App\JsonApi\V1\AcademicCalendars\CourseWorkMarks;

use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Rules\AcademicCalendars\ValidCourseWorkMark;
use Illuminate\Validation\Validator;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class CourseWorkMarkRequest extends ResourceRequest
{
    public function rules(): array
    {
        $isCreating = $this->isCreating();

        return [
            'studentEnrolmentId' => [$isCreating ? 'required' : 'sometimes', 'integer', 'exists:student_enrolments,id'],
            'courseSyllabusModuleId' => [$isCreating ? 'required' : 'sometimes', 'integer', 'exists:course_syllabus_modules,id'],
            'assessmentTypeId' => ['nullable', 'integer', 'exists:assessment_types,id'],
            'mark' => ['nullable', new ValidCourseWorkMark(allowNull: true)],
            'remark' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $moduleId = (int) ($this->input('data.attributes.courseSyllabusModuleId')
                ?? $this->input('courseSyllabusModuleId')
                ?? $this->route('course_work_mark')?->course_syllabus_module_id
                ?? 0);

            if ($moduleId < 1) {
                return;
            }

            $module = CourseSyllabusModule::query()->find($moduleId);

            if ($module === null) {
                return;
            }

            $assessmentTypeId = $this->input('data.attributes.assessmentTypeId')
                ?? $this->input('assessmentTypeId');

            if ($module->capture_mark_only) {
                if ($assessmentTypeId !== null && $assessmentTypeId !== '') {
                    $validator->errors()->add(
                        'assessmentTypeId',
                        __('academic_calendar.course_work_mark_only_no_assessment'),
                    );
                }

                return;
            }

            if ($this->isCreating() && ($assessmentTypeId === null || $assessmentTypeId === '')) {
                $validator->errors()->add(
                    'assessmentTypeId',
                    __('academic_calendar.course_work_assessment_required'),
                );
            }
        });
    }
}
