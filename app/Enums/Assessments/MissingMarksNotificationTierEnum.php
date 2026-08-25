<?php

namespace App\Enums\Assessments;

enum MissingMarksNotificationTierEnum: string
{
    case First = 'first';
    case Second = 'second';
    case Due = 'due';

    public function includesLecturers(): bool
    {
        return $this !== self::Due;
    }

    public function includesVicePrincipal(): bool
    {
        return $this !== self::First;
    }

    public function daysColumn(): string
    {
        return match ($this) {
            self::First => 'first_notification_days_before',
            self::Second => 'second_notification_days_before',
            self::Due => 'due_notification_days_before',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::First => __('assessments.missing_marks_tier_first'),
            self::Second => __('assessments.missing_marks_tier_second'),
            self::Due => __('assessments.missing_marks_tier_due'),
        };
    }
}
