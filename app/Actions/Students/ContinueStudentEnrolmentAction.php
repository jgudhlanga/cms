<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Services\Students\ResolveStudentEnrolmentAttributesService;

class ContinueStudentEnrolmentAction
{
    public function __construct(
        protected ResolveStudentEnrolmentAttributesService $resolveStudentEnrolmentAttributes,
    ) {}

    public function execute(StudentApplication $studentApplication): StudentEnrolment
    {
        $studentApplication->loadMissing([
            'student',
            'classList',
            'institutionDepartment',
            'departmentLevel',
            'departmentCourse',
        ]);

        $classListId = $studentApplication->classList?->id
            ?? ClassList::query()
                ->where('student_application_id', $studentApplication->id)
                ->value('id');

        if ($classListId !== null) {
            ClassList::query()
                ->whereKey($classListId)
                ->update(['type' => ClassListTypeEnum::FINAL->value]);
        }

        $enrolledStep = WorkflowStep::query()
            ->where('slug', WorkflowStepEnum::ENROLLED->slug())
            ->first();

        if ($enrolledStep !== null) {
            $departmentStep = DepartmentApplicationStep::query()
                ->where('institution_department_id', $studentApplication->institution_department_id)
                ->where('workflow_step_id', $enrolledStep->id)
                ->first();

            $studentApplication->update([
                'department_application_step_id' => $departmentStep?->id,
            ]);
        }

        $enrolmentAttributes = $this->resolveStudentEnrolmentAttributes->resolve(
            (int) $studentApplication->student_id,
            (int) $studentApplication->id,
        );

        return StudentEnrolment::query()->updateOrCreate(
            [
                'student_id' => $studentApplication->student_id,
                'student_application_id' => $studentApplication->id,
                'institution_department_id' => $studentApplication->institution_department_id,
                'department_level_id' => $studentApplication->department_level_id,
                'department_course_id' => $studentApplication->department_course_id,
                'academic_year_option_id' => $enrolmentAttributes['academic_year_option_id'],
                'academic_calendar_id' => $enrolmentAttributes['academic_calendar_id'],
                'mode_of_study_id' => $studentApplication->mode_of_study_id,
            ],
            [
                'student_application_id' => $studentApplication->id,
                'student_enrolment_status_id' => $enrolmentAttributes['student_enrolment_status_id'],
                'mode_of_study_id' => $studentApplication->mode_of_study_id,
            ],
        );
    }
}
