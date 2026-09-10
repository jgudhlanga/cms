<?php

declare(strict_types=1);

namespace App\Enums\AcademicCalendars;

enum EnrolmentProgressionImportAction: string
{
    case CompleteLevel = 'complete-level';
    case AdvancePhase = 'advance-phase';

    public function label(): string
    {
        return match ($this) {
            self::CompleteLevel => __('academic_calendar.progression_import_action_complete_level'),
            self::AdvancePhase => __('academic_calendar.progression_import_action_advance_phase'),
        };
    }
}
