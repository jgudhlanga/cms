<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Enrolments\ClassList;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use Carbon\CarbonInterface;

class ContinueStudentEnrolmentAction
{
    public function __construct(
        protected UpsertYearStudentEnrolmentAction $upsertYearStudentEnrolment,
    ) {}

    /**
     * @param  CarbonInterface|null  $asOf  Point in time the enrolment is being made as of. Null
     *                                      means "now", which is what the normal finalisation flow
     *                                      wants. Reconciliation of a past year passes an anchor
     *                                      inside that year so the enrolment does not land in the
     *                                      current academic calendar.
     */
    public function execute(StudentApplication $studentApplication, ?CarbonInterface $asOf = null): StudentEnrolment
    {
        $studentApplication->loadMissing([
            'student',
            'classList',
            'institutionDepartment',
            'departmentLevel',
            'departmentCourse',
        ]);

        $classList = $studentApplication->classList
            ?? ClassList::query()
                ->where('student_application_id', $studentApplication->id)
                ->first();

        // Update through the model, not the query builder: ClassList is activity-logged, and a
        // query-builder update bypasses Eloquent events, leaving this irreversible status change
        // with no audit trail.
        if ($classList instanceof ClassList) {
            $classList->update(['type' => ClassListTypeEnum::FINAL->value]);
        }

        $enrolledStep = WorkflowStep::query()
            ->where('slug', WorkflowStepEnum::ENROLLED->slug())
            ->first();

        if ($enrolledStep !== null) {
            $studentApplication->update([
                'workflow_step_id' => $enrolledStep->id,
            ]);
        }

        return $this->upsertYearStudentEnrolment->execute($studentApplication, $asOf);
    }
}
