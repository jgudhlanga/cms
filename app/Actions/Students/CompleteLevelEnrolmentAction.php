<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Support\Facades\DB;

class CompleteLevelEnrolmentAction
{
    public function __construct(
        protected StudentEnrolmentProgressionService $progression,
    ) {}

    public function execute(StudentEnrolment|StudentSemester $target): void
    {
        if ($target instanceof StudentSemester) {
            $this->executeForSemester($target);

            return;
        }

        $target->loadMissing(['studentApplication', 'studentEnrolmentStatus', 'departmentLevel.level', 'studentSemesters']);

        if (! $this->progression->canCompleteLevel($target)) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_cannot_complete_level'),
            );
        }

        $currentSemester = $this->progression->currentStudentSemester($target);

        if ($currentSemester instanceof StudentSemester) {
            $this->executeForSemester($currentSemester);

            return;
        }

        $statusId = $this->progression->statusIdBySlug(StudentEnrolmentProgressionService::STATUS_AWARD);

        if ($statusId === null) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_cannot_complete_level'),
            );
        }

        DB::transaction(function () use ($target, $statusId): void {
            $this->progression->updateEnrolmentStatus($target, $statusId);
        });
    }

    private function executeForSemester(StudentSemester $studentSemester): void
    {
        $studentSemester->loadMissing(['enrolment.studentApplication', 'enrolment.departmentLevel.level', 'studentEnrolmentStatus']);

        $enrolment = $studentSemester->enrolment;

        if (! $enrolment instanceof StudentEnrolment) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_cannot_complete_level'),
            );
        }

        if (! $this->progression->canCompleteLevelSemester($studentSemester)) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_cannot_complete_level'),
            );
        }

        $statusId = $this->progression->statusIdBySlug(StudentEnrolmentProgressionService::STATUS_AWARD);

        if ($statusId === null) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_cannot_complete_level'),
            );
        }

        DB::transaction(function () use ($studentSemester, $statusId): void {
            $this->progression->updateStudentSemesterStatus($studentSemester, $statusId);
        });
    }
}
