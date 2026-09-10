<?php

declare(strict_types=1);

namespace App\Importers\AcademicCalendars;

use RuntimeException;
use Spatie\SimpleExcel\SimpleExcelReader;

class StudentNumberProgressionImporter
{
    /** @var list<string> */
    public const array COLUMNS = [
        'Student Number',
        'ID Number',
        'Name',
        'Department',
        'Level',
        'Course',
        'Mode',
    ];

    /** @var array<string, list<string>> */
    public const array HEADER_ALIASES = [
        'student_number' => ['STUDENT NUMBER', 'STUDENT NO', 'STUDENT NO.', 'REG NUMBER', 'REGISTRATION NUMBER'],
        'id_number' => ['ID NUMBER', 'NATIONAL ID', 'NATIONAL ID NUMBER', 'PASSPORT NUMBER'],
        'name' => ['NAME', 'STUDENT NAME', 'FULL NAME'],
        'department' => ['DEPARTMENT'],
        'level' => ['LEVEL'],
        'course' => ['COURSE', 'PROGRAMME', 'PROGRAM'],
        'mode' => ['MODE', 'MODE OF STUDY'],
    ];

    /**
     * @return array{
     *     rows: list<array{rowNumber: int, studentNumber: string|null}>,
     *     headerRowNumber: int,
     * }
     */
    public function parse(string $filePath): array
    {
        $reader = SimpleExcelReader::create($filePath)->noHeaderRow();
        $sheetRows = [];

        $reader->getRows()->each(function (array $row) use (&$sheetRows): void {
            $sheetRows[] = array_values($row);
        });

        $reader->close();

        if ($sheetRows === []) {
            return [
                'rows' => [],
                'headerRowNumber' => 0,
            ];
        }

        $headerRowNumber = $this->detectHeaderRowNumber($sheetRows);

        if ($headerRowNumber === null) {
            throw new RuntimeException(__('academic_calendar.progression_import_preview_failed'));
        }

        $columnMap = $this->mapColumns($sheetRows[$headerRowNumber - 1] ?? []);
        $parsedRows = [];

        foreach (array_slice($sheetRows, $headerRowNumber) as $index => $row) {
            $rowNumber = $headerRowNumber + $index + 1;
            $studentNumber = $this->normalize($row[$columnMap['student_number']] ?? null);

            if ($studentNumber === null) {
                continue;
            }

            $parsedRows[] = [
                'rowNumber' => $rowNumber,
                'studentNumber' => $studentNumber,
            ];
        }

        return [
            'rows' => $parsedRows,
            'headerRowNumber' => $headerRowNumber,
        ];
    }

    /**
     * @param  list<array<int, mixed>>  $sheetRows
     */
    private function detectHeaderRowNumber(array $sheetRows): ?int
    {
        foreach ($sheetRows as $index => $row) {
            $columnMap = $this->mapColumns($row);
            if (isset($columnMap['student_number'])) {
                return $index + 1;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<string, int>
     */
    private function mapColumns(array $row): array
    {
        $map = [];

        foreach ($row as $index => $cell) {
            $normalized = strtoupper(trim((string) $cell));
            if ($normalized === '') {
                continue;
            }

            foreach (self::HEADER_ALIASES as $key => $aliases) {
                if (in_array($normalized, $aliases, true)) {
                    $map[$key] = (int) $index;
                }
            }
        }

        return $map;
    }

    private function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
