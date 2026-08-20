<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Models\Students\StudentEnrolment;
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

    public function execute(StudentEnrolment $enrolment, string $statusSlug): void
    {
        $enrolment->loadMissing(['studentApplication', 'studentEnrolmentStatus', 'departmentLevel.level']);

        if (! in_array($statusSlug, self::ALLOWED_SLUGS, true)) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_status_invalid'),
            );
        }

        if ($statusSlug === StudentEnrolmentProgressionService::STATUS_AWARD
            && ! $this->progression->isLastPhase($enrolment)
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

        DB::transaction(function () use ($enrolment, $statusId): void {
            $this->progression->updateEnrolmentStatus($enrolment, $statusId);
        });
    }
}
