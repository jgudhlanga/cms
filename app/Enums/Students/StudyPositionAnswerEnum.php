<?php

declare(strict_types=1);

namespace App\Enums\Students;

enum StudyPositionAnswerEnum: string
{
    case PHASE = 'phase';
    case NOT_SURE = 'not_sure';
    case WRONG_PROGRAMME = 'wrong_programme';

    public function label(): string
    {
        return match ($this) {
            self::PHASE => __('students.study_position_answer_phase'),
            self::NOT_SURE => __('students.study_position_answer_not_sure'),
            self::WRONG_PROGRAMME => __('students.study_position_answer_wrong_programme'),
        };
    }

    /**
     * Answers that leave the phase unknown and need the department to follow up.
     */
    public function isFollowUp(): bool
    {
        return $this !== self::PHASE;
    }
}
