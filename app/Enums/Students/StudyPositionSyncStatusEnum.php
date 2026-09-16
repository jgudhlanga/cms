<?php

declare(strict_types=1);

namespace App\Enums\Students;

/**
 * What happened to the student's records when the answer was saved.
 */
enum StudyPositionSyncStatusEnum: string
{
    // The records were moved to the confirmed phase.
    case APPLIED = 'applied';
    // The records already held the confirmed phase.
    case UNCHANGED = 'unchanged';
    // The answer was kept but the records could not be changed safely; registry must resolve it.
    case NEEDS_REVIEW = 'needs_review';
    // A follow-up answer ("not sure", "wrong programme"): there is no phase to write.
    case NOT_APPLICABLE = 'not_applicable';

    public function isSettled(): bool
    {
        return $this === self::APPLIED || $this === self::UNCHANGED;
    }
}
