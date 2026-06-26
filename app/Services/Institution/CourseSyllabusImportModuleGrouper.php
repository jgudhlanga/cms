<?php

declare(strict_types=1);

namespace App\Services\Institution;

use App\Support\Institution\SyllabusImportCode;
use Throwable;

final class CourseSyllabusImportModuleGrouper
{
    /**
     * @param  list<array{number: int, data: array<string, mixed>}>  $parsedRows
     * @return array<int, array{
     *     allSemesters: bool,
     *     moduleImportRole: 'primary'|'skip',
     *     groupKey: string|null,
     *     moduleSpansAllPeriods: bool,
     *     moduleDuplicateSamePeriod: bool,
     * }>
     */
    public function group(array $parsedRows, int $tenantId, int $institutionDepartmentId): array
    {
        /** @var array<string, list<array{number: int, optionId: int|null}>> $groups */
        $groups = [];
        $meta = [];

        foreach ($parsedRows as $parsedRow) {
            $rowNumber = (int) $parsedRow['number'];
            $rowData = $parsedRow['data'];
            $moduleCode = trim((string) ($rowData['MODULE_CODE'] ?? ''));
            $moduleTitle = trim((string) ($rowData['MODULE_TITLE'] ?? ''));

            if ($moduleCode === '' && $moduleTitle === '') {
                $meta[$rowNumber] = $this->emptyModuleMeta();

                continue;
            }

            $courseCode = trim((string) ($rowData['COURSE_CODE'] ?? ''));
            $groupKey = $this->groupKey($courseCode, $moduleCode);

            if ($groupKey === null) {
                $meta[$rowNumber] = $this->defaultMeta(null);

                continue;
            }

            $optionId = $this->resolveOptionId(
                $rowData,
                $tenantId,
                $institutionDepartmentId,
                $courseCode,
            );

            $groups[$groupKey] ??= [];
            $groups[$groupKey][] = [
                'number' => $rowNumber,
                'optionId' => $optionId,
            ];
        }

        foreach ($parsedRows as $parsedRow) {
            $rowNumber = (int) $parsedRow['number'];

            if (isset($meta[$rowNumber])) {
                continue;
            }

            $rowData = $parsedRow['data'];
            $groupKey = $this->groupKey(
                trim((string) ($rowData['COURSE_CODE'] ?? '')),
                trim((string) ($rowData['MODULE_CODE'] ?? '')),
            );

            if ($groupKey === null || ! isset($groups[$groupKey])) {
                $meta[$rowNumber] = $this->defaultMeta($groupKey);

                continue;
            }

            $members = $groups[$groupKey];
            $uniqueOptionIds = array_values(array_unique(array_filter(
                array_column($members, 'optionId'),
                static fn (?int $id): bool => $id !== null,
            )));
            $optionCounts = [];

            foreach ($members as $member) {
                if ($member['optionId'] === null) {
                    continue;
                }

                $optionCounts[$member['optionId']] = ($optionCounts[$member['optionId']] ?? 0) + 1;
            }

            $moduleSpansAllPeriods = count($uniqueOptionIds) >= 2;
            $moduleDuplicateSamePeriod = count(array_filter($optionCounts, static fn (int $count): bool => $count > 1)) > 0;
            $allSemesters = $moduleSpansAllPeriods;
            $primaryRowNumber = min(array_column($members, 'number'));
            $moduleImportRole = $rowNumber === $primaryRowNumber ? 'primary' : 'skip';

            if (! $moduleSpansAllPeriods) {
                $moduleImportRole = 'primary';
            }

            $meta[$rowNumber] = [
                'allSemesters' => $allSemesters,
                'moduleImportRole' => $moduleImportRole,
                'groupKey' => $groupKey,
                'moduleSpansAllPeriods' => $moduleSpansAllPeriods,
                'moduleDuplicateSamePeriod' => $moduleDuplicateSamePeriod,
            ];
        }

        return $meta;
    }

    /**
     * @param  array<string, mixed>  $rowData
     */
    private function resolveOptionId(
        array $rowData,
        int $tenantId,
        int $institutionDepartmentId,
        string $courseCode,
    ): ?int {
        $semester = trim((string) ($rowData['SEMESTER'] ?? ''));

        if ($semester === '') {
            return null;
        }

        try {
            return app(ResolveAcademicYearOptionFromImport::class)->resolve(
                $semester,
                SyllabusImportCode::findCourseSyllabusId($tenantId, $courseCode),
                $institutionDepartmentId,
                (string) ($rowData['LEVEL'] ?? ''),
            );
        } catch (Throwable) {
            return null;
        }
    }

    private function groupKey(string $courseCode, string $moduleCode): ?string
    {
        if ($courseCode === '' || $moduleCode === '') {
            return null;
        }

        return SyllabusImportCode::comparisonKey($courseCode).'|'.SyllabusImportCode::comparisonKey($moduleCode);
    }

    /**
     * @return array{
     *     allSemesters: bool,
     *     moduleImportRole: 'primary'|'skip',
     *     groupKey: string|null,
     *     moduleSpansAllPeriods: bool,
     *     moduleDuplicateSamePeriod: bool,
     * }
     */
    private function emptyModuleMeta(): array
    {
        return [
            'allSemesters' => false,
            'moduleImportRole' => 'skip',
            'groupKey' => null,
            'moduleSpansAllPeriods' => false,
            'moduleDuplicateSamePeriod' => false,
        ];
    }

    /**
     * @return array{
     *     allSemesters: bool,
     *     moduleImportRole: 'primary'|'skip',
     *     groupKey: string|null,
     *     moduleSpansAllPeriods: bool,
     *     moduleDuplicateSamePeriod: bool,
     * }
     */
    private function defaultMeta(?string $groupKey): array
    {
        return [
            'allSemesters' => false,
            'moduleImportRole' => 'primary',
            'groupKey' => $groupKey,
            'moduleSpansAllPeriods' => false,
            'moduleDuplicateSamePeriod' => false,
        ];
    }
}
