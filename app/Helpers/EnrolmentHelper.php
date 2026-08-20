<?php

namespace App\Helpers;

use App\Enums\Institution\LevelEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Enrolments\ClassList;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;

class EnrolmentHelper
{
    public static function resolveStudentNumber(StudentApplication $program): string
    {
        $student = $program->student;
        $identity = $student->isZimbabwean() ? $student->id_number : $student->passport_number;
        // Try to find an existing legacy student number
        $studentNumber = Helper::lookupLegacyStudentNumber($identity);
        // If legacy student number exists, return it
        if (! empty($studentNumber)) {
            return $studentNumber;
        }

        // Otherwise generate a new student number
        return Helper::generateStudentNumber($program);
    }

    public static function isEntryLevel(StudentApplication $program): bool
    {
        $entryLevels = [
            strtolower(LevelEnum::NC->name()),
            strtolower(LevelEnum::SDP->name()),
            strtolower(LevelEnum::ABMA_LEVEL_3->name()),
        ];

        $levelName = strtolower(optional($program->departmentLevel->level)->name);

        return in_array($levelName, $entryLevels, true);
    }

    public static function rejectOtherApplications(Student $student, StudentApplication $currentProgram): void
    {
        $rejectedStepId = WorkflowStep::where('slug', WorkflowStepEnum::REJECTED->slug())->value('id');

        $otherPrograms = $student->applications()
            ->where('id', '!=', $currentProgram->id)
            ->with('classList')
            ->get();

        foreach ($otherPrograms as $program) {
            $program->update(['workflow_step_id' => $rejectedStepId]);

            if ($program->classList instanceof ClassList) {
                $program->classList()->update([
                    'type' => ClassListTypeEnum::FAILED->value,
                ]);
            }
        }
    }
}
