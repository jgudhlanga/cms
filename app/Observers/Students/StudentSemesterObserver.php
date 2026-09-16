<?php

declare(strict_types=1);

namespace App\Observers\Students;

use App\Models\Students\StudentSemester;
use App\Services\Students\ProgrammeStageCompletionService;

class StudentSemesterObserver
{
    public function saved(StudentSemester $studentSemester): void
    {
        $studentSemester->loadMissing([
            'enrolment.student',
            'enrolment.studentApplication',
            'programmeSemester.programmeStage',
        ]);

        $student = $studentSemester->enrolment?->student;
        $stage = $studentSemester->programmeSemester?->programmeStage;

        if ($student === null || $stage === null) {
            return;
        }

        app(ProgrammeStageCompletionService::class)->recordIfComplete(
            $student,
            $stage,
            $studentSemester->enrolment?->studentApplication,
        );
    }
}
