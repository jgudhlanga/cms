<?php

namespace App\Support\AcademicCalendars;

final class CourseWorkMarkValue
{
    public const int MIN = 0;

    public const int MAX = 100;

    public static function isValid(mixed $value): bool
    {
        return self::tryParse($value) !== null;
    }

    public static function tryParse(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return null;
        }

        if (is_int($value)) {
            return self::inRange($value) ? $value : null;
        }

        if (is_float($value)) {
            if (! is_finite($value) || $value !== (float) (int) $value) {
                return null;
            }

            $int = (int) $value;

            return self::inRange($int) ? $int : null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '' || ! preg_match('/^\d+$/', $trimmed)) {
                return null;
            }

            $int = (int) $trimmed;

            return self::inRange($int) ? $int : null;
        }

        if (is_numeric($value)) {
            $numeric = (float) $value;

            if (! is_finite($numeric) || $numeric !== (float) (int) $numeric) {
                return null;
            }

            $int = (int) $numeric;

            return self::inRange($int) ? $int : null;
        }

        return null;
    }

    private static function inRange(int $value): bool
    {
        return $value >= self::MIN && $value <= self::MAX;
    }
}
