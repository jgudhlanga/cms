<?php

namespace App\Support\Examinations;

use Carbon\Carbon;
use DateTimeInterface;
use InvalidArgumentException;

final class ExaminationDumpColumns
{
    public const string DISCIPLINE = 'Discipline';

    public const string COURSE_CODE = 'Course Code';

    public const string CANDIDATE_NUMBER = 'Candidate_Number';

    public const string SURNAME = 'Surname';

    public const string FIRST_NAMES = 'First_Names';

    public const string SUBJECT_CODE = 'Subject Code';

    public const string SUBJECT = 'Subject';

    public const string GRADE = 'Grade';

    public const string SESSION = 'Session';

    public const string COURSE_COMMENT = 'Course Comment';

    /**
     * @return list<string>
     */
    public static function requiredHeaders(): array
    {
        return [
            self::DISCIPLINE,
            self::COURSE_CODE,
            self::CANDIDATE_NUMBER,
            self::SURNAME,
            self::FIRST_NAMES,
            self::SUBJECT_CODE,
            self::SUBJECT,
            self::GRADE,
            self::SESSION,
            self::COURSE_COMMENT,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    public static function cell(array $row, string $header): ?string
    {
        if (! array_key_exists($header, $row)) {
            return null;
        }

        $value = $row[$header];

        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    public static function excelSerialToDate(?string $session): ?Carbon
    {
        if ($session === null || $session === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $session) === 1) {
            try {
                return Carbon::parse($session)->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        if (! is_numeric($session)) {
            return null;
        }

        $serial = (float) $session;

        // Excel serial date (Windows / 1900 date system).
        $unix = (int) round(($serial - 25569) * 86400);

        return Carbon::createFromTimestampUTC($unix)->startOfDay();
    }

    /**
     * @param  list<string|null>  $headers
     * @return list<string>
     */
    public static function assertHeadersPresent(array $headers): array
    {
        $normalized = array_values(array_filter(
            array_map(static fn ($h) => $h === null ? null : trim((string) $h), $headers),
            static fn (?string $h): bool => $h !== null && $h !== '',
        ));

        $missing = array_values(array_diff(self::requiredHeaders(), $normalized));

        if ($missing !== []) {
            throw new InvalidArgumentException(
                __('examinations.import_missing_headers', ['headers' => implode(', ', $missing)])
            );
        }

        return $normalized;
    }
}
