<?php

declare(strict_types=1);

namespace App\Enums\Students;

use App\Models\Students\StudentStudyPositionConfirmation;

/**
 * Derived, never stored: where an enrolment stands for the current period. The values double as
 * the student index filter values.
 */
enum StudyPositionStateEnum: string
{
    case UNCONFIRMED = 'unconfirmed';
    case FOLLOW_UP = 'follow_up';
    case NEEDS_REVIEW = 'needs_review';
    case CONFIRMED = 'confirmed';

    public static function fromConfirmation(?StudentStudyPositionConfirmation $confirmation): self
    {
        if (! $confirmation instanceof StudentStudyPositionConfirmation) {
            return self::UNCONFIRMED;
        }

        return self::fromValues($confirmation->answer, $confirmation->sync_status);
    }

    public static function fromValues(StudyPositionAnswerEnum|string|null $answer, StudyPositionSyncStatusEnum|string|null $syncStatus): self
    {
        $answer = $answer instanceof StudyPositionAnswerEnum ? $answer : StudyPositionAnswerEnum::tryFrom((string) $answer);
        $syncStatus = $syncStatus instanceof StudyPositionSyncStatusEnum
            ? $syncStatus
            : StudyPositionSyncStatusEnum::tryFrom((string) $syncStatus);

        if ($answer === null) {
            return self::UNCONFIRMED;
        }

        if ($answer->isFollowUp()) {
            return self::FOLLOW_UP;
        }

        return $syncStatus === StudyPositionSyncStatusEnum::NEEDS_REVIEW ? self::NEEDS_REVIEW : self::CONFIRMED;
    }

    /**
     * Higher is worse; a student's overall state is their worst programme.
     */
    public function severity(): int
    {
        return match ($this) {
            self::UNCONFIRMED => 3,
            self::FOLLOW_UP => 2,
            self::NEEDS_REVIEW => 1,
            self::CONFIRMED => 0,
        };
    }

    public function label(): string
    {
        return __('students.study_position_state_'.$this->value);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $state): array => ['value' => $state->value, 'label' => $state->label()],
            self::cases(),
        );
    }
}
