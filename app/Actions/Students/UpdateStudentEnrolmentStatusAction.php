<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Support\Facades\DB;

class UpdateStudentEnrolmentStatusAction
{
    /**
     * @var list<string>
     */
    private const ALLOWED_SLUGS = [
        StudentEnrolmentProgressionService::STATUS_ACTIVE,
        StudentEnrolmentProgressionService::STATUS_ABSENT,
        StudentEnrolmentProgressionService::STATUS_AWARD,
        StudentEnrolmentProgressionService::STATUS_DEFERRED,
        StudentEnrolmentProgressionService::STATUS_DISQUALIFIED,
        StudentEnrolmentProgressionService::STATUS_PROCEED,
        StudentEnrolmentProgressionService::STATUS_REFERRED,
    ];

    public function __construct(
        protected StudentEnrolmentProgressionService $progression,
    ) {}

    public function execute(StudentEnrolment|StudentSemester $target, string $statusSlug): void
    {
        if ($target instanceof StudentSemester) {
            $this->executeForSemester($target, $statusSlug);

            return;
        }

        $target->loadMissing(['studentApplication', 'studentEnrolmentStatus', 'departmentLevel.level', 'studentSemesters']);

        if (! in_array($statusSlug, self::ALLOWED_SLUGS, true)) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_status_invalid'),
            );
        }

        if ($statusSlug === StudentEnrolmentProgressionService::STATUS_AWARD
            && ! $this->progression->isLastPhase($target)
        ) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_cannot_complete_level'),
            );
        }

        $statusId = $this->progression->statusIdBySlug($statusSlug);

        if ($statusId === null) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_status_invalid'),
            );
        }

        $currentSemester = $this->progression->currentStudentSemester($target);

        DB::transaction(function () use ($target, $statusId, $currentSemester): void {
            if ($currentSemester instanceof StudentSemester) {
                $this->progression->updateStudentSemesterStatus($currentSemester, $statusId);

                return;
            }

            $this->progression->updateEnrolmentStatus($target, $statusId);
        });
    }

    private function executeForSemester(StudentSemester $studentSemester, string $statusSlug): void
    {
        $studentSemester->loadMissing(['enrolment.studentApplication', 'enrolment.departmentLevel.level']);

        if (! in_array($statusSlug, self::ALLOWED_SLUGS, true)) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_status_invalid'),
            );
        }

        $enrolment = $studentSemester->enrolment;

        if ($statusSlug === StudentEnrolmentProgressionService::STATUS_AWARD
            && (! $enrolment instanceof StudentEnrolment
                || ! $this->progression->isLastPhaseSemester($enrolment, $studentSemester))
        ) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_cannot_complete_level'),
            );
        }

        $statusId = $this->progression->statusIdBySlug($statusSlug);

        if ($statusId === null) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_status_invalid'),
            );
        }

        DB::transaction(function () use ($studentSemester, $statusId): void {
            $this->progression->updateStudentSemesterStatus($studentSemester, $statusId);
        });
    }
}
