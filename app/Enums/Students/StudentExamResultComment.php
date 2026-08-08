<?php

declare(strict_types=1);

namespace App\Enums\Students;

enum StudentExamResultComment: string
{
    case Absent = 'ABSENT';
    case Award = 'AWARD';
    case Deferred = 'DEFERRED';
    case Disqualified = 'DISQUALIFIED';
    case Proceed = 'PROCEED';
    case Referred = 'REFERRED';

    public function label(): string
    {
        return match ($this) {
            self::Absent => __('examinations.comment_absent'),
            self::Award => __('examinations.comment_award'),
            self::Deferred => __('examinations.comment_deferred'),
            self::Disqualified => __('examinations.comment_disqualified'),
            self::Proceed => __('examinations.comment_proceed'),
            self::Referred => __('examinations.comment_referred'),
        };
    }

    public static function tryFromCourseComment(?string $courseComment): ?self
    {
        if ($courseComment === null) {
            return null;
        }

        $normalized = strtoupper(trim($courseComment));

        if ($normalized === '') {
            return null;
        }

        return self::tryFrom($normalized);
    }
}
