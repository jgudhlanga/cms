<?php

namespace App\Helpers;

use App\Enums\Institution\LevelEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;

class EnrolmentHelper
{
    public static function resolveStudentNumber(StudentProgram $program): string
    {
        $student = $program->student;
        $identity = $student->isZimbabwean() ? $student->id_number : $student->passport_number;
        // Try to find an existing legacy student number
        $studentNumber = Helper::lookupLegacyStudentNumber($identity);
        // If legacy student number exists, return it
        if (!empty($studentNumber)) {
            return $studentNumber;
        }
        // Otherwise generate a new student number
        return Helper::generateStudentNumber($student, $program->institutionDepartment);
    }


    public static function isEntryLevel(StudentProgram $program): bool
    {
        $entryLevels = [
            strtolower(LevelEnum::NC->name()),
            strtolower(LevelEnum::SDP->name()),
            strtolower(LevelEnum::ABMA_LEVEL_3->name()),
        ];

        $levelName = strtolower(optional($program->departmentLevel->level)->name);

        return in_array($levelName, $entryLevels, true);
    }

    public static function rejectOtherApplications(Student $student, StudentProgram $currentProgram): void
    {
        $rejectedStepId = WorkflowStep::where('slug', WorkflowStepEnum::REJECTED->slug())->value('id');

        $otherPrograms = $student->programs()
            ->where('id', '!=', $currentProgram->id)
            ->with('classList')
            ->get();

        foreach ($otherPrograms as $program) {
            $stepId = DepartmentApplicationStep::where([
                'institution_department_id' => $program->institution_department_id,
                'workflow_step_id' => $rejectedStepId,
            ])->value('id');

            $program->update(['department_application_step_id' => $stepId]);

            if ($program->classList instanceof ClassList) {
                $program->classList()->update([
                    'type' => ClassListTypeEnum::FAILED->value,
                ]);
            }
        }
    }
}
