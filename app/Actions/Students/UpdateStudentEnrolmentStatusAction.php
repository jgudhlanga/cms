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
        StudentEnrolmentProgressionService::STATUS_REPEAT,
        StudentEnrolmentProgressionService::STATUS_DEFERRED,
        StudentEnrolmentProgressionService::STATUS_COMPLETED,
        StudentEnrolmentProgressionService::STATUS_ACTIVE,
        'repeat-re-write',
        'deferred-postponed',
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

        if ($statusSlug === StudentEnrolmentProgressionService::STATUS_COMPLETED
            && ! $this->progression->isLastPhase($enrolment)
        ) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_cannot_complete_level'),
            );
        }

        $studentApplication = $enrolment->studentApplication;
        $statusId = $this->progression->statusIdBySlug($statusSlug);

        if ($studentApplication === null || $statusId === null) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_status_invalid'),
            );
        }

        DB::transaction(function () use ($studentApplication, $statusId): void {
            $this->progression->syncStatusForApplication($studentApplication, $statusId);
        });
    }
}
