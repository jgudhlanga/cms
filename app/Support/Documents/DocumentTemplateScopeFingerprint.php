<?php

declare(strict_types=1);

namespace App\Support\Documents;

final class DocumentTemplateScopeFingerprint
{
    public static function make(
        int $documentTypeId,
        ?int $intakePeriodId,
        ?int $institutionDepartmentId,
        ?int $levelId,
        ?int $courseId,
        ?int $modeOfStudyId,
    ): string {
        return implode('|', [
            $documentTypeId,
            self::token($intakePeriodId),
            self::token($institutionDepartmentId),
            self::token($levelId),
            self::token($courseId),
            self::token($modeOfStudyId),
        ]);
    }

    public static function isConcrete(string $fingerprint): bool
    {
        $parts = explode('|', $fingerprint);

        return collect(array_slice($parts, 1))->contains(fn (string $part): bool => $part !== 'n');
    }

    private static function token(?int $id): string
    {
        return $id !== null && $id > 0 ? (string) $id : 'n';
    }
}
