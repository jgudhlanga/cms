<?php

declare(strict_types=1);

namespace App\Services\Institution;

final class CourseSyllabusImportFileStats
{
    /**
     * @param  list<array{number: int, data: array<string, mixed>}>  $parsedRows
     * @return array{
     *     totalRows: int,
     *     uniqueCourseCodes: int,
     *     uniqueModuleCodes: int,
     *     uniqueModuleRecords: int,
     *     duplicateModuleCodeGroups: int,
     *     extraRowsFromDuplicateModuleCodes: int,
     *     moduleRows: int,
     *     moduleSkipRows: int,
     * }
     */
    public static function fromParsedRows(array $parsedRows): array
    {
        $courseCodes = [];
        $moduleCodes = [];
        $moduleRecords = [];
        $moduleCodeCounts = [];
        $moduleRows = 0;
        $moduleSkipRows = 0;

        foreach ($parsedRows as $parsedRow) {
            $rowData = $parsedRow['data'];
            $courseCode = trim((string) ($rowData['COURSE_CODE'] ?? ''));
            $moduleCode = trim((string) ($rowData['MODULE_CODE'] ?? ''));
            $moduleTitle = trim((string) ($rowData['MODULE_TITLE'] ?? ''));

            if ($courseCode !== '') {
                $courseCodes[$courseCode] = true;
            }

            if ($moduleCode === '' && $moduleTitle === '') {
                $moduleSkipRows++;

                continue;
            }

            $moduleRows++;

            if ($moduleCode !== '') {
                $moduleCodes[$moduleCode] = true;
                $moduleCodeCounts[$moduleCode] = ($moduleCodeCounts[$moduleCode] ?? 0) + 1;
            }

            if ($courseCode !== '' && $moduleCode !== '') {
                $moduleRecords[$courseCode.'|'.$moduleCode] = true;
            }
        }

        $duplicateGroups = 0;
        $extraRows = 0;

        foreach ($moduleCodeCounts as $count) {
            if ($count > 1) {
                $duplicateGroups++;
                $extraRows += $count - 1;
            }
        }

        return [
            'totalRows' => count($parsedRows),
            'uniqueCourseCodes' => count($courseCodes),
            'uniqueModuleCodes' => count($moduleCodes),
            'uniqueModuleRecords' => count($moduleRecords),
            'duplicateModuleCodeGroups' => $duplicateGroups,
            'extraRowsFromDuplicateModuleCodes' => $extraRows,
            'moduleRows' => $moduleRows,
            'moduleSkipRows' => $moduleSkipRows,
        ];
    }

    /**
     * @param  list<array{number: int, data: array<string, mixed>}>  $parsedRows
     * @return array<string, int>
     */
    public static function moduleCodeOccurrences(array $parsedRows): array
    {
        $counts = [];

        foreach ($parsedRows as $parsedRow) {
            $moduleCode = trim((string) ($parsedRow['data']['MODULE_CODE'] ?? ''));
            $moduleTitle = trim((string) ($parsedRow['data']['MODULE_TITLE'] ?? ''));

            if ($moduleCode === '' && $moduleTitle === '') {
                continue;
            }

            if ($moduleCode === '') {
                continue;
            }

            $counts[$moduleCode] = ($counts[$moduleCode] ?? 0) + 1;
        }

        return $counts;
    }
}
