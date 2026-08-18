<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Models\Students\StudentEnrolment;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Support\Facades\DB;

class CompleteLevelEnrolmentAction
{
    public function __construct(
        protected StudentEnrolmentProgressionService $progression,
    ) {}

    public function execute(StudentEnrolment $enrolment): void
    {
        $enrolment->loadMissing(['studentApplication', 'studentEnrolmentStatus', 'departmentLevel.level']);

        if (! $this->progression->canCompleteLevel($enrolment)) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_cannot_complete_level'),
            );
        }

        $studentApplication = $enrolment->studentApplication;
        $statusId = $this->progression->statusIdBySlug(StudentEnrolmentProgressionService::STATUS_COMPLETED);

        if ($studentApplication === null || $statusId === null) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_cannot_complete_level'),
            );
        }

        DB::transaction(function () use ($studentApplication, $statusId): void {
            $this->progression->syncStatusForApplication($studentApplication, $statusId);
        });
    }
}
