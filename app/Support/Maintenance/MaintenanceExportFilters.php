<?php

declare(strict_types=1);

namespace App\Support\Maintenance;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;

/**
 * Canonical filter payload shared by the maintenance enrolment and application
 * exports, their preview screens, the queued jobs and the console commands.
 */
final class MaintenanceExportFilters
{
    /**
     * @var list<string>
     */
    public const STUDENT_KEYS = [
        'search',
        'department',
        'level',
        'course',
        'mode_of_study',
        'gender',
        'student_type',
        'sponsored',
        'disability',
    ];

    /**
     * @var list<string>
     */
    public const ENROLMENT_KEYS = [
        'intake_year',
        'calendar_year',
        'semester_id',
        'calendar_type',
    ];

    /**
     * @var list<string>
     */
    public const APPLICATION_KEYS = [
        'intake_year',
        'intake_period_id',
        'applied_from',
        'applied_to',
    ];

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    public static function normalize(array $input, array $keys): array
    {
        $filters = [];

        foreach ($keys as $key) {
            $value = self::normalizeValue($key, $input[$key] ?? null);

            if ($value !== null) {
                $filters[$key] = $value;
            }
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizeForEnrolments(array $input): array
    {
        return self::normalize($input, [...self::STUDENT_KEYS, ...self::ENROLMENT_KEYS]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizeForApplications(array $input): array
    {
        return self::normalize($input, [...self::STUDENT_KEYS, ...self::APPLICATION_KEYS]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function studentRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'array'],
            'department.*' => ['integer', 'min:1'],
            'level' => ['nullable', 'array'],
            'level.*' => ['integer', 'min:1'],
            'course' => ['nullable', 'array'],
            'course.*' => ['integer', 'min:1'],
            'mode_of_study' => ['nullable', 'array'],
            'mode_of_study.*' => ['integer', 'min:1'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'student_type' => ['nullable', 'string', 'in:direct,apprentice'],
            'sponsored' => ['nullable', 'string', 'in:sponsored,not_sponsored'],
            'disability' => ['nullable', 'string', 'in:yes,no'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function enrolmentRules(): array
    {
        return [
            ...self::studentRules(),
            'intake_year' => ['nullable', 'string', 'max:20'],
            'calendar_year' => ['nullable', 'string', 'max:20'],
            'semester_id' => ['nullable', 'integer', 'min:1'],
            'calendar_type' => ['nullable', 'string', 'in:'.implode(',', self::calendarTypes())],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function applicationRules(): array
    {
        return [
            ...self::studentRules(),
            'intake_year' => ['nullable', 'string', 'max:20'],
            'intake_period_id' => ['nullable', 'integer', 'min:1'],
            'applied_from' => ['nullable', 'date'],
            'applied_to' => ['nullable', 'date', 'after_or_equal:applied_from'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function calendarTypes(): array
    {
        return array_map(
            static fn (AcademicCalendarTypeEnum $type): string => $type->value,
            AcademicCalendarTypeEnum::cases(),
        );
    }

    private static function normalizeValue(string $key, mixed $value): mixed
    {
        return match ($key) {
            'department', 'level', 'course', 'mode_of_study' => self::intList($value),
            'semester_id', 'intake_period_id' => self::positiveInt($value),
            default => self::nonEmptyString($value),
        };
    }

    /**
     * @return list<int>|null
     */
    private static function intList(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', (array) $value),
            static fn (int $id): bool => $id > 0,
        )));

        return $ids === [] ? null : $ids;
    }

    private static function positiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private static function nonEmptyString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
