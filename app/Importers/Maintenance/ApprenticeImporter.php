<?php

declare(strict_types=1);

namespace App\Importers\Maintenance;

use RuntimeException;
use Spatie\SimpleExcel\SimpleExcelReader;

class ApprenticeImporter
{
    /** @var list<string> */
    public const array COLUMNS = [
        'ID Number',
        'Student Number',
        'Apprentice Number',
        'Employer',
    ];

    /** @var array<string, list<string>> */
    public const array HEADER_ALIASES = [
        'id_number' => ['ID NUMBER', 'NATIONAL ID NUMBER'],
        'student_number' => ['STUDENT NUMBER'],
        'apprentice_number' => ['APPRENTICE NUMBER', 'NUMBER', 'APPLICATION NUMBER'],
        'employer' => ['EMPLOYER', 'COMPANY', 'C0MPANY'],
    ];

    /**
     * @return array{
     *     rows: list<array{
     *         rowNumber: int,
     *         idNumber: string|null,
     *         studentNumber: string|null,
     *         apprenticeNumber: string|null,
     *         employer: string|null,
     *     }>,
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
            throw new RuntimeException(__('trans.maintenance_apprentice_import_preview_failed'));
        }

        $columnMap = $this->mapColumns($sheetRows[$headerRowNumber - 1] ?? []);
        $parsedRows = [];

        foreach (array_slice($sheetRows, $headerRowNumber) as $index => $row) {
            $rowNumber = $headerRowNumber + $index + 1;
            $values = $this->extractRowValues($row, $columnMap);

            if ($this->isBlankRow($values)) {
                continue;
            }

            if ($this->isSecondaryHeaderRow($values)) {
                continue;
            }

            $parsedRows[] = [
                'rowNumber' => $rowNumber,
                'idNumber' => $values['id_number'],
                'studentNumber' => $values['student_number'],
                'apprenticeNumber' => $values['apprentice_number'],
                'employer' => $values['employer'],
            ];
        }

        return [
            'rows' => $parsedRows,
            'headerRowNumber' => $headerRowNumber,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sheetRows
     */
    private function detectHeaderRowNumber(array $sheetRows): ?int
    {
        foreach ($sheetRows as $index => $row) {
            $columnMap = $this->mapColumns($row);

            if ($columnMap['id_number'] !== null || $columnMap['student_number'] !== null) {
                return $index + 1;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{
     *     id_number: int|null,
     *     student_number: int|null,
     *     apprentice_number: int|null,
     *     employer: int|null,
     * }
     */
    private function mapColumns(array $row): array
    {
        $normalizedHeaders = [];

        foreach (array_values($row) as $index => $value) {
            $normalizedHeaders[$index] = $this->normalizeHeader((string) $value);
        }

        $columnMap = [
            'id_number' => null,
            'student_number' => null,
            'apprentice_number' => null,
            'employer' => null,
        ];

        foreach (self::HEADER_ALIASES as $field => $aliases) {
            foreach ($normalizedHeaders as $index => $header) {
                if ($header === '' || in_array($header, ['NO', 'NO.', 'SURNAME', 'FIRST NAME', 'DOB', 'INDICATOR', 'APPRENTICE'], true)) {
                    continue;
                }

                if (in_array($header, $aliases, true)) {
                    $columnMap[$field] = $index;

                    break;
                }
            }
        }

        return $columnMap;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{
     *     id_number: int|null,
     *     student_number: int|null,
     *     apprentice_number: int|null,
     *     employer: int|null,
     * }  $columnMap
     * @return array{
     *     id_number: string|null,
     *     student_number: string|null,
     *     apprentice_number: string|null,
     *     employer: string|null,
     * }
     */
    private function extractRowValues(array $row, array $columnMap): array
    {
        $values = array_values($row);

        return [
            'id_number' => $this->cellValue($values, $columnMap['id_number']),
            'student_number' => $this->cellValue($values, $columnMap['student_number']),
            'apprentice_number' => $this->cellValue($values, $columnMap['apprentice_number']),
            'employer' => $this->cellValue($values, $columnMap['employer']),
        ];
    }

    /**
     * @param  list<mixed>  $values
     */
    private function cellValue(array $values, ?int $index): ?string
    {
        if ($index === null || ! array_key_exists($index, $values)) {
            return null;
        }

        $value = trim((string) $values[$index]);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array{
     *     id_number: string|null,
     *     student_number: string|null,
     *     apprentice_number: string|null,
     *     employer: string|null,
     * }  $values
     */
    private function isBlankRow(array $values): bool
    {
        return $values['id_number'] === null
            && $values['student_number'] === null
            && $values['apprentice_number'] === null
            && $values['employer'] === null;
    }

    /**
     * @param  array{
     *     id_number: string|null,
     *     student_number: string|null,
     *     apprentice_number: string|null,
     *     employer: string|null,
     * }  $values
     */
    private function isSecondaryHeaderRow(array $values): bool
    {
        $idNumber = strtoupper((string) $values['id_number']);
        $studentNumber = strtoupper((string) $values['student_number']);

        return in_array($idNumber, ['NATIONAL ID NUMBER', 'ID NUMBER', '(DD/MM/YYYY)'], true)
            || in_array($studentNumber, ['STUDENT NUMBER'], true);
    }

    private function normalizeHeader(string $value): string
    {
        $normalized = strtoupper(trim($value));
        $normalized = str_replace(['_', '.'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
