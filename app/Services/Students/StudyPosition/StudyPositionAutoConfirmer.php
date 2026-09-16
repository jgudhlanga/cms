<?php

declare(strict_types=1);

namespace App\Services\Students\StudyPosition;

use App\Actions\Students\ConfirmStudyPositionAction;
use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionSyncStatusEnum;
use App\Models\Students\StudentEnrolment;

/**
 * Runs the detector for one enrolment and records what it found. Anything already recorded, a
 * refused write, or no evidence leaves the enrolment for the student to answer.
 */
class StudyPositionAutoConfirmer
{
    public const APPLIED = 'applied';

    public const KEPT = 'kept';

    public const CONFLICT = 'conflict';

    public const NO_EVIDENCE = 'no_evidence';

    public const REFUSED = 'refused';

    public function __construct(
        private readonly StudyPositionAutoDetector $detector,
        private readonly ConfirmStudyPositionAction $confirm,
        private readonly CurrentStudyPeriodResolver $periods,
    ) {}

    public function run(StudentEnrolment $enrolment, ?CurrentStudyPeriod $period = null): string
    {
        $period ??= $this->periods->forEnrolment($enrolment);

        if (! $period instanceof CurrentStudyPeriod) {
            return self::NO_EVIDENCE;
        }

        $detection = $this->detector->detect($enrolment, $period);

        if ($detection->outcome === StudyPositionDetection::CONFLICT) {
            return self::CONFLICT;
        }

        if (! $detection->isFound() || $detection->phase === null || $detection->source === null) {
            return self::NO_EVIDENCE;
        }

        $confirmation = $this->confirm->execute(
            $enrolment,
            StudyPositionAnswerEnum::PHASE,
            $detection->phase,
            $detection->source,
            evidence: $detection->evidence,
        );

        if ($confirmation === null || $confirmation->source !== $detection->source) {
            return self::REFUSED;
        }

        return $confirmation->sync_status === StudyPositionSyncStatusEnum::APPLIED ? self::APPLIED : self::KEPT;
    }
}
