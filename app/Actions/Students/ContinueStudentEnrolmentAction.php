<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Enrolments\ClassList;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;

class ContinueStudentEnrolmentAction
{
    public function __construct(
        protected UpsertYearStudentEnrolmentAction $upsertYearStudentEnrolment,
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
            $studentApplication->update([
                'workflow_step_id' => $enrolledStep->id,
            ]);
        }

        return $this->upsertYearStudentEnrolment->execute($studentApplication);
    }
}
