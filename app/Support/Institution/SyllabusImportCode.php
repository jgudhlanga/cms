<?php

declare(strict_types=1);

namespace App\Support\Institution;

use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use RuntimeException;

final class SyllabusImportCode
{
    /**
     * @return list<string>
     */
    public static function segments(string $code): array
    {
        $code = trim($code);

        if ($code === '') {
            return [];
        }

        return array_values(array_filter(
            array_map(trim(...), preg_split('/[.\/]/', $code) ?: []),
            static fn (string $segment): bool => $segment !== '',
        ));
    }

    public static function comparisonKey(string $code): string
    {
        return implode('/', self::segments($code));
    }

    public static function equivalent(string $a, string $b): bool
    {
        return self::comparisonKey($a) === self::comparisonKey($b);
    }

    public static function implementationYear(string $courseCode): string
    {
        $segments = self::segments($courseCode);
        $yearSegment = $segments[1] ?? null;

        if ($yearSegment === null || ! preg_match('/^\d{2}$/', $yearSegment)) {
            throw new RuntimeException(
                "Invalid COURSE_CODE '{$courseCode}'. Expected second segment (after '/' or '.') to be a two-digit year."
            );
        }

        return "20{$yearSegment}";
    }

    public static function findStoredCourseCode(int $tenantId, string $code): ?string
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $storedCode = CourseSyllabus::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->value('code');

        if (is_string($storedCode)) {
            return $storedCode;
        }

        $comparisonKey = self::comparisonKey($code);

        foreach (CourseSyllabus::query()->where('tenant_id', $tenantId)->pluck('code') as $candidate) {
            if (self::comparisonKey((string) $candidate) === $comparisonKey) {
                return (string) $candidate;
            }
        }

        return null;
    }

    public static function findCourseSyllabusId(int $tenantId, string $code): ?int
    {
        $storedCode = self::findStoredCourseCode($tenantId, $code);

        if ($storedCode === null) {
            return null;
        }

        $id = CourseSyllabus::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $storedCode)
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    public static function courseSyllabusExists(int $tenantId, string $code): bool
    {
        return self::findStoredCourseCode($tenantId, $code) !== null;
    }

    public static function findStoredModuleCode(int $tenantId, int $courseSyllabusId, string $code): ?string
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $storedCode = CourseSyllabusModule::query()
            ->where('tenant_id', $tenantId)
            ->where('course_syllabus_id', $courseSyllabusId)
            ->where('code', $code)
            ->value('code');

        if (is_string($storedCode)) {
            return $storedCode;
        }

        $comparisonKey = self::comparisonKey($code);

        foreach (
            CourseSyllabusModule::query()
                ->where('tenant_id', $tenantId)
                ->where('course_syllabus_id', $courseSyllabusId)
                ->pluck('code') as $candidate
        ) {
            if (self::comparisonKey((string) $candidate) === $comparisonKey) {
                return (string) $candidate;
            }
        }

        return null;
    }

    public static function moduleExists(int $tenantId, int $courseSyllabusId, string $code): bool
    {
        return self::findStoredModuleCode($tenantId, $courseSyllabusId, $code) !== null;
    }
}
