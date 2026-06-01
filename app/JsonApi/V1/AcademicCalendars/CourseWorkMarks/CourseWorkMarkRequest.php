<?php

namespace App\JsonApi\V1\AcademicCalendars\CourseWorkMarks;

use App\Rules\AcademicCalendars\ValidCourseWorkMark;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class CourseWorkMarkRequest extends ResourceRequest
{
    public function rules(): array
    {
        $isCreating = $this->isCreating();

        return [
            'studentEnrolmentId' => [$isCreating ? 'required' : 'sometimes', 'integer', 'exists:student_enrolments,id'],
            'courseSyllabusModuleId' => [$isCreating ? 'required' : 'sometimes', 'integer', 'exists:course_syllabus_modules,id'],
            'assessmentTypeId' => [$isCreating ? 'required' : 'sometimes', 'integer', 'exists:assessment_types,id'],
            'mark' => ['nullable', new ValidCourseWorkMark(allowNull: true)],
            'remark' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
